<?php
class AttendanceService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Jalankan pemrosesan kehadiran untuk semua karyawan pada tanggal tertentu
     */
    public function processDaily(string $date): array
    {
        $results = ['processed' => 0, 'skipped_holiday' => 0, 'updated' => 0];

        // [Fix 2.6] Cek apakah tanggal ini adalah hari libur nasional
        if ($this->isNationalHoliday($date)) {
            $results['skipped_holiday'] = 1;
            return $results;
        }

        // Cek apakah weekend (Sabtu/Minggu)
        $dayOfWeek = (int)date('N', strtotime($date)); // 1=Sen, 7=Min
        if ($dayOfWeek >= 6) {
            $results['skipped_holiday'] = 1;
            return $results;
        }

        // Ambil semua karyawan aktif dan shift mereka (prioritas: employee_shifts -> position_shifts)
        $employees = $this->db->query(
            "SELECT e.id, e.department_id, e.position_id,
                    COALESCE(es.shift_id, ps.shift_id) AS shift_id,
                    COALESCE(s_emp.start_time, s_pos.start_time) AS start_time,
                    COALESCE(s_emp.end_time, s_pos.end_time) AS end_time
             FROM employees e
             LEFT JOIN employee_shifts es ON es.employee_id = e.id 
                AND es.effective_date <= ? AND (es.end_date >= ? OR es.end_date IS NULL)
             LEFT JOIN shifts s_emp ON s_emp.id = es.shift_id
             LEFT JOIN position_shifts ps ON ps.position_id = e.position_id
             LEFT JOIN shifts s_pos ON s_pos.id = ps.shift_id
             WHERE e.employment_status = 'active'",
            [$date, $date]
        )->fetchAll();

        foreach ($employees as $emp) {
            $this->resolveStatus($emp['id'], $date, $emp);
            $results['processed']++;
        }

        return $results;
    }

    /**
     * [Fix 2.6] Cek apakah tanggal adalah hari libur nasional
     */
    public function isNationalHoliday(string $date): bool
    {
        $row = $this->db->query(
            "SELECT id FROM national_holidays WHERE date = ? LIMIT 1",
            [$date]
        )->fetch();
        return (bool) $row;
    }

    /**
     * Tentukan status final untuk satu karyawan pada satu tanggal
     * [Fix 2.2] Merge approved requests with finger data instead of override
     * [Fix 2.3] Integrate camera attendance logs
     */
    public function resolveStatus(int $empId, string $date, ?array $empInfo = null): string
    {
        if (!$empInfo) {
            $empInfo = $this->db->query(
                "SELECT e.id, e.position_id,
                        COALESCE(es.shift_id, ps.shift_id) AS shift_id,
                        COALESCE(s_emp.start_time, s_pos.start_time) AS start_time,
                        COALESCE(s_emp.end_time, s_pos.end_time) AS end_time
                 FROM employees e
                 LEFT JOIN employee_shifts es ON es.employee_id = e.id 
                    AND es.effective_date <= ? AND (es.end_date >= ? OR es.end_date IS NULL)
                 LEFT JOIN shifts s_emp ON s_emp.id = es.shift_id
                 LEFT JOIN position_shifts ps ON ps.position_id = e.position_id
                 LEFT JOIN shifts s_pos ON s_pos.id = ps.shift_id
                 WHERE e.id = ?",
                [$date, $date, $empId]
            )->fetch();
        }

        // 1. Cek Pengajuan (Requests) yang sudah APPROVED
        $request = $this->db->query(
            "SELECT ar.request_type, ar.id as request_id
             FROM attendance_requests ar
             WHERE ar.employee_id = ? AND ar.attendance_date = ? AND ar.workflow_status = 'approved'
             LIMIT 1",
            [$empId, $date]
        )->fetch();

        if ($request) {
            $status = match($request['request_type']) {
                'paid_leave'   => 'PAID_LEAVE',
                'tidak_hadir'  => 'UNPAID_DENGAN',
                'sakit'        => 'SAKIT',
                'hourly_leave' => 'HOURLY_UNPAID',
                'tidak_finger' => 'HADIR',
                default        => 'HADIR'
            };

            // [Fix 2.2] Untuk tidak_finger yang disetujui, tetap hitung keterlambatan dari finger log
            $lateMinutes = 0;
            if ($request['request_type'] === 'tidak_finger' && $empInfo && $empInfo['start_time']) {
                $logs = $this->getAttendanceLogs($empId, $date);
                if ($logs && $logs['timestamp_in']) {
                    $lateMinutes = $this->calculateLateMinutes($empInfo['start_time'], $logs['timestamp_in']);
                }
            }

            $this->updateStatusTable($empId, $date, $status, $lateMinutes, 'Approved Request: ' . $request['request_type']);
            return $status;
        }

        // 2. Cek Finger Logs + Camera Logs [Fix 2.3]
        $logs = $this->getAttendanceLogs($empId, $date);

        $status = 'NO_LOG';
        $lateMinutes = 0;
        $notes = '';
        $source = 'finger';

        if ($logs && $logs['timestamp_in']) {
            if ($logs['timestamp_out']) {
                $status = 'HADIR';
                // Hitung keterlambatan jika ada jadwal
                if ($empInfo && $empInfo['start_time']) {
                    $lateMinutes = $this->calculateLateMinutes($empInfo['start_time'], $logs['timestamp_in']);
                }
            } else {
                $status = 'PENDING'; // Lupa finger keluar
                $notes = 'Missing Clock Out';
            }
            $source = $logs['source'] ?? 'finger';
        } else {
            // Tidak ada finger atau kamera sama sekali
            $status = 'UNPAID_TANPA';
            $notes = 'No log found (Alpha)';
        }

        $this->updateStatusTable($empId, $date, $status, $lateMinutes, $notes, $source);

        // [Fix 7.2] Send notification to supervisor if missing attendance
        if ($status === 'UNPAID_TANPA' || $status === 'PENDING' || $lateMinutes >= 60) {
            $this->notifySupervisor($empId, $date, $status, $lateMinutes);
        }

        return $status;
    }

    /**
     * [Fix 2.3] Ambil log kehadiran dari finger_logs ATAU camera_attendance_logs
     * Finger logs diprioritaskan; camera dipakai jika finger tidak ada
     */
    private function getAttendanceLogs(int $empId, string $date): ?array
    {
        // Coba finger log dulu
        $finger = $this->db->query(
            "SELECT timestamp_in, timestamp_out, 'finger' AS source FROM finger_logs 
             WHERE employee_id = ? AND log_date = ? LIMIT 1",
            [$empId, $date]
        )->fetch();

        if ($finger && $finger['timestamp_in']) {
            return $finger;
        }

        // Fallback ke camera log (verified only)
        $camera = $this->db->query(
            "SELECT timestamp_in, timestamp_out, 'camera' AS source FROM camera_attendance_logs 
             WHERE employee_id = ? AND log_date = ? AND status = 'verified' LIMIT 1",
            [$empId, $date]
        )->fetch();

        if ($camera && $camera['timestamp_in']) {
            return $camera;
        }

        // Jika ada finger log tapi tanpa timestamp_in (misalnya hanya timestamp_out)
        if ($finger) {
            return $finger;
        }

        return null;
    }

    /**
     * [Fix 2.1] Hitung selisih menit keterlambatan (dengan toleransi)
     * FIXED: Returns diff MINUS tolerance, not the full diff
     */
    private function calculateLateMinutes(string $scheduledTime, string $actualTimestamp): int
    {
        $scheduled = strtotime(date('Y-m-d', strtotime($actualTimestamp)) . ' ' . $scheduledTime);
        $actual    = strtotime($actualTimestamp);
        
        if ($actual <= $scheduled) return 0;

        $diff = (int) floor(($actual - $scheduled) / 60);
        
        // Ambil toleransi dari system_settings, fallback ke 10 menit
        $tolerance = $this->getSettingInt('late_tolerance_minutes', 10);
        if ($diff <= $tolerance) return 0;

        // [Fix 2.1] Return diff MINUS tolerance, bukan full diff
        return $diff - $tolerance;
    }

    /**
     * Helper: ambil setting integer dari system_settings
     */
    public function getSettingInt(string $key, int $default): int
    {
        $row = $this->db->query(
            "SELECT `value` FROM system_settings WHERE `key` = ? LIMIT 1",
            [$key]
        )->fetch();
        return $row ? (int) $row['value'] : $default;
    }

    /**
     * Helper: ambil setting decimal dari system_settings
     */
    public function getSettingDecimal(string $key, float $default): float
    {
        $row = $this->db->query(
            "SELECT `value` FROM system_settings WHERE `key` = ? LIMIT 1",
            [$key]
        )->fetch();
        return $row ? (float) $row['value'] : $default;
    }

    private function updateStatusTable(int $empId, string $date, string $status, int $late, string $notes, string $source = 'finger'): void
    {
        $this->db->query(
            "INSERT INTO daily_attendance_status (employee_id, attendance_date, final_status, late_minutes, notes, source)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
                final_status = VALUES(final_status), 
                late_minutes = VALUES(late_minutes),
                notes = VALUES(notes),
                source = VALUES(source),
                updated_at = NOW()",
            [$empId, $date, $status, $late, $notes, $source]
        );
    }

    /**
     * [Fix 7.2] Notifikasi ke supervisor bila ada pelanggaran kehadiran (alpha, lupa finger, telat parah)
     */
    private function notifySupervisor(int $empId, string $date, string $status, int $lateMinutes): void
    {
        // Get supervisor ID
        $supervisor = $this->db->query(
            "SELECT u.id as user_id, e.first_name, e.last_name 
             FROM employees e
             JOIN employees sup ON sup.id = e.supervisor_id
             JOIN users u ON u.employee_id = sup.id
             WHERE e.id = ?",
            [$empId]
        )->fetch();

        if ($supervisor && $supervisor['user_id']) {
            $empName = $supervisor['first_name'] . ' ' . $supervisor['last_name'];
            $title = "Peringatan Kehadiran: {$empName}";
            $message = "Karyawan {$empName} ";
            if ($status === 'UNPAID_TANPA') {
                $message .= "tidak hadir (Alpha) pada tanggal {$date}.";
            } elseif ($status === 'PENDING') {
                $message .= "lupa melakukan absen keluar pada tanggal {$date}.";
            } elseif ($lateMinutes >= 60) {
                $message .= "terlambat {$lateMinutes} menit pada tanggal {$date}.";
            }
            
            // Check if notif already sent today to avoid spam
            $existing = $this->db->query(
                "SELECT id FROM notifications WHERE user_id = ? AND title = ? AND DATE(created_at) = CURDATE()",
                [$supervisor['user_id'], $title]
            )->fetch();

            if (!$existing) {
                $this->db->query(
                    "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'warning')",
                    [$supervisor['user_id'], $title, $message]
                );
            }
        }
    }
}

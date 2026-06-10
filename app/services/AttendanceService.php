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
        $results = ['processed' => 0, 'updated' => 0];
        
        // Ambil semua karyawan aktif
        $employees = $this->db->query(
            "SELECT e.id, e.department_id, es.shift_id, s.start_time, s.end_time
             FROM employees e
             LEFT JOIN employee_shifts es ON es.employee_id = e.id 
                AND es.effective_date <= ? AND (es.end_date >= ? OR es.end_date IS NULL)
             LEFT JOIN shifts s ON s.id = es.shift_id
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
     * Tentukan status final untuk satu karyawan pada satu tanggal
     */
    public function resolveStatus(int $empId, string $date, ?array $empInfo = null): string
    {
        if (!$empInfo) {
            $empInfo = $this->db->query(
                "SELECT e.id, es.shift_id, s.start_time, s.end_time
                 FROM employees e
                 LEFT JOIN employee_shifts es ON es.employee_id = e.id 
                    AND es.effective_date <= ? AND (es.end_date >= ? OR es.end_date IS NULL)
                 LEFT JOIN shifts s ON s.id = es.shift_id
                 WHERE e.id = ?",
                [$date, $date, $empId]
            )->fetch();
        }

        // 1. Cek Pengajuan (Requests) yang sudah APPROVED
        $request = $this->db->query(
            "SELECT request_type FROM attendance_requests 
             WHERE employee_id = ? AND attendance_date = ? AND workflow_status = 'approved'
             LIMIT 1",
            [$empId, $date]
        )->fetch();

        if ($request) {
            $status = match($request['request_type']) {
                'paid_leave'   => 'PAID_LEAVE',
                'tidak_hadir'  => 'UNPAID_DENGAN',
                'sakit'        => 'SAKIT',
                'hourly_leave' => 'HOURLY_UNPAID',
                'tidak_finger' => 'HADIR', // Dianggap hadir jika pengajuan disetujui
                default        => 'HADIR'
            };
            $this->updateStatusTable($empId, $date, $status, 0, 'Approved Request');
            return $status;
        }

        // 2. Cek Finger Logs
        $logs = $this->db->query(
            "SELECT * FROM finger_logs WHERE employee_id = ? AND log_date = ? LIMIT 1",
            [$empId, $date]
        )->fetch();

        $status = 'NO_LOG';
        $lateMinutes = 0;
        $notes = '';

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
        } else {
            // Tidak ada finger sama sekali
            $status = 'UNPAID_TANPA';
            $notes = 'No log found (Alpha)';
        }

        $this->updateStatusTable($empId, $date, $status, $lateMinutes, $notes);
        return $status;
    }

    /**
     * Hitung selisih menit keterlambatan (dengan toleransi)
     */
    private function calculateLateMinutes(string $scheduledTime, string $actualTimestamp): int
    {
        $scheduled = strtotime(date('Y-m-d', strtotime($actualTimestamp)) . ' ' . $scheduledTime);
        $actual    = strtotime($actualTimestamp);
        
        if ($actual <= $scheduled) return 0;

        $diff = floor(($actual - $scheduled) / 60);
        
        // Toleransi (Misal 10 menit sesuai configApp2.md L413)
        $tolerance = 10; 
        if ($diff <= $tolerance) return 0;

        return (int)$diff;
    }

    private function updateStatusTable(int $empId, string $date, string $status, int $late, string $notes): void
    {
        $this->db->query(
            "INSERT INTO daily_attendance_status (employee_id, attendance_date, final_status, late_minutes, notes)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
                final_status = VALUES(final_status), 
                late_minutes = VALUES(late_minutes),
                notes = VALUES(notes),
                updated_at = NOW()",
            [$empId, $date, $status, $late, $notes]
        );
    }
}

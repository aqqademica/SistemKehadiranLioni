<?php
class DisciplineService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Periksa dan buat Warning Letter jika pola pelanggaran terpenuhi
     * [Fix 4.4] Accept issuer ID parameter instead of hardcoded 1
     */
    public function checkAndIssueWL(int $empId, int $issuerId = 0): ?string
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        // [Fix 4.1] Ambil data absensi bulan ini — ORDER BY ASC for correct consecutive detection
        $attendance = $this->db->query(
            "SELECT attendance_date, final_status 
             FROM daily_attendance_status 
             WHERE employee_id = ? AND attendance_date >= ? 
             ORDER BY attendance_date ASC",
            [$empId, $monthStart]
        )->fetchAll();

        // 2. Cek histori WL
        $lastWL = $this->db->query(
            "SELECT wl_type FROM warning_letters WHERE employee_id = ? ORDER BY issued_at DESC LIMIT 1",
            [$empId]
        )->fetch();

        // Count total WL per type
        $wlCounts = $this->db->query(
            "SELECT wl_type, COUNT(*) as cnt FROM warning_letters WHERE employee_id = ? GROUP BY wl_type",
            [$empId]
        )->fetchAll();
        $wlCountMap = [];
        foreach ($wlCounts as $wc) {
            $wlCountMap[$wc['wl_type']] = (int)$wc['cnt'];
        }
        $wl1Count = $wlCountMap['WL1'] ?? 0;
        $wl2Count = $wlCountMap['WL2'] ?? 0;
        $wl3Count = $wlCountMap['WL3'] ?? 0;

        // 3. Hitung Alpha (Unpaid Tanpa Keterangan) dan Unpaid Dengan Keterangan
        $alphaCount = 0;
        $maxConsecutiveAlpha = 0;
        $consecutiveAlpha = 0;

        $unpaidDenganCount = 0;
        $maxConsecutiveUnpaidDengan = 0;
        $consecutiveUnpaidDengan = 0;

        // [Fix 4.1] Use ASC order + track previous date for gap detection
        $prevDate = null;
        
        foreach ($attendance as $att) {
            $currentDate = $att['attendance_date'];
            
            // Check if dates are consecutive (skip weekends)
            $isConsecutive = true;
            if ($prevDate !== null) {
                $expectedNext = $this->getNextWorkday($prevDate);
                if ($currentDate !== $expectedNext) {
                    $isConsecutive = false;
                }
            }

            if ($att['final_status'] === 'UNPAID_TANPA') {
                $alphaCount++;
                if ($isConsecutive || $prevDate === null) {
                    $consecutiveAlpha++;
                } else {
                    $consecutiveAlpha = 1; // Reset but count this day
                }
                $maxConsecutiveAlpha = max($maxConsecutiveAlpha, $consecutiveAlpha);
            } else {
                $consecutiveAlpha = 0;
            }

            // [Fix 4.3] Track UNPAID_DENGAN consecutive days
            if ($att['final_status'] === 'UNPAID_DENGAN') {
                $unpaidDenganCount++;
                if ($isConsecutive || $prevDate === null) {
                    $consecutiveUnpaidDengan++;
                } else {
                    $consecutiveUnpaidDengan = 1;
                }
                $maxConsecutiveUnpaidDengan = max($maxConsecutiveUnpaidDengan, $consecutiveUnpaidDengan);
            } else {
                $consecutiveUnpaidDengan = 0;
            }

            $prevDate = $currentDate;
        }

        // [Fix 4.1] Cross-month consecutive detection for alpha
        if ($consecutiveAlpha > 0 || $maxConsecutiveAlpha > 0) {
            $prevMonthAlpha = $this->getTrailingConsecutiveAlpha($empId, $monthStart);
            // If the first days of this month continue the streak from last month
            $firstAlphaStreak = 0;
            foreach ($attendance as $att) {
                if ($att['final_status'] === 'UNPAID_TANPA') {
                    $firstAlphaStreak++;
                } else {
                    break;
                }
            }
            $crossMonthConsecutive = $prevMonthAlpha + $firstAlphaStreak;
            $maxConsecutiveAlpha = max($maxConsecutiveAlpha, $crossMonthConsecutive);
        }

        // 4. Logika Penentuan WL
        $newWL = null;
        $reason = "";

        // Logika Termination (§12D): sudah WL3, lalu dapat WL1 atau WL2 lagi
        if ($lastWL && $lastWL['wl_type'] === 'WL3' && $alphaCount > 0) {
            $newWL = 'TERMINATION';
            $reason = "Pelanggaran berulang setelah Warning Letter 3.";
        }
        // Logika WL3 (§12C): 3 hari berturut-turut alpha + sudah pernah WL2 atau WL1 >= 3x
        elseif ($maxConsecutiveAlpha >= 3 && $this->hasSignificantHistory($empId)) {
            $newWL = 'WL3';
            $reason = "Absen tanpa keterangan 3 hari berturut-turut dengan riwayat SP sebelumnya.";
        }
        // Logika WL2 (§12B): 3 hari berturut-turut alpha, ATAU WL1 > 3x dalam sebulan, 
        // ATAU unpaid dengan keterangan 5 hari berturut-turut
        elseif ($maxConsecutiveAlpha >= 3) {
            $newWL = 'WL2';
            $reason = "Absen tanpa keterangan 3 hari berturut-turut.";
        }
        // [Fix 4.3] WL2 dari WL1 terlalu banyak
        elseif ($this->getWL1CountThisMonth($empId) > 3) {
            $newWL = 'WL2';
            $reason = "Warning Letter 1 lebih dari 3 kali dalam satu bulan.";
        }
        // [Fix 4.3] WL2 dari UNPAID_DENGAN 5 hari berturut-turut
        elseif ($maxConsecutiveUnpaidDengan >= 5) {
            $newWL = 'WL2';
            $reason = "Unpaid leave dengan keterangan 5 hari berturut-turut dalam satu bulan.";
        }
        // Logika WL1 (§12A): Alpha 1 kali (maks 1 WL1 per bulan)
        // [Fix 4.2] Removed 'NONE' check — just check if alpha occurred
        elseif ($alphaCount >= 1) {
            $newWL = 'WL1';
            $reason = "Absen tanpa keterangan (Alpha) dalam bulan ini.";
        }

        if ($newWL) {
            return $this->issueWL($empId, $newWL, $reason, $issuerId);
        }

        return null;
    }

    /**
     * [Fix 4.1] Hitung berapa hari alpha berturut-turut di akhir bulan sebelumnya
     */
    private function getTrailingConsecutiveAlpha(int $empId, string $currentMonthStart): int
    {
        $prevMonthEnd = date('Y-m-d', strtotime($currentMonthStart . ' -1 day'));
        $prevMonthStart = date('Y-m-01', strtotime($prevMonthEnd));
        
        $attendance = $this->db->query(
            "SELECT attendance_date, final_status 
             FROM daily_attendance_status 
             WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?
             ORDER BY attendance_date DESC",
            [$empId, $prevMonthStart, $prevMonthEnd]
        )->fetchAll();

        $consecutiveAlpha = 0;
        foreach ($attendance as $att) {
            if ($att['final_status'] === 'UNPAID_TANPA') {
                $consecutiveAlpha++;
            } else {
                break;
            }
        }
        return $consecutiveAlpha;
    }

    /**
     * [Fix 4.3] Hitung jumlah WL1 di bulan ini
     */
    private function getWL1CountThisMonth(int $empId): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM warning_letters 
             WHERE employee_id = ? AND wl_type = 'WL1' 
             AND MONTH(issued_at) = MONTH(NOW()) AND YEAR(issued_at) = YEAR(NOW())",
            [$empId]
        )->fetchColumn();
    }

    /**
     * Helper: cari hari kerja berikutnya (skip weekend)
     */
    private function getNextWorkday(string $date): string
    {
        $next = strtotime($date . ' +1 day');
        while (date('N', $next) >= 6) { // Skip Sat/Sun
            $next = strtotime('+1 day', $next);
        }
        return date('Y-m-d', $next);
    }

    private function hasSignificantHistory(int $empId): bool
    {
        $wlCount = $this->db->query(
            "SELECT COUNT(*) FROM warning_letters WHERE employee_id = ? AND wl_type IN ('WL1', 'WL2')",
            [$empId]
        )->fetchColumn();
        return $wlCount >= 1;
    }

    /**
     * [Fix 4.4] Accept issuerId parameter
     */
    private function issueWL(int $empId, string $type, string $reason, int $issuerId = 0): string
    {
        // Cek apakah WL tipe yang sama sudah keluar di bulan yang sama untuk menghindari duplikasi instan
        $existing = $this->db->query(
            "SELECT id FROM warning_letters 
             WHERE employee_id = ? AND wl_type = ? AND MONTH(issued_at) = MONTH(NOW()) AND YEAR(issued_at) = YEAR(NOW())",
            [$empId, $type]
        )->fetch();

        if ($existing) return "Already issued this month.";

        // [Fix 4.4] Use actual issuer ID, fallback to system user (first HRD admin found)
        if ($issuerId <= 0) {
            $hrdUser = $this->db->query(
                "SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id 
                 WHERE r.name IN ('hrd_admin', 'hrd_manager') AND u.is_active = 1 LIMIT 1"
            )->fetch();
            $issuerId = $hrdUser ? (int)$hrdUser['id'] : 1;
        }

        $this->db->query(
            "INSERT INTO warning_letters (employee_id, wl_type, trigger_reason, trigger_date, issued_by, issued_at) 
             VALUES (?, ?, ?, CURDATE(), ?, NOW())",
            [$empId, $type, $reason, $issuerId]
        );

        // Buat notifikasi
        $this->db->query(
            "INSERT INTO notifications (user_id, title, message, type) 
             SELECT u.id, 'Surat Peringatan Baru', ?, 'danger' 
             FROM users u WHERE u.employee_id = ?",
            ["Anda telah menerima {$type} karena: {$reason}", $empId]
        );

        return "Issued {$type}.";
    }
}

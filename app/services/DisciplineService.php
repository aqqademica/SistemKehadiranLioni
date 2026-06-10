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
     */
    public function checkAndIssueWL(int $empId): ?string
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        // 1. Ambil data absensi bulan ini
        $attendance = $this->db->query(
            "SELECT attendance_date, final_status 
             FROM daily_attendance_status 
             WHERE employee_id = ? AND attendance_date >= ? 
             ORDER BY attendance_date DESC",
            [$empId, $monthStart]
        )->fetchAll();

        // 2. Cek histori WL
        $lastWL = $this->db->query(
            "SELECT wl_type FROM warning_letters WHERE employee_id = ? ORDER BY issued_at DESC LIMIT 1",
            [$empId]
        )->fetch();

        // 3. Hitung Alpha (Unpaid Tanpa Keterangan)
        $alphaCount = 0;
        $consecutiveAlpha = 0;
        $maxConsecutiveAlpha = 0;
        
        foreach ($attendance as $att) {
            if ($att['final_status'] === 'UNPAID_TANPA') {
                $alphaCount++;
                $consecutiveAlpha++;
                $maxConsecutiveAlpha = max($maxConsecutiveAlpha, $consecutiveAlpha);
            } else {
                $consecutiveAlpha = 0;
            }
        }

        // 4. Logika Penentuan WL
        $newWL = null;
        $reason = "";

        // Logika Termination
        if ($lastWL && $lastWL['wl_type'] === 'WL3' && $alphaCount > 0) {
            $newWL = 'TERMINATION';
            $reason = "Pelanggaran berulang setelah Warning Letter 3.";
        }
        // Logika WL 3
        elseif ($maxConsecutiveAlpha >= 3 && $this->hasSignificantHistory($empId)) {
            $newWL = 'WL3';
            $reason = "Absen tanpa keterangan 3 hari berturut-turut dengan riwayat SP sebelumnya.";
        }
        // Logika WL 2
        elseif ($maxConsecutiveAlpha >= 3 || $alphaCount >= 3) {
            $newWL = 'WL2';
            $reason = "Absen tanpa keterangan berulang (3 hari atau berturut-turut).";
        }
        // Logika WL 1
        elseif ($alphaCount >= 1 && (!$lastWL || $lastWL['wl_type'] === 'NONE')) {
            $newWL = 'WL1';
            $reason = "Pertama kali absen tanpa keterangan (Alpha).";
        }

        if ($newWL) {
            return $this->issueWL($empId, $newWL, $reason);
        }

        return null;
    }

    private function hasSignificantHistory(int $empId): bool
    {
        $wlCount = $this->db->query(
            "SELECT COUNT(*) FROM warning_letters WHERE employee_id = ? AND wl_type IN ('WL1', 'WL2')",
            [$empId]
        )->fetchColumn();
        return $wlCount >= 1;
    }

    private function issueWL(int $empId, string $type, string $reason): string
    {
        // Cek apakah WL tipe yang sama sudah keluar di bulan yang sama untuk menghindari duplikasi instan
        $existing = $this->db->query(
            "SELECT id FROM warning_letters 
             WHERE employee_id = ? AND wl_type = ? AND MONTH(issued_at) = MONTH(NOW())",
            [$empId, $type]
        )->fetch();

        if ($existing) return "Already issued this month.";

        $this->db->query(
            "INSERT INTO warning_letters (employee_id, wl_type, trigger_reason, trigger_date, issued_by, issued_at) 
             VALUES (?, ?, ?, CURDATE(), 1, NOW())", // Issued by system (ID 1)
            [$empId, $type, $reason]
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

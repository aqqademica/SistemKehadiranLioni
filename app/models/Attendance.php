<?php
class Attendance extends Model
{
    protected string $table = 'daily_attendance_status';

    /**
     * Ambil data fingerprint harian untuk satu karyawan
     */
    public function getFingerLog(int $employeeId, string $date): ?array
    {
        return $this->db->query(
            "SELECT * FROM finger_logs WHERE employee_id = ? AND log_date = ? LIMIT 1",
            [$employeeId, $date]
        )->fetch() ?: null;
    }

    /**
     * Catat fingerprint dummy (Masuk/Keluar)
     */
    public function logFinger(int $employeeId, string $date, string $type, string $timestamp, string $deviceId = 'DUMMY-01'): bool
    {
        $existing = $this->getFingerLog($employeeId, $date);

        if ($existing) {
            $field = ($type === 'in') ? 'timestamp_in' : 'timestamp_out';
            return (bool) $this->db->query(
                "UPDATE finger_logs SET {$field} = ?, device_id = ? WHERE id = ?",
                [$timestamp, $deviceId, $existing['id']]
            );
        } else {
            $field = ($type === 'in') ? 'timestamp_in' : 'timestamp_out';
            return (bool) $this->db->query(
                "INSERT INTO finger_logs (employee_id, log_date, {$field}, device_id, is_dummy) VALUES (?, ?, ?, ?, 1)",
                [$employeeId, $date, $timestamp, $deviceId]
            );
        }
    }

    /**
     * Catat Camera Attendance (Selfie, dll)
     */
    public function logCamera(array $data): int
    {
        $cols = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $this->db->query(
            "INSERT INTO camera_attendance_logs (`{$cols}`) VALUES ({$placeholders})",
            array_values($data)
        );
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Ambil status kehadiran harian karyawan dalam satu periode
     */
    public function getAttendanceRange(int $employeeId, string $startDate, string $endDate): array
    {
        return $this->db->query(
            "SELECT * FROM daily_attendance_status 
             WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? 
             ORDER BY attendance_date ASC",
            [$employeeId, $startDate, $endDate]
        )->fetchAll();
    }

    /**
     * Update status final harian
     */
    public function updateDailyStatus(int $employeeId, string $date, array $data): bool
    {
        $existing = $this->findWhere(['employee_id' => $employeeId, 'attendance_date' => $date]);
        
        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            $data['employee_id'] = $employeeId;
            $data['attendance_date'] = $date;
            return (bool) $this->create($data);
        }
    }

    /**
     * Ambil summary kehadiran untuk dashboard
     */
    public function getSummary(int $employeeId, int $month, int $year): array
    {
        return $this->db->query(
            "SELECT 
                COUNT(CASE WHEN final_status = 'HADIR' THEN 1 END) as total_hadir,
                COUNT(CASE WHEN final_status LIKE 'UNPAID%' THEN 1 END) as total_unpaid,
                COUNT(CASE WHEN final_status = 'SAKIT' THEN 1 END) as total_sakit,
                COUNT(CASE WHEN final_status = 'PAID_LEAVE' THEN 1 END) as total_cuti,
                SUM(late_minutes) as total_late_minutes
             FROM daily_attendance_status 
             WHERE employee_id = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?",
            [$employeeId, $month, $year]
        )->fetch();
    }
}

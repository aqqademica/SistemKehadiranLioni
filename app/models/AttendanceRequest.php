<?php
class AttendanceRequest extends Model
{
    protected string $table = 'attendance_requests';

    /**
     * Ambil pengajuan karyawan dengan detailnya
     */
    public function getWithDetails(int $requestId): ?array
    {
        $base = $this->find($requestId);
        if (!$base) return null;

        $detailTable = match($base['request_type']) {
            'tidak_finger' => 'tidak_finger_requests',
            'paid_leave'   => 'leave_requests',
            'tidak_hadir'  => 'leave_requests', // Unpaid leave juga masuk leave_requests
            'sakit'        => 'sick_requests',
            'hourly_leave' => 'hourly_leave_requests',
            'overtime'     => 'overtime_requests',
            default        => null
        };

        if ($detailTable) {
            $details = $this->db->query(
                "SELECT * FROM `{$detailTable}` WHERE request_id = ? LIMIT 1",
                [$requestId]
            )->fetch();
            return array_merge($base, $details ?: []);
        }

        return $base;
    }

    /**
     * Buat pengajuan baru (Atomic)
     */
    public function createWithDetails(array $baseData, string $detailTable, array $detailData): int
    {
        try {
            $this->db->beginTransaction();
            
            $requestId = $this->create($baseData);
            
            $detailData['request_id'] = $requestId;
            $cols = implode('`, `', array_keys($detailData));
            $placeholders = implode(', ', array_fill(0, count($detailData), '?'));
            
            $this->db->query(
                "INSERT INTO `{$detailTable}` (`{$cols}`) VALUES ({$placeholders})",
                array_values($detailData)
            );
            
            $this->db->commit();
            return $requestId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ambil antrian approval untuk Supervisor
     */
    public function getPendingForSupervisor(int $departmentId): array
    {
        return $this->db->query(
            "SELECT ar.*, e.first_name, e.last_name, e.employee_code
             FROM attendance_requests ar
             JOIN employees e ON e.id = ar.employee_id
             WHERE ar.workflow_status = 'pending_supervisor'
               AND e.department_id = ?
             ORDER BY ar.created_at ASC",
            [$departmentId]
        )->fetchAll();
    }

    /**
     * Proses Approval
     */
    public function approve(int $requestId, int $approverId, string $role, string $notes = ''): bool
    {
        try {
            $this->db->beginTransaction();
            
            $request = $this->find($requestId);
            $nextStatus = $this->determineNextStatus($request['request_type'], $role, 'approve');
            
            // Catat approval
            $this->db->query(
                "INSERT INTO request_approvals (request_id, approver_id, role, decision, notes) 
                 VALUES (?, ?, ?, 'approve', ?)",
                [$requestId, $approverId, $role, $notes]
            );
            
            // Update status pengajuan
            $this->update($requestId, [
                'workflow_status' => $nextStatus,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Tentukan status berikutnya berdasarkan role dan jenis pengajuan
     */
    private function determineNextStatus(string $type, string $role, string $decision): string
    {
        if ($decision === 'reject') return 'rejected';

        if ($role === 'supervisor') {
            if ($type === 'sakit') return 'approved'; // Sakit langsung HRD biasanya, tapi di sini supervisor cuma monitor
            return 'pending_hrd';
        }

        if ($role === 'hrd_admin') {
            if ($type === 'tidak_finger') return 'pending_manager';
            return 'approved';
        }

        if ($role === 'hrd_manager') {
            return 'approved';
        }

        return 'approved';
    }
}

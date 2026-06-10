<?php
class Payroll extends Model
{
    protected string $table = 'payroll_details';

    /**
     * Ambil periode payroll yang sedang terbuka
     */
    public function getOpenPeriod(): ?array
    {
        return $this->db->query(
            "SELECT * FROM payroll_periods WHERE status = 'open' ORDER BY year DESC, month DESC LIMIT 1"
        )->fetch() ?: null;
    }

    /**
     * Ambil semua komponen gaji karyawan
     */
    public function getEmployeeComponents(int $empId): array
    {
        return $this->db->query(
            "SELECT esc.*, pc.code, pc.name, pc.type 
             FROM employee_salary_components esc
             JOIN payroll_components pc ON pc.id = esc.component_id
             WHERE esc.employee_id = ? AND (esc.end_date >= CURDATE() OR esc.end_date IS NULL)",
            [$empId]
        )->fetchAll();
    }

    /**
     * Simpan hasil perhitungan payroll (Run)
     */
    public function saveRun(array $runData, array $details): int
    {
        try {
            $this->db->beginTransaction();
            
            // 1. Catat Payroll Run
            $this->db->query(
                "INSERT INTO payroll_runs (period_id, run_by, notes) VALUES (?, ?, ?)",
                [$runData['period_id'], $runData['run_by'], $runData['notes']]
            );
            $runId = (int) $this->db->lastInsertId();

            // 2. Simpan Detail untuk setiap karyawan
            foreach ($details as $detail) {
                $detail['run_id'] = $runId;
                $cols = implode('`, `', array_keys($detail));
                $placeholders = implode(', ', array_fill(0, count($detail), '?'));
                
                $this->db->query(
                    "INSERT INTO payroll_details (`{$cols}`) VALUES ({$placeholders})
                     ON DUPLICATE KEY UPDATE net_pay = VALUES(net_pay), updated_at = NOW()",
                    array_values($detail)
                );
            }

            $this->db->commit();
            return $runId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

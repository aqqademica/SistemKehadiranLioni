<?php
class PayrollService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Hitung payroll untuk satu karyawan dalam satu periode
     */
    public function calculateEmployee(int $empId, array $period): array
    {
        // 1. Ambil Data Karyawan & Gaji Pokok
        $emp = $this->db->query(
            "SELECT id, base_salary, first_name, last_name FROM employees WHERE id = ?",
            [$empId]
        )->fetch();

        // 2. Ambil Rekap Kehadiran dalam Periode
        $attendance = $this->db->query(
            "SELECT 
                COUNT(CASE WHEN final_status = 'HADIR' THEN 1 END) as present_days,
                COUNT(CASE WHEN final_status LIKE 'UNPAID%' THEN 1 END) as unpaid_days,
                COUNT(CASE WHEN final_status = 'SAKIT' THEN 1 END) as sick_days,
                COUNT(CASE WHEN final_status = 'PAID_LEAVE' THEN 1 END) as leave_days,
                SUM(late_minutes) as total_late_minutes,
                SUM(overtime_hours) as total_ot_hours
             FROM daily_attendance_status 
             WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?",
            [$empId, $period['start_date'], $period['end_date']]
        )->fetch();

        // 3. Konstanta Perhitungan (Bisa diambil dari system_settings nantinya)
        $workDaysInMonth = 22; // Standar
        $workHoursInMonth = 173; // Standar Depnaker
        
        $dailyRate  = $emp['base_salary'] / $workDaysInMonth;
        $hourlyRate = $emp['base_salary'] / $workHoursInMonth;

        // 4. Hitung Potongan
        $lateDeduction   = ($attendance['total_late_minutes'] ?: 0) * ($hourlyRate / 60);
        $unpaidDeduction = ($attendance['unpaid_days'] ?: 0) * $dailyRate;
        
        // 5. Hitung Tunjangan & Bonus
        $allowances = $this->db->query(
            "SELECT SUM(amount) FROM employee_salary_components esc 
             JOIN payroll_components pc ON pc.id = esc.component_id 
             WHERE esc.employee_id = ? AND pc.type = 'earning'",
            [$empId]
        )->fetchColumn() ?: 0;

        $overtimePay = ($attendance['total_ot_hours'] ?: 0) * ($hourlyRate * 1.5); // Sederhana: 1.5x

        // 6. Final Calculation
        $grossPay = $emp['base_salary'] + $allowances + $overtimePay;
        $totalDeductions = $lateDeduction + $unpaidDeduction;
        $netPay = $grossPay - $totalDeductions;

        return [
            'employee_id'        => $empId,
            'base_salary'        => $emp['base_salary'],
            'present_days'       => $attendance['present_days'],
            'late_minutes_total' => $attendance['total_late_minutes'] ?: 0,
            'late_deduction'     => $lateDeduction,
            'unpaid_leave_days'  => $attendance['unpaid_days'] ?: 0,
            'unpaid_deduction'   => $unpaidDeduction,
            'overtime_hours'     => $attendance['total_ot_hours'] ?: 0,
            'overtime_pay'       => $overtimePay,
            'total_earnings'     => $grossPay,
            'total_deductions'   => $totalDeductions,
            'net_pay'            => $netPay,
            'notes'              => "Calculated for {$period['month']}/{$period['year']}"
        ];
    }

    /**
     * Jalankan Payroll untuk semua karyawan aktif
     */
    public function runAll(int $periodId, int $runBy): int
    {
        $period = $this->db->query("SELECT * FROM payroll_periods WHERE id = ?", [$periodId])->fetch();
        $employees = $this->db->query("SELECT id FROM employees WHERE employment_status = 'active'")->fetchAll();
        
        $details = [];
        foreach ($employees as $emp) {
            $details[] = $this->calculateEmployee($emp['id'], $period);
        }

        require_once APP_PATH . '/models/Payroll.php';
        $payrollModel = new Payroll();
        
        return $payrollModel->saveRun([
            'period_id' => $periodId,
            'run_by'    => $runBy,
            'notes'     => "Auto-run on " . date('Y-m-d H:i')
        ], $details);
    }
}

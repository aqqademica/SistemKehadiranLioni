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
     * [Fix 3.1-3.6] Complete payroll calculation overhaul
     */
    public function calculateEmployee(int $empId, array $period): array
    {
        // 1. Ambil Data Karyawan & Gaji Pokok
        $emp = $this->db->query(
            "SELECT id, base_salary, first_name, last_name FROM employees WHERE id = ?",
            [$empId]
        )->fetch();

        // 2. Ambil Rekap Kehadiran dalam Periode (split UNPAID types)
        $attendance = $this->db->query(
            "SELECT 
                COUNT(CASE WHEN final_status = 'HADIR' THEN 1 END) as present_days,
                COUNT(CASE WHEN final_status = 'UNPAID_TANPA' THEN 1 END) as alpha_days,
                COUNT(CASE WHEN final_status = 'UNPAID_DENGAN' THEN 1 END) as unpaid_excused_days,
                COUNT(CASE WHEN final_status = 'HOURLY_UNPAID' THEN 1 END) as hourly_unpaid_days,
                COUNT(CASE WHEN final_status = 'SAKIT' THEN 1 END) as sick_days,
                COUNT(CASE WHEN final_status = 'PAID_LEAVE' THEN 1 END) as leave_days,
                COUNT(CASE WHEN late_minutes > 0 THEN 1 END) as late_count,
                COALESCE(SUM(late_minutes), 0) as total_late_minutes,
                COALESCE(SUM(overtime_hours), 0) as total_ot_hours
             FROM daily_attendance_status 
             WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?",
            [$empId, $period['start_date'], $period['end_date']]
        )->fetch();

        // [Fix 3.2] Ambil total jam hourly unpaid leave yang disetujui
        $hourlyLeaveHours = $this->db->query(
            "SELECT COALESCE(SUM(hlr.hours_requested), 0)
             FROM hourly_leave_requests hlr
             JOIN attendance_requests ar ON ar.id = hlr.request_id
             WHERE ar.employee_id = ? 
               AND ar.attendance_date BETWEEN ? AND ?
               AND ar.workflow_status = 'approved'",
            [$empId, $period['start_date'], $period['end_date']]
        )->fetchColumn() ?: 0;

        // 3. [Fix 3.3] Hitung hari kerja aktual dalam periode (kurangi weekend + libur)
        $workDaysInMonth = $this->calculateWorkDays($period['start_date'], $period['end_date']);
        if ($workDaysInMonth < 1) $workDaysInMonth = 22; // Safety fallback

        // [Fix 3.4] Ambil konstanta dari system_settings
        $hourlyDivisor = $this->getSettingInt('hourly_rate_divisor', 173);
        $otMultiplierRegular = $this->getSettingDecimal('overtime_multiplier_regular', 1.5);

        $dailyRate  = $emp['base_salary'] / $workDaysInMonth;
        $hourlyRate = $emp['base_salary'] / $hourlyDivisor;
        $minuteRate = $hourlyRate / 60;

        // 4. [Fix 3.1] Hitung Potongan Terlambat dengan Bracket System
        $lateDeduction = $this->calculateLateDeduction(
            (int)($attendance['total_late_minutes'] ?: 0),
            (int)($attendance['late_count'] ?: 0),
            $hourlyRate
        );

        // [Fix 3.5] Pisahkan potongan UNPAID_TANPA (alpha) dan UNPAID_DENGAN
        $alphaDays = (float)($attendance['alpha_days'] ?: 0);
        $unpaidExcusedDays = (float)($attendance['unpaid_excused_days'] ?: 0);
        $totalUnpaidDays = $alphaDays + $unpaidExcusedDays;
        $unpaidDeduction = $totalUnpaidDays * $dailyRate;

        // [Fix 3.2] Potongan Hourly Unpaid Leave
        $hourlyDeduction = (float)$hourlyLeaveHours * $hourlyRate;

        // 5. Hitung Tunjangan & Bonus
        $allowances = $this->db->query(
            "SELECT COALESCE(SUM(esc.amount), 0) 
             FROM employee_salary_components esc 
             JOIN payroll_components pc ON pc.id = esc.component_id 
             WHERE esc.employee_id = ? AND pc.type = 'earning'
               AND (esc.end_date >= CURDATE() OR esc.end_date IS NULL)",
            [$empId]
        )->fetchColumn() ?: 0;

        // [Fix 3.4 & Overtime Update] Progressive Overtime with Holiday/Rest Day detection
        $ot15xThreshold = $this->getSettingInt('overtime_15x_threshold', 1);
        $workWeekType = $this->getSettingInt('work_week_type', 5);

        $dailyLogs = $this->db->query(
            "SELECT attendance_date, overtime_hours 
             FROM daily_attendance_status 
             WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? AND overtime_hours > 0",
            [$empId, $period['start_date'], $period['end_date']]
        )->fetchAll();

        $overtimePay = 0;
        foreach ($dailyLogs as $log) {
            $otHours = (float)$log['overtime_hours'];
            if ($otHours <= 0) continue;

            $date = $log['attendance_date'];
            $isHoliday = $this->db->query("SELECT id FROM national_holidays WHERE date = ?", [$date])->fetch();

            $dayOfWeek = (int)date('N', strtotime($date));
            $isRestDay = false;
            if ($workWeekType == 5 && $dayOfWeek >= 6) {
                $isRestDay = true;
            } elseif ($workWeekType == 6 && $dayOfWeek == 7) {
                $isRestDay = true;
            }

            if ($isHoliday || $isRestDay) {
                // Libur/Istirahat formula
                if ($otHours <= 8) {
                    $pay = $otHours * ($hourlyRate * 2.0);
                } elseif ($otHours <= 9) {
                    $pay = (8 * ($hourlyRate * 2.0)) + (($otHours - 8) * ($hourlyRate * 3.0));
                } else {
                    $pay = (8 * ($hourlyRate * 2.0)) + (1 * ($hourlyRate * 3.0)) + (($otHours - 9) * ($hourlyRate * 4.0));
                }
                $overtimePay += $pay;
            } else {
                // Hari kerja normal formula
                if ($otHours <= $ot15xThreshold) {
                    $overtimePay += $otHours * ($hourlyRate * 1.5);
                } else {
                    $overtimePay += ($ot15xThreshold * ($hourlyRate * 1.5)) + (($otHours - $ot15xThreshold) * ($hourlyRate * 2.0));
                }
            }
        }

        // Bonus jabatan (dari position_bonuses jika ada)
        $bonusAmount = $this->db->query(
            "SELECT COALESCE(SUM(pb.amount), 0) 
             FROM position_bonuses pb 
             WHERE pb.period_id = ? AND pb.position_id = (
                SELECT position_id FROM employees WHERE id = ?
             )",
            [$period['id'], $empId]
        )->fetchColumn() ?: 0;

        // 6. Final Calculation
        $grossPay = $emp['base_salary'] + (float)$allowances + $overtimePay + (float)$bonusAmount;
        $totalDeductions = $lateDeduction + $unpaidDeduction + $hourlyDeduction;
        $netPay = $grossPay - $totalDeductions;

        return [
            'employee_id'        => $empId,
            'base_salary'        => $emp['base_salary'],
            'work_days'          => $workDaysInMonth,
            'present_days'       => (int)($attendance['present_days'] ?: 0),
            'late_count'         => (int)($attendance['late_count'] ?: 0),
            'late_minutes_total' => (int)($attendance['total_late_minutes'] ?: 0),
            'late_deduction'     => round($lateDeduction, 2),
            'unpaid_leave_days'  => $totalUnpaidDays,
            'unpaid_deduction'   => round($unpaidDeduction, 2),
            'hourly_leave_hours' => (float)$hourlyLeaveHours,
            'hourly_deduction'   => round($hourlyDeduction, 2),
            'overtime_hours'     => (float)($attendance['total_ot_hours'] ?: 0),
            'overtime_pay'       => round($overtimePay, 2),
            'bonus_amount'       => round((float)$bonusAmount, 2),
            'total_earnings'     => round($grossPay, 2),
            'total_deductions'   => round($totalDeductions, 2),
            'gross_pay'          => round($grossPay, 2),
            'net_pay'            => round($netPay, 2),
            'notes'              => "Period {$period['month']}/{$period['year']} | WorkDays={$workDaysInMonth} Alpha={$alphaDays} UnpaidExcused={$unpaidExcusedDays}"
        ];
    }

    /**
     * [Fix 3.1] Hitung potongan terlambat menggunakan bracket system dari late_deduction_rules
     */
    private function calculateLateDeduction(int $totalLateMinutes, int $lateCount, float $hourlyRate): float
    {
        if ($totalLateMinutes <= 0) return 0;

        // Ambil aturan bracket
        $rules = $this->db->query(
            "SELECT min_minutes, max_minutes, deduction_percent, deduction_amount 
             FROM late_deduction_rules 
             WHERE is_active = 1 
             ORDER BY min_minutes ASC"
        )->fetchAll();

        // Jika tidak ada rules, fallback ke per-menit
        if (empty($rules)) {
            return $totalLateMinutes * ($hourlyRate / 60);
        }

        // Hitung berdasarkan rata-rata menit per kejadian terlambat
        $avgLateMinutes = $lateCount > 0 ? (int)ceil($totalLateMinutes / $lateCount) : $totalLateMinutes;
        $deduction = 0;

        // Untuk setiap kejadian terlambat, tentukan bracket-nya
        // Pendekatan: gunakan total menit dan cocokkan ke bracket tertinggi yang berlaku
        foreach ($rules as $rule) {
            $min = (int)$rule['min_minutes'];
            $max = (int)$rule['max_minutes'];

            if ($avgLateMinutes >= $min && ($max == 0 || $avgLateMinutes <= $max)) {
                // Gunakan deduction_amount jika ada, otherwise use percent
                if ((float)$rule['deduction_amount'] > 0) {
                    $deduction = (float)$rule['deduction_amount'] * $lateCount;
                } else {
                    $deduction = ($hourlyRate * ((float)$rule['deduction_percent'] / 100)) * $lateCount;
                }
                break; // Ambil bracket pertama yang cocok
            }
        }

        // Jika tidak ada bracket yang cocok (menit > bracket tertinggi), gunakan bracket terakhir
        if ($deduction == 0 && !empty($rules)) {
            $lastRule = end($rules);
            if ((float)$lastRule['deduction_amount'] > 0) {
                $deduction = (float)$lastRule['deduction_amount'] * $lateCount;
            } else {
                $deduction = ($hourlyRate * ((float)$lastRule['deduction_percent'] / 100)) * $lateCount;
            }
        }

        return $deduction;
    }

    /**
     * [Fix 3.3] Hitung hari kerja aktual (exclude weekend + libur nasional)
     */
    private function calculateWorkDays(string $startDate, string $endDate): int
    {
        $holidays = $this->db->query(
            "SELECT date FROM national_holidays WHERE date BETWEEN ? AND ?",
            [$startDate, $endDate]
        )->fetchAll(PDO::FETCH_COLUMN, 0);

        $holidaySet = array_flip($holidays ?: []);
        $workDays = 0;
        $current = strtotime($startDate);
        $end = strtotime($endDate);

        while ($current <= $end) {
            $dayOfWeek = (int)date('N', $current); // 1=Mon, 7=Sun
            $dateStr = date('Y-m-d', $current);
            if ($dayOfWeek <= 5 && !isset($holidaySet[$dateStr])) {
                $workDays++;
            }
            $current = strtotime('+1 day', $current);
        }

        return $workDays;
    }

    /**
     * Jalankan Payroll untuk semua karyawan aktif
     */
    public function runAll(int $periodId, int $runBy): int
    {
        $period = $this->db->query("SELECT * FROM payroll_periods WHERE id = ?", [$periodId])->fetch();
        
        // Pastikan periode belum closed [Fix 7.7]
        if ($period['status'] === 'closed') {
            throw new Exception("Periode payroll sudah ditutup, tidak bisa menjalankan kalkulasi ulang.");
        }
        
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

    // Helpers to read system_settings
    private function getSettingInt(string $key, int $default): int
    {
        $row = $this->db->query("SELECT `value` FROM system_settings WHERE `key` = ? LIMIT 1", [$key])->fetch();
        return $row ? (int) $row['value'] : $default;
    }

    private function getSettingDecimal(string $key, float $default): float
    {
        $row = $this->db->query("SELECT `value` FROM system_settings WHERE `key` = ? LIMIT 1", [$key])->fetch();
        return $row ? (float) $row['value'] : $default;
    }
}

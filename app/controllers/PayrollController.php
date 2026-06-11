<?php
class PayrollController extends Controller
{
    private Payroll $payrollModel;

    public function __construct()
    {
        parent::__construct();
        require_once APP_PATH . '/models/Payroll.php';
        $this->payrollModel = new Payroll();
    }

    /**
     * Halaman Utama Payroll
     */
    public function index(): void
    {
        $this->requireRole(['payroll_officer', 'hrd_manager']);
        
        $periods = $this->db->query("SELECT * FROM payroll_periods ORDER BY year DESC, month DESC")->fetchAll();
        $openPeriod = $this->payrollModel->getOpenPeriod();

        $this->render('payroll.index', [
            'pageTitle'  => 'Manajemen Payroll',
            'activePage' => '/KehadiranApp/public/payroll',
            'periods'    => $periods,
            'openPeriod' => $openPeriod,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Jalankan Kalkulasi Payroll
     */
    public function run(): void
    {
        $this->requireRole(['payroll_officer']);
        $this->verifyCsrf();

        $periodId = $this->inputInt('period_id');
        
        require_once APP_PATH . '/services/PayrollService.php';
        $service = new PayrollService();
        
        try {
            $runId = $service->runAll($periodId, $_SESSION['user_id']);
            $this->flash('success', 'Kalkulasi payroll berhasil dijalankan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal menjalankan payroll: ' . $e->getMessage());
        }

        $this->redirect('payroll');
    }

    /**
     * Detail Payroll / Slip Gaji
     */
    public function detail(): void
    {
        $this->requireLogin();
        $detailId = $this->inputInt('id');
        
        $detail = $this->db->query(
            "SELECT pd.*, e.first_name, e.last_name, e.employee_code, p.name as position_name, d.name as dept_name,
                    pp.month, pp.year
             FROM payroll_details pd
             JOIN employees e ON e.id = pd.employee_id
             JOIN positions p ON p.id = e.position_id
             JOIN departments d ON d.id = e.department_id
             JOIN payroll_runs pr ON pr.id = pd.run_id
             JOIN payroll_periods pp ON pp.id = pr.period_id
             WHERE pd.id = ?",
            [$detailId]
        )->fetch();

        // Karyawan hanya boleh lihat slip miliknya sendiri
        if ($_SESSION['role'] === 'employee' && $detail['employee_id'] != $_SESSION['employee_id']) {
            $this->redirect('dashboard');
        }

        $this->render('payroll.detail', [
            'pageTitle'  => 'Slip Gaji',
            'activePage' => '/KehadiranApp/public/payroll',
            'd'          => $detail
        ]);
    }

    /**
     * Slip Gaji Saya (Employee View)
     */
    public function mySalary(): void
    {
        $this->requireLogin();
        $empId = $_SESSION['employee_id'];
        
        $history = $this->db->query(
            "SELECT pd.*, pp.month, pp.year
             FROM payroll_details pd
             JOIN payroll_runs pr ON pr.id = pd.run_id
             JOIN payroll_periods pp ON pp.id = pr.period_id
             WHERE pd.employee_id = ?
             ORDER BY pp.year DESC, pp.month DESC",
            [$empId]
        )->fetchAll();

        // Ambil Open Period (Periode yang belum ditutup)
        $openPeriod = $this->payrollModel->getOpenPeriod();
        $runningOvertimeHours = 0;
        
        if ($openPeriod) {
            $runningOvertimeHours = $this->db->query(
                "SELECT COALESCE(SUM(overtime_hours), 0) 
                 FROM daily_attendance_status 
                 WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?",
                [$empId, $openPeriod['start_date'], $openPeriod['end_date']]
            )->fetchColumn();
        }

        $this->render('payroll.my_history', [
            'pageTitle'  => 'Riwayat Gaji & Lembur',
            'activePage' => '/KehadiranApp/public/my-salary',
            'history'    => $history,
            'openPeriod' => $openPeriod,
            'runningOvertimeHours' => $runningOvertimeHours
        ]);
    }

    /**
     * [Fix 7.7] Tutup/kunci periode payroll
     */
    public function closePeriod(): void
    {
        $this->requireRole(['hrd_manager']);
        $this->verifyCsrf();

        $periodId = $this->inputInt('period_id');

        try {
            $period = $this->db->query("SELECT * FROM payroll_periods WHERE id = ?", [$periodId])->fetch();
            if (!$period) throw new Exception("Periode tidak ditemukan.");
            if ($period['status'] === 'closed') throw new Exception("Periode sudah ditutup.");

            // Verify there is at least one payroll run
            $runs = $this->db->query("SELECT COUNT(*) FROM payroll_runs WHERE period_id = ?", [$periodId])->fetchColumn();
            if ($runs == 0) throw new Exception("Tidak ada kalkulasi payroll untuk periode ini. Jalankan payroll terlebih dahulu.");

            $this->db->query(
                "UPDATE payroll_periods SET status = 'closed', closed_by = ?, closed_at = NOW() WHERE id = ?",
                [$_SESSION['user_id'], $periodId]
            );

            $this->flash('success', "Periode payroll {$period['month']}/{$period['year']} berhasil ditutup.");
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal menutup periode: ' . $e->getMessage());
        }

        $this->redirect('payroll');
    }
}

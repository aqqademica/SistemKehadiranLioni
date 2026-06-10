<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $role = $_SESSION['role'] ?? '';

        match($role) {
            'employee'        => $this->employeeDashboard(),
            'supervisor'      => $this->supervisorDashboard(),
            'hrd_admin'       => $this->hrdAdminDashboard(),
            'hrd_manager'     => $this->hrdManagerDashboard(),
            'payroll_officer' => $this->payrollDashboard(),
            default           => $this->redirect('login'),
        };
    }

    private function employeeDashboard(): void
    {
        $empId = $_SESSION['employee_id'];
        $today = date('Y-m-d');
        $month = date('Y-m');

        // Kehadiran hari ini
        $todayStatus = $this->db->query(
            "SELECT * FROM daily_attendance_status WHERE employee_id = ? AND attendance_date = ?",
            [$empId, $today]
        )->fetch();

        // Ringkasan bulan ini
        $summary = $this->db->query(
            "SELECT
               SUM(final_status = 'HADIR') AS hadir,
               SUM(final_status IN ('UNPAID_TANPA','UNPAID_DENGAN')) AS unpaid,
               SUM(final_status = 'PAID_LEAVE') AS cuti,
               SUM(final_status = 'SAKIT') AS sakit,
               SUM(final_status = 'HOURLY_UNPAID') AS hourly
             FROM daily_attendance_status
             WHERE employee_id = ? AND DATE_FORMAT(attendance_date,'%Y-%m') = ?",
            [$empId, $month]
        )->fetch();

        // Saldo cuti
        $leaveBalance = $this->db->query(
            "SELECT * FROM employee_leave_balances WHERE employee_id = ? AND year = ?",
            [$empId, date('Y')]
        )->fetch();

        // Pengajuan aktif
        $activeRequests = $this->db->query(
            "SELECT * FROM attendance_requests
             WHERE employee_id = ? AND workflow_status NOT IN ('approved','rejected','auto_converted')
             ORDER BY created_at DESC LIMIT 5",
            [$empId]
        )->fetchAll();

        // Notifikasi terbaru
        $notifications = $this->db->query(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
            [$_SESSION['user_id']]
        )->fetchAll();

        $this->render('dashboard.employee', [
            'pageTitle'      => 'Dashboard',
            'activePage'     => '/KehadiranApp/public/dashboard',
            'todayStatus'    => $todayStatus,
            'summary'        => $summary,
            'leaveBalance'   => $leaveBalance,
            'activeRequests' => $activeRequests,
            'notifications'  => $notifications,
            'csrf_token'     => $this->generateCsrf()
        ]);
    }

    private function supervisorDashboard(): void
    {
        $userId = $_SESSION['user_id'];
        $today  = date('Y-m-d');

        // Karyawan belum absen hari ini (di department yang sama)
        $empId = $_SESSION['employee_id'];
        $deptId = $this->db->query(
            "SELECT department_id FROM employees WHERE id = ?", [$empId]
        )->fetchColumn();

        $notPresent = $this->db->query(
            "SELECT e.employee_code, e.first_name, e.last_name
             FROM employees e
             LEFT JOIN daily_attendance_status d ON d.employee_id = e.id AND d.attendance_date = ?
             WHERE e.department_id = ? AND e.employment_status = 'active'
               AND (d.id IS NULL OR d.final_status = 'NO_LOG')
             ORDER BY e.first_name",
            [$today, $deptId]
        )->fetchAll();

        // Antrian approval
        $pendingApprovals = $this->db->query(
            "SELECT ar.*, e.first_name, e.last_name, e.employee_code
             FROM attendance_requests ar
             JOIN employees e ON e.id = ar.employee_id
             WHERE (ar.workflow_status = 'pending_supervisor' 
                    OR (ar.request_type = 'sakit' AND ar.workflow_status IN ('pending_hrd', 'pending_manager')))
               AND e.department_id = ?
             ORDER BY ar.created_at ASC LIMIT 10",
            [$deptId]
        )->fetchAll();

        // Summary tim bulan ini
        $teamSummary = $this->db->query(
            "SELECT
               COUNT(DISTINCT e.id) AS total_emp,
               SUM(d.final_status = 'HADIR') AS hadir,
               SUM(d.final_status IN ('UNPAID_TANPA','UNPAID_DENGAN')) AS unpaid,
               SUM(d.final_status = 'SAKIT') AS sakit
             FROM employees e
             LEFT JOIN daily_attendance_status d ON d.employee_id = e.id
               AND DATE_FORMAT(d.attendance_date,'%Y-%m') = ?
             WHERE e.department_id = ? AND e.employment_status = 'active'",
            [date('Y-m'), $deptId]
        )->fetch();

        $this->render('dashboard.supervisor', [
            'pageTitle'        => 'Dashboard Supervisor',
            'activePage'       => '/KehadiranApp/public/dashboard',
            'notPresent'       => $notPresent,
            'pendingApprovals' => $pendingApprovals,
            'teamSummary'      => $teamSummary,
        ]);
    }

    private function hrdAdminDashboard(): void
    {
        $today = date('Y-m-d');
        $month = date('Y-m');

        $stats = $this->db->query(
            "SELECT
               (SELECT COUNT(*) FROM employees WHERE employment_status='active') AS total_emp,
               (SELECT COUNT(*) FROM daily_attendance_status WHERE attendance_date=? AND final_status='HADIR') AS hadir_hari_ini,
               (SELECT COUNT(*) FROM attendance_requests WHERE workflow_status='pending_hrd') AS pending_verif,
               (SELECT COUNT(*) FROM sick_requests sr JOIN attendance_requests ar ON ar.id=sr.request_id WHERE ar.workflow_status='pending_hrd') AS pending_sakit",
            [$today]
        )->fetch();

        $recentRequests = $this->db->query(
            "SELECT ar.*, e.first_name, e.last_name, e.employee_code
             FROM attendance_requests ar
             JOIN employees e ON e.id = ar.employee_id
             WHERE ar.workflow_status IN ('pending_hrd','submitted')
             ORDER BY ar.created_at DESC LIMIT 10"
        )->fetchAll();

        $this->render('dashboard.hrd_admin', [
            'pageTitle'      => 'Dashboard Admin HRD',
            'activePage'     => '/KehadiranApp/public/dashboard',
            'stats'          => $stats,
            'recentRequests' => $recentRequests,
        ]);
    }

    private function hrdManagerDashboard(): void
    {
        $stats = $this->db->query(
            "SELECT
               (SELECT COUNT(*) FROM attendance_requests WHERE workflow_status='pending_manager') AS pending_final,
               (SELECT COUNT(*) FROM warning_letters WHERE MONTH(issued_at)=MONTH(NOW()) AND YEAR(issued_at)=YEAR(NOW())) AS wl_bulan_ini,
               (SELECT COUNT(*) FROM employees WHERE employment_status='active') AS total_emp,
               (SELECT COUNT(*) FROM warning_letters WHERE wl_type='TERMINATION' AND YEAR(issued_at)=YEAR(NOW())) AS termination_tahun_ini"
        )->fetch();

        $pendingFinal = $this->db->query(
            "SELECT ar.*, e.first_name, e.last_name, e.employee_code
             FROM attendance_requests ar
             JOIN employees e ON e.id = ar.employee_id
             WHERE ar.workflow_status = 'pending_manager'
             ORDER BY ar.created_at ASC LIMIT 10"
        )->fetchAll();

        $recentWL = $this->db->query(
            "SELECT wl.*, e.first_name, e.last_name, e.employee_code
             FROM warning_letters wl
             JOIN employees e ON e.id = wl.employee_id
             ORDER BY wl.issued_at DESC LIMIT 5"
        )->fetchAll();

        $this->render('dashboard.hrd_manager', [
            'pageTitle'    => 'Dashboard Manager HRD',
            'activePage'   => '/KehadiranApp/public/dashboard',
            'stats'        => $stats,
            'pendingFinal' => $pendingFinal,
            'recentWL'     => $recentWL,
        ]);
    }

    private function payrollDashboard(): void
    {
        $currentPeriod = $this->db->query(
            "SELECT * FROM payroll_periods WHERE status='open' ORDER BY year DESC, month DESC LIMIT 1"
        )->fetch();

        $stats = $this->db->query(
            "SELECT
               (SELECT COUNT(*) FROM employees WHERE employment_status='active') AS total_emp,
               (SELECT COUNT(*) FROM payroll_periods WHERE status='open') AS open_periods,
               (SELECT COUNT(*) FROM payroll_periods WHERE status='closed' AND year=YEAR(NOW())) AS closed_this_year,
               (SELECT COALESCE(SUM(net_pay),0) FROM payroll_details pd JOIN payroll_runs pr ON pr.id=pd.run_id JOIN payroll_periods pp ON pp.id=pr.period_id WHERE pp.month=MONTH(NOW()) AND pp.year=YEAR(NOW())) AS total_net_bulan_ini"
        )->fetch();

        $this->render('dashboard.payroll_officer', [
            'pageTitle'     => 'Dashboard Payroll',
            'activePage'    => '/KehadiranApp/public/dashboard',
            'currentPeriod' => $currentPeriod,
            'stats'         => $stats,
        ]);
    }
}

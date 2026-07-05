<?php
class AdminController extends Controller
{
    /**
     * Jalankan Sinkronisasi Kehadiran Harian
     */
    public function syncAttendance(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf(); // [Fix 5.3] Added CSRF since route is now POST
        
        require_once APP_PATH . '/services/AttendanceService.php';
        require_once APP_PATH . '/services/DisciplineService.php';
        
        $service = new AttendanceService();
        $discipline = new DisciplineService();
        
        $date = $this->input('date', date('Y-m-d'));
        
        $results = $service->processDaily($date);
        
        // Cek WL untuk semua yang diproses [Fix 4.4: pass actual user_id]
        $employees = $this->db->query("SELECT id FROM employees WHERE employment_status = 'active'")->fetchAll();
        $wlIssued = 0;
        $issuerId = (int) ($_SESSION['user_id'] ?? 0);
        foreach ($employees as $emp) {
            $res = $discipline->checkAndIssueWL($emp['id'], $issuerId);
            if ($res && strpos($res, 'Issued') !== false) $wlIssued++;
        }

        $this->flash('success', "Sinkronisasi selesai. {$results['processed']} karyawan diproses. {$wlIssued} Warning Letter diterbitkan.");
        $this->redirect('dashboard');
    }

    /**
     * Daftar Semua Karyawan (Admin View)
     */
    public function employees(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager', 'payroll_officer']);
        
        $employees = $this->db->query(
            "SELECT e.*, d.name as dept_name, p.name as pos_name 
             FROM employees e
             JOIN departments d ON d.id = e.department_id
             JOIN positions p ON p.id = e.position_id
             ORDER BY e.employee_code ASC"
        )->fetchAll();

        $this->render('admin.employees', [
            'pageTitle'  => 'Data Karyawan',
            'activePage' => '/KehadiranApp/public/admin/employees',
            'employees'  => $employees
        ]);
    }

    /**
     * Daftar Anggota Tim (Supervisor View)
     */
    public function myTeam(): void
    {
        $this->requireRole(['supervisor']);
        $empId = $_SESSION['employee_id'] ?? 0;
        
        $deptId = $this->db->query("SELECT department_id FROM employees WHERE id = ?", [$empId])->fetchColumn();
        
        $employees = $this->db->query(
            "SELECT e.*, d.name as dept_name, p.name as pos_name 
             FROM employees e
             JOIN departments d ON d.id = e.department_id
             JOIN positions p ON p.id = e.position_id
             WHERE e.department_id = ? AND e.employment_status = 'active'
             ORDER BY e.employee_code ASC",
            [$deptId]
        )->fetchAll();

        $this->render('admin.employees', [
            'pageTitle'          => 'Anggota Tim Saya',
            'activePage'         => '/KehadiranApp/public/hrd/my-team',
            'employees'          => $employees,
            'isSupervisorView'   => true
        ]);
    }

    /**
     * Rekap Kehadiran Seluruh Karyawan (Admin View)
     */
    public function attendance(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager', 'supervisor']);
        
        $date = $this->input('date', date('Y-m-d'));
        $role = $_SESSION['role'];
        $empId = $_SESSION['employee_id'] ?? 0;
        
        if ($role === 'supervisor') {
            $deptId = $this->db->query("SELECT department_id FROM employees WHERE id = ?", [$empId])->fetchColumn();
            
            $logs = $this->db->query(
                "SELECT d.*, e.first_name, e.last_name, e.employee_code 
                 FROM daily_attendance_status d
                 JOIN employees e ON e.id = d.employee_id
                 WHERE d.attendance_date = ? AND e.department_id = ?
                 ORDER BY e.first_name ASC",
                [$date, $deptId]
            )->fetchAll();
        } else {
            $logs = $this->db->query(
                "SELECT d.*, e.first_name, e.last_name, e.employee_code 
                 FROM daily_attendance_status d
                 JOIN employees e ON e.id = d.employee_id
                 WHERE d.attendance_date = ?
                 ORDER BY e.first_name ASC",
                [$date]
            )->fetchAll();
        }

        $this->render('admin.attendance', [
            'pageTitle'  => 'Rekap Kehadiran (' . date('d M Y', strtotime($date)) . ')',
            'activePage' => '/KehadiranApp/public/hrd/attendance',
            'logs'       => $logs,
            'date'       => $date
        ]);
    }

    /**
     * Mitra Kesehatan (HRD View)
     */
    public function healthPartners(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $partners = $this->db->query("SELECT * FROM health_partners ORDER BY name ASC")->fetchAll();
        $this->render('admin.health_partners', [
            'pageTitle'  => 'Mitra Kesehatan',
            'activePage' => '/KehadiranApp/public/hrd/health-partners',
            'partners'   => $partners,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    public function storeHealthPartner(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();

        $name = $this->input('name');
        $type = $this->input('type');
        $address = $this->input('address');
        $phone = $this->input('phone');
        $is_active = $this->inputInt('is_active', 1);

        try {
            $this->db->query(
                "INSERT INTO health_partners (name, type, address, phone, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?)",
                [$name, $type, $address, $phone, $is_active, $_SESSION['user_id']]
            );
            $this->flash('success', 'Mitra Kesehatan berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal menambah mitra: ' . $e->getMessage());
        }
        $this->redirect('hrd/health-partners');
    }

    public function updateHealthPartner(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();

        $id = $this->inputInt('id');
        $name = $this->input('name');
        $type = $this->input('type');
        $address = $this->input('address');
        $phone = $this->input('phone');
        $is_active = $this->inputInt('is_active');

        try {
            $this->db->query(
                "UPDATE health_partners SET name = ?, type = ?, address = ?, phone = ?, is_active = ? WHERE id = ?",
                [$name, $type, $address, $phone, $is_active, $id]
            );
            $this->flash('success', 'Data Mitra Kesehatan berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal memperbarui mitra: ' . $e->getMessage());
        }
        $this->redirect('hrd/health-partners');
    }

    public function deleteHealthPartner(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();

        $id = $this->inputInt('id');

        try {
            $this->db->query("DELETE FROM health_partners WHERE id = ?", [$id]);
            $this->flash('success', 'Mitra Kesehatan berhasil dihapus.');
        } catch (Exception $e) {
            // Soft delete if FK constraint fails
            $this->db->query("UPDATE health_partners SET is_active = 0 WHERE id = ?", [$id]);
            $this->flash('warning', 'Mitra Kesehatan memiliki riwayat penggunaan, sehingga hanya dinonaktifkan.');
        }
        $this->redirect('hrd/health-partners');
    }

    /**
     * Pengaturan HRD (HRD View) — Dashboard Konfigurasi
     */
    public function settings(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);

        $tab = $this->input('tab', '');

        $shifts     = $this->db->query("SELECT * FROM shifts ORDER BY name ASC")->fetchAll();
        $positions  = $this->db->query(
            "SELECT p.*, d.name as dept_name FROM positions p LEFT JOIN departments d ON d.id = p.department_id ORDER BY p.name ASC"
        )->fetchAll();
        $lateRules  = $this->db->query("SELECT * FROM late_deduction_rules ORDER BY min_minutes ASC")->fetchAll();
        $leaveTypes = $this->db->query("SELECT * FROM leave_types ORDER BY name ASC")->fetchAll();
        $departments = $this->db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

        $this->render('admin.settings', [
            'pageTitle'   => 'Pengaturan HRD',
            'activePage'  => '/KehadiranApp/public/hrd/settings',
            'tab'         => $tab,
            'shifts'      => $shifts,
            'positions'   => $positions,
            'lateRules'   => $lateRules,
            'leaveTypes'  => $leaveTypes,
            'departments' => $departments,
            'csrf_token'  => $this->generateCsrf()
        ]);
    }

    // --- SHIFTS CRUD ---
    public function storeShift(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "INSERT INTO shifts (name, start_time, end_time, work_hours, is_overnight) VALUES (?, ?, ?, ?, ?)",
                [$this->input('name'), $this->input('start_time'), $this->input('end_time'), $this->input('work_hours', 8), $this->inputInt('is_overnight')]
            );
            $this->flash('success', 'Jam Kerja berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/settings?tab=shifts');
    }
    public function updateShift(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "UPDATE shifts SET name=?, start_time=?, end_time=?, work_hours=?, is_overnight=? WHERE id=?",
                [$this->input('name'), $this->input('start_time'), $this->input('end_time'), $this->input('work_hours', 8), $this->inputInt('is_overnight'), $this->inputInt('id')]
            );
            $this->flash('success', 'Jam Kerja berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/settings?tab=shifts');
    }
    public function deleteShift(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query("DELETE FROM shifts WHERE id = ?", [$this->inputInt('id')]);
            $this->flash('success', 'Jam Kerja berhasil dihapus.');
        } catch (Exception $e) {
            $this->db->query("UPDATE shifts SET is_active = 0 WHERE id = ?", [$this->inputInt('id')]);
            $this->flash('warning', 'Jam Kerja memiliki relasi, sehingga hanya dinonaktifkan.');
        }
        $this->redirect('hrd/settings?tab=shifts');
    }

    // --- POSITIONS CRUD ---
    public function storePosition(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $this->input('name')), 0, 6)) . rand(10,99);
            $this->db->query(
                "INSERT INTO positions (name, code, department_id, level) VALUES (?, ?, ?, ?)",
                [$this->input('name'), $code, $this->inputInt('department_id'), $this->input('level', 'staff')]
            );
            $this->flash('success', 'Jabatan berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/settings?tab=positions');
    }
    public function updatePosition(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "UPDATE positions SET name=?, department_id=?, level=? WHERE id=?",
                [$this->input('name'), $this->inputInt('department_id'), $this->input('level', 'staff'), $this->inputInt('id')]
            );
            $this->flash('success', 'Jabatan berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/settings?tab=positions');
    }
    public function deletePosition(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query("DELETE FROM positions WHERE id = ?", [$this->inputInt('id')]);
            $this->flash('success', 'Jabatan berhasil dihapus.');
        } catch (Exception $e) {
            $this->db->query("UPDATE positions SET is_active = 0 WHERE id = ?", [$this->inputInt('id')]);
            $this->flash('warning', 'Jabatan memiliki relasi, sehingga hanya dinonaktifkan.');
        }
        $this->redirect('hrd/settings?tab=positions');
    }

    // --- LATE RULES CRUD ---
    public function storeLateRule(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "INSERT INTO late_deduction_rules (min_minutes, max_minutes, deduction_percent, deduction_amount, description) VALUES (?, ?, ?, ?, ?)",
                [$this->inputInt('min_minutes'), $this->inputInt('max_minutes'), $this->input('deduction_percent', 0), $this->input('deduction_amount', 0), $this->input('description')]
            );
            $this->flash('success', 'Aturan Terlambat berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/settings?tab=late');
    }
    public function updateLateRule(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "UPDATE late_deduction_rules SET min_minutes=?, max_minutes=?, deduction_percent=?, deduction_amount=?, description=? WHERE id=?",
                [$this->inputInt('min_minutes'), $this->inputInt('max_minutes'), $this->input('deduction_percent', 0), $this->input('deduction_amount', 0), $this->input('description'), $this->inputInt('id')]
            );
            $this->flash('success', 'Aturan Terlambat berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/settings?tab=late');
    }
    public function deleteLateRule(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query("DELETE FROM late_deduction_rules WHERE id = ?", [$this->inputInt('id')]);
            $this->flash('success', 'Aturan Terlambat berhasil dihapus.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/settings?tab=late');
    }

    // --- LEAVE TYPES CRUD ---
    public function storeLeaveType(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "INSERT INTO leave_types (name, max_days, description) VALUES (?, ?, ?)",
                [$this->input('name'), $this->inputInt('max_days'), $this->input('description')]
            );
            $this->flash('success', 'Jenis Cuti berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/settings?tab=leave');
    }
    public function updateLeaveType(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "UPDATE leave_types SET name=?, max_days=?, description=? WHERE id=?",
                [$this->input('name'), $this->inputInt('max_days'), $this->input('description'), $this->inputInt('id')]
            );
            $this->flash('success', 'Jenis Cuti berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/settings?tab=leave');
    }
    public function deleteLeaveType(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query("DELETE FROM leave_types WHERE id = ?", [$this->inputInt('id')]);
            $this->flash('success', 'Jenis Cuti berhasil dihapus.');
        } catch (Exception $e) {
            $this->db->query("UPDATE leave_types SET is_active = 0 WHERE id = ?", [$this->inputInt('id')]);
            $this->flash('warning', 'Jenis Cuti memiliki relasi, sehingga hanya dinonaktifkan.');
        }
        $this->redirect('hrd/settings?tab=leave');
    }

    /**
     * Kelola Akun (HRD View)
     */
    public function accounts(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $users = $this->db->query(
            "SELECT u.*, e.first_name, e.last_name, e.employee_code, r.name as role_name
             FROM users u
             LEFT JOIN employees e ON e.id = u.employee_id
             JOIN roles r ON r.id = u.role_id
             ORDER BY r.name ASC, u.username ASC"
        )->fetchAll();

        $roles = $this->db->query("SELECT * FROM roles ORDER BY name ASC")->fetchAll();

        $this->render('admin.accounts', [
            'pageTitle'  => 'Kelola Akun',
            'activePage' => '/KehadiranApp/public/hrd/accounts',
            'users'      => $users,
            'roles'      => $roles,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    // ==========================================
    // EMPLOYEE MANAGEMENT
    // ==========================================

    public function createEmployee(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $departments = $this->db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
        $positions   = $this->db->query("SELECT * FROM positions ORDER BY name ASC")->fetchAll();

        // Auto-generate next employee code (format: EMP0001, EMP0002, ...)
        $lastCode = $this->db->query(
            "SELECT employee_code FROM employees ORDER BY employee_code DESC LIMIT 1"
        )->fetchColumn();

        $nextNum  = 1;
        if ($lastCode && preg_match('/\d+$/', $lastCode, $m)) {
            $nextNum = ((int)$m[0]) + 1;
        }
        $suggestedCode = 'EMP' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $this->render('admin.form_employee', [
            'pageTitle'     => 'Tambah Karyawan',
            'activePage'    => '/KehadiranApp/public/admin/employees',
            'departments'   => $departments,
            'positions'     => $positions,
            'employee'      => null,
            'suggestedCode' => $suggestedCode,
            'csrf_token'    => $this->generateCsrf()
        ]);
    }

    public function storeEmployee(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();

        // Implement logic to store employee
        $data = [
            'employee_code'     => $this->input('employee_code'),
            'first_name'        => $this->input('first_name'),
            'last_name'         => $this->input('last_name'),
            'department_id'     => $this->inputInt('department_id'),
            'position_id'       => $this->inputInt('position_id'),
            'join_date'         => $this->input('join_date'),
            'employment_status' => $this->input('employment_status', 'active'),
            'phone'             => $this->input('phone'),
            'email'             => $this->input('email'),
            'address'           => $this->input('address'),
            'urgent_phone'      => $this->input('urgent_phone'),
            'base_salary'       => $this->input('base_salary', 0),
            'company_name'      => $this->input('company_name'),
        ];

        // Handle file uploads
        $uploadDir = APP_PATH . '/../public/uploads/employees/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileFields = ['photo', 'photo_ktp', 'photo_ijazah'];
        foreach ($fileFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $filename = uniqid($field . '_') . '_' . basename($_FILES[$field]['name']);
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $filename)) {
                    $data[$field] = '/uploads/employees/' . $filename;
                }
            }
        }

        try {
            $cols = implode('`, `', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $this->db->query("INSERT INTO employees (`{$cols}`) VALUES ({$placeholders})", array_values($data));
            $this->flash('success', 'Data Karyawan berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal menambahkan karyawan: ' . $e->getMessage());
        }

        $this->redirect('admin/employees');
    }

    public function editEmployee(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $id = $this->inputInt('id');
        $employee = $this->db->query("SELECT * FROM employees WHERE id = ?", [$id])->fetch();
        if (!$employee) $this->redirect('admin/employees');

        $departments = $this->db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
        $positions = $this->db->query("SELECT * FROM positions ORDER BY name ASC")->fetchAll();

        $this->render('admin.form_employee', [
            'pageTitle'   => 'Edit Karyawan',
            'activePage'  => '/KehadiranApp/public/admin/employees',
            'departments' => $departments,
            'positions'   => $positions,
            'employee'    => $employee,
            'csrf_token'  => $this->generateCsrf()
        ]);
    }

    public function updateEmployee(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        $id = $this->inputInt('id');

        $data = [
            'first_name'        => $this->input('first_name'),
            'last_name'         => $this->input('last_name'),
            'department_id'     => $this->inputInt('department_id'),
            'position_id'       => $this->inputInt('position_id'),
            'join_date'         => $this->input('join_date'),
            'employment_status' => $this->input('employment_status', 'active'),
            'phone'             => $this->input('phone'),
            'email'             => $this->input('email'),
            'address'           => $this->input('address'),
            'urgent_phone'      => $this->input('urgent_phone'),
            'base_salary'       => $this->input('base_salary', 0),
            'company_name'      => $this->input('company_name'),
        ];

        // Handle file uploads
        $uploadDir = APP_PATH . '/../public/uploads/employees/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileFields = ['photo', 'photo_ktp', 'photo_ijazah'];
        foreach ($fileFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $filename = uniqid($field . '_') . '_' . basename($_FILES[$field]['name']);
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . $filename)) {
                    $data[$field] = '/uploads/employees/' . $filename;
                }
            }
        }

        try {
            $setClause = [];
            $values = [];
            foreach ($data as $k => $v) {
                $setClause[] = "`{$k}` = ?";
                $values[] = $v;
            }
            $values[] = $id;
            
            $sql = "UPDATE employees SET " . implode(', ', $setClause) . " WHERE id = ?";
            $this->db->query($sql, $values);
            
            $this->flash('success', 'Data Karyawan berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal memperbarui data: ' . $e->getMessage());
        }

        $this->redirect('admin/employees');
    }

    public function deleteEmployee(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf(); // [Fix 5.2] Added CSRF verification
        $id = $this->inputInt('id');

        try {
            // Check if user account exists and deactivate it
            $this->db->query("UPDATE users SET is_active = 0 WHERE employee_id = ?", [$id]);

            // Attempt to delete employee
            $this->db->query("DELETE FROM employees WHERE id = ?", [$id]);
            $this->flash('success', 'Data Karyawan berhasil dihapus dan Akun dinonaktifkan.');
        } catch (Exception $e) {
            // If foreign key constraint fails, do a soft delete instead
            $this->db->query("UPDATE employees SET employment_status = 'terminated' WHERE id = ?", [$id]);
            $this->flash('warning', 'Karyawan tidak bisa dihapus sepenuhnya karena masih memiliki data riwayat (Kehadiran/Gaji). Status diubah menjadi Terminated, dan akun dinonaktifkan.');
        }

        $this->redirect('admin/employees');
    }

    // ==========================================
    // ACCOUNT CREATION VIA AJAX & FORM
    // ==========================================

    public function searchEmployeeNoAccount(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $query = $_GET['q'] ?? '';
        
        $sql = "SELECT e.id, e.employee_code, e.first_name, e.last_name, d.name as department
                FROM employees e
                LEFT JOIN users u ON u.employee_id = e.id
                JOIN departments d ON d.id = e.department_id
                WHERE u.id IS NULL 
                AND e.employment_status = 'active'";
                
        $params = [];
        if ($query) {
            $sql .= " AND (e.employee_code LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)";
            $q = "%{$query}%";
            $params = [$q, $q, $q];
        }
        
        $sql .= " LIMIT 10";
        
        $results = $this->db->query($sql, $params)->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;
    }

    public function storeAccount(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        
        $empId    = (int)($_POST['employee_id'] ?? 0);
        $roleId   = (int)($_POST['role_id'] ?? 0);
        $password = '123!';

        if (!$empId || !$roleId || !$password) {
            $this->flash('danger', 'Semua form wajib diisi.');
            $this->redirect('hrd/accounts');
        }

        try {
            $emp = $this->db->query("SELECT employee_code, first_name, last_name FROM employees WHERE id = ?", [$empId])->fetch();
            if (!$emp) throw new Exception("Karyawan tidak ditemukan.");

            // Format Username: MGR001_Budi_Santoso
            $cleanFirstName = preg_replace('/[^a-zA-Z0-9]/', '', $emp['first_name']);
            $cleanLastName = preg_replace('/[^a-zA-Z0-9]/', '', $emp['last_name']);
            
            $username = $emp['employee_code'] . '_' . $cleanFirstName;
            if ($cleanLastName) {
                $username .= '_' . $cleanLastName;
            }
            
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $this->db->query(
                "INSERT INTO users (employee_id, username, password_hash, role_id) VALUES (?, ?, ?, ?)",
                [$empId, $username, $hash, $roleId]
            );

            $this->flash('success', "Akun berhasil dibuat dengan username: {$username}");
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal membuat akun: ' . $e->getMessage());
        }

        $this->redirect('hrd/accounts');
    }

    public function updateAccount(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        
        $userId = $this->inputInt('user_id');
        $roleId = $this->inputInt('role_id');
        $password = $this->input('password');
        $isActive = $this->inputInt('is_active');

        try {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $this->db->query("UPDATE users SET role_id = ?, password_hash = ?, is_active = ?, force_change_password = 1 WHERE id = ?", [$roleId, $hash, $isActive, $userId]);
            } else {
                $this->db->query("UPDATE users SET role_id = ?, is_active = ? WHERE id = ?", [$roleId, $isActive, $userId]);
            }
            $this->flash('success', 'Akun berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal memperbarui akun: ' . $e->getMessage());
        }

        $this->redirect('hrd/accounts');
    }

    /**
     * Halaman Surat Peringatan (SP) untuk HRD Manager & Supervisor
     */
    public function warningLetters(): void
    {
        $this->requireRole(['hrd_manager', 'supervisor']);
        
        $role = $_SESSION['role'];
        $empId = $_SESSION['employee_id'] ?? 0;
        
        if ($role === 'supervisor') {
            // Get supervisor's department
            $deptId = $this->db->query("SELECT department_id FROM employees WHERE id = ?", [$empId])->fetchColumn();
            
            $warningLetters = $this->db->query(
                "SELECT wl.*, e.first_name, e.last_name, e.employee_code, d.name as dept_name, p.name as pos_name,
                        u.username as issuer_name
                 FROM warning_letters wl
                 JOIN employees e ON e.id = wl.employee_id
                 JOIN departments d ON d.id = e.department_id
                 JOIN positions p ON p.id = e.position_id
                 JOIN users u ON u.id = wl.issued_by
                 WHERE e.department_id = ?
                 ORDER BY wl.issued_at DESC",
                [$deptId]
            )->fetchAll();
        } else {
            // hrd_manager sees all
            $warningLetters = $this->db->query(
                "SELECT wl.*, e.first_name, e.last_name, e.employee_code, d.name as dept_name, p.name as pos_name,
                        u.username as issuer_name
                 FROM warning_letters wl
                 JOIN employees e ON e.id = wl.employee_id
                 JOIN departments d ON d.id = e.department_id
                 JOIN positions p ON p.id = e.position_id
                 JOIN users u ON u.id = wl.issued_by
                 ORDER BY wl.issued_at DESC"
            )->fetchAll();
        }
        
        $this->render('admin.warning_letters', [
            'pageTitle' => 'Daftar Surat Peringatan (SP)',
            'activePage' => '/KehadiranApp/public/hrd/warning-letters',
            'warningLetters' => $warningLetters,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Acknowledge Surat Peringatan
     */
    public function acknowledgeWarningLetter(): void
    {
        $this->requireRole(['hrd_manager', 'supervisor']);
        $this->verifyCsrf();
        
        $id = $this->inputInt('id');
        $notes = $this->input('notes');
        
        $this->db->query(
            "UPDATE warning_letters SET acknowledged_by = ?, acknowledged_at = NOW(), notes = ? WHERE id = ?",
            [$_SESSION['user_id'], $notes, $id]
        );
        
        $this->flash('success', 'Surat Peringatan berhasil di-acknowledge.');
        $this->redirect('hrd/warning-letters');
    }

    /**
     * Halaman Statistik & Laporan (HRD Manager View)
     */
    public function statistics(): void
    {
        $this->requireRole(['hrd_manager']);
        
        // 1. Total Active vs Terminated Employees
        $activeEmp = $this->db->query("SELECT COUNT(*) FROM employees WHERE employment_status = 'active'")->fetchColumn();
        $termEmp = $this->db->query("SELECT COUNT(*) FROM employees WHERE employment_status = 'terminated'")->fetchColumn();
        
        // 2. Department size breakdown
        $deptStats = $this->db->query(
            "SELECT d.name, COUNT(e.id) as emp_count
             FROM departments d
             LEFT JOIN employees e ON e.department_id = d.id AND e.employment_status = 'active'
             GROUP BY d.id, d.name
             ORDER BY emp_count DESC"
        )->fetchAll();
        
        // 3. Current month attendance stats
        $month = date('Y-m');
        $attendanceStats = $this->db->query(
            "SELECT 
                COUNT(CASE WHEN final_status = 'HADIR' THEN 1 END) as present_days,
                COUNT(CASE WHEN final_status = 'UNPAID_TANPA' THEN 1 END) as alpha_days,
                COUNT(CASE WHEN final_status = 'UNPAID_DENGAN' THEN 1 END) as unpaid_excused_days,
                COUNT(CASE WHEN final_status = 'SAKIT' THEN 1 END) as sick_days,
                COUNT(CASE WHEN final_status = 'PAID_LEAVE' THEN 1 END) as leave_days,
                COUNT(CASE WHEN late_minutes > 0 THEN 1 END) as late_count
             FROM daily_attendance_status 
             WHERE DATE_FORMAT(attendance_date, '%Y-%m') = ?",
            [$month]
        )->fetch();
        
        // 4. Warning Letters by level
        $wlStats = $this->db->query(
            "SELECT wl_type, COUNT(*) as cnt 
             FROM warning_letters 
             GROUP BY wl_type"
        )->fetchAll();
        $wlCounts = ['WL1' => 0, 'WL2' => 0, 'WL3' => 0, 'TERMINATION' => 0];
        foreach ($wlStats as $w) {
            $wlCounts[$w['wl_type']] = (int)$w['cnt'];
        }

        $this->render('admin.statistics', [
            'pageTitle' => 'Statistik & Analitik Laporan',
            'activePage' => '/KehadiranApp/public/hrd/statistics',
            'activeEmp' => $activeEmp,
            'termEmp' => $termEmp,
            'deptStats' => $deptStats,
            'attStats' => $attendanceStats,
            'wlCounts' => $wlCounts,
            'currentMonth' => date('F Y')
        ]);
    }
}

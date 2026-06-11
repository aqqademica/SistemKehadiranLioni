<?php
// app/controllers/HrConfigController.php

class HrConfigController extends Controller
{
    public function salaryConfig(): void
    {
        $this->requireRole(['hrd_manager']);
        $tab = $this->input('tab', '');

        // 1. Position Salary Components
        $positions = $this->db->query("SELECT * FROM positions ORDER BY name ASC")->fetchAll();
        $positionComponents = $this->db->query(
            "SELECT c.*, p.name as position_name 
             FROM position_salary_components c 
             JOIN positions p ON p.id = c.position_id 
             ORDER BY p.name ASC, c.name ASC"
        )->fetchAll();

        // 2. Overtime Divider
        $otDividerRow = $this->db->query("SELECT * FROM system_settings WHERE `key` = 'overtime_divider'")->fetch();
        $otDivider = $otDividerRow ? $otDividerRow['value'] : 173;

        // 3. Global Deductions
        $globalDeductions = $this->db->query("SELECT * FROM global_deductions ORDER BY name ASC")->fetchAll();

        $this->render('admin.salary_config', [
            'pageTitle'          => 'Standar Gaji & Potongan',
            'activePage'         => '/KehadiranApp/public/hrd-manager/salary-config',
            'tab'                => $tab,
            'positions'          => $positions,
            'positionComponents' => $positionComponents,
            'otDivider'          => $otDivider,
            'globalDeductions'   => $globalDeductions,
            'csrf_token'         => $this->generateCsrf()
        ]);
    }

    // --- POSITIONS SALARY COMPONENTS CRUD ---
    public function storePositionSalary(): void
    {
        $this->requireRole(['hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "INSERT INTO position_salary_components (position_id, name, type, amount) VALUES (?, ?, ?, ?)",
                [$this->inputInt('position_id'), $this->input('name'), $this->input('type'), $this->input('amount')]
            );
            $this->flash('success', 'Komponen gaji berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd-manager/salary-config?tab=positions');
    }

    public function updatePositionSalary(): void
    {
        $this->requireRole(['hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "UPDATE position_salary_components SET position_id=?, name=?, type=?, amount=? WHERE id=?",
                [$this->inputInt('position_id'), $this->input('name'), $this->input('type'), $this->input('amount'), $this->inputInt('id')]
            );
            $this->flash('success', 'Komponen gaji berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd-manager/salary-config?tab=positions');
    }

    public function deletePositionSalary(): void
    {
        $this->requireRole(['hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query("DELETE FROM position_salary_components WHERE id = ?", [$this->inputInt('id')]);
            $this->flash('success', 'Komponen gaji berhasil dihapus.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd-manager/salary-config?tab=positions');
    }

    // --- OVERTIME DIVIDER UPDATE ---
    public function updateOvertimeDivider(): void
    {
        $this->requireRole(['hrd_manager']);
        $this->verifyCsrf();
        try {
            $divider = $this->inputInt('overtime_divider');
            $this->db->query("UPDATE system_settings SET value = ? WHERE `key` = 'overtime_divider'", [$divider]);
            $this->flash('success', 'Variabel pembagi overtime berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd-manager/salary-config?tab=overtime');
    }

    // --- GLOBAL DEDUCTIONS CRUD ---
    public function storeGlobalDeduction(): void
    {
        $this->requireRole(['hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "INSERT INTO global_deductions (name, percent_amount, fixed_amount, description) VALUES (?, ?, ?, ?)",
                [$this->input('name'), $this->input('percent_amount', 0), $this->input('fixed_amount', 0), $this->input('description')]
            );
            $this->flash('success', 'Variabel potongan berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd-manager/salary-config?tab=deductions');
    }

    public function updateGlobalDeduction(): void
    {
        $this->requireRole(['hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "UPDATE global_deductions SET name=?, percent_amount=?, fixed_amount=?, description=? WHERE id=?",
                [$this->input('name'), $this->input('percent_amount', 0), $this->input('fixed_amount', 0), $this->input('description'), $this->inputInt('id')]
            );
            $this->flash('success', 'Variabel potongan berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd-manager/salary-config?tab=deductions');
    }

    public function deleteGlobalDeduction(): void
    {
        $this->requireRole(['hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query("DELETE FROM global_deductions WHERE id = ?", [$this->inputInt('id')]);
            $this->flash('success', 'Variabel potongan berhasil dihapus.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd-manager/salary-config?tab=deductions');
    }

    // ============================================================
    // SHIFTS & WORKING HOURS CONFIGURATION
    // ============================================================

    public function shiftConfig(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $tab = $this->input('tab', 'shifts');

        $shifts = $this->db->query("SELECT * FROM shifts ORDER BY start_time ASC")->fetchAll();
        $positions = $this->db->query("SELECT * FROM positions ORDER BY name ASC")->fetchAll();
        $positionShifts = $this->db->query(
            "SELECT ps.*, p.name as position_name, s.name as shift_name 
             FROM position_shifts ps 
             JOIN positions p ON p.id = ps.position_id 
             JOIN shifts s ON s.id = ps.shift_id 
             ORDER BY p.name ASC"
        )->fetchAll();

        $workWeekTypeRow = $this->db->query("SELECT * FROM system_settings WHERE `key` = 'work_week_type'")->fetch();
        $workWeekType = $workWeekTypeRow ? $workWeekTypeRow['value'] : 5;

        $this->render('hr.shift_config', [
            'pageTitle'          => 'Konfigurasi Shift & Jam Kerja',
            'activePage'         => '/KehadiranApp/public/hrd/shift-config',
            'tab'                => $tab,
            'shifts'             => $shifts,
            'positions'          => $positions,
            'positionShifts'     => $positionShifts,
            'workWeekType'       => $workWeekType,
            'csrf_token'         => $this->generateCsrf()
        ]);
    }

    public function storeShift(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $this->db->query(
                "INSERT INTO shifts (name, start_time, end_time) VALUES (?, ?, ?)",
                [$this->input('name'), $this->input('start_time'), $this->input('end_time')]
            );
            $this->flash('success', 'Shift berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/shift-config?tab=shifts');
    }

    public function storePositionShift(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            // Upsert / ON DUPLICATE KEY UPDATE is easier since position_id is UNIQUE
            $this->db->query(
                "INSERT INTO position_shifts (position_id, shift_id) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE shift_id = VALUES(shift_id)",
                [$this->inputInt('position_id'), $this->inputInt('shift_id')]
            );
            $this->flash('success', 'Shift berhasil di-assign ke Jabatan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/shift-config?tab=positions');
    }

    public function updateWorkWeekType(): void
    {
        $this->requireRole(['hrd_admin', 'hrd_manager']);
        $this->verifyCsrf();
        try {
            $type = $this->inputInt('work_week_type');
            if (in_array($type, [5, 6])) {
                $this->db->query("UPDATE system_settings SET value = ? WHERE `key` = 'work_week_type'", [$type]);
                $this->flash('success', 'Sistem hari kerja berhasil diperbarui.');
            }
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal: ' . $e->getMessage());
        }
        $this->redirect('hrd/shift-config?tab=settings');
    }
}

<?php
class RequestController extends Controller
{
    private AttendanceRequest $requestModel;

    public function __construct()
    {
        parent::__construct();
        require_once APP_PATH . '/models/AttendanceRequest.php';
        $this->requestModel = new AttendanceRequest();
    }

    /**
     * Halaman Daftar Pengajuan Saya
     */
    public function index(): void
    {
        $this->requireLogin();
        $empId = $_SESSION['employee_id'];
        
        $requests = $this->requestModel->where(['employee_id' => $empId], 'created_at DESC');
        
        foreach ($requests as &$req) {
            $approval = $this->db->query(
                "SELECT notes FROM request_approvals WHERE request_id = ? ORDER BY created_at DESC LIMIT 1", 
                [$req['id']]
            )->fetch();
            $req['approver_notes'] = $approval ? $approval['notes'] : null;

            if (in_array($req['request_type'], ['paid_leave', 'tidak_hadir'])) {
                $detail = $this->db->query("SELECT start_date, end_date FROM leave_requests WHERE request_id = ?", [$req['id']])->fetch();
                $req['start_date'] = $detail['start_date'] ?? $req['attendance_date'];
                $req['end_date'] = $detail['end_date'] ?? $req['attendance_date'];
            } else {
                $req['start_date'] = $req['attendance_date'];
                $req['end_date'] = $req['attendance_date']; // sakit usually 1 day or has upload_deadline, but let's assume attendance_date is start_date.
            }
        }
        
        $this->render('requests.index', [
            'pageTitle'  => 'Pengajuan Saya',
            'activePage' => '/KehadiranApp/public/requests',
            'requests'   => $requests,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Form Pengajuan Tidak Finger
     */
    public function createTidakFinger(): void
    {
        $this->requireLogin();
        $this->render('requests.form_tidak_finger', [
            'pageTitle'  => 'Pengajuan Tidak Finger',
            'activePage' => '/KehadiranApp/public/requests',
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Simpan Pengajuan Tidak Finger
     */
    public function storeTidakFinger(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();
        
        $empId = $_SESSION['employee_id'];
        $date  = $this->input('attendance_date');
        $type  = $this->input('finger_type');
        $reason = $this->input('reason');

        // Validasi 2 jam (Sesuai configApp2.md L155)
        // Note: Implementasi sederhana, asumsikan jam masuk 08:00
        $deadline = strtotime($date . ' 10:00:00');
        if (time() > $deadline) {
            // Tetap boleh ajukan tapi mungkin ditandai atau auto-rejected/converted nantinya
        }

        $baseData = [
            'employee_id'     => $empId,
            'request_type'    => 'tidak_finger',
            'attendance_date' => $date,
            'workflow_status' => 'pending_supervisor',
            'submitted_at'    => date('Y-m-d H:i:s'),
            'notes'           => $reason
        ];

        $detailData = [
            'finger_type' => $type,
            'reason'      => $reason
        ];

        try {
            $this->requestModel->createWithDetails($baseData, 'tidak_finger_requests', $detailData);
            $this->flash('success', 'Pengajuan tidak finger berhasil dikirim.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal mengirim pengajuan.');
        }

        $this->redirect('requests');
    }

    /**
     * Form Pengajuan Leave/Cuti (Consolidated)
     */
    public function createLeave(): void
    {
        $this->requireLogin();
        
        $healthPartners = $this->db->query("SELECT * FROM health_partners WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        
        $this->render('requests.form_leave', [
            'pageTitle'  => 'Pengajuan Leave/Cuti',
            'activePage' => '/KehadiranApp/public/requests',
            'healthPartners' => $healthPartners,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Simpan Pengajuan Leave/Cuti (Consolidated)
     */
    public function storeLeave(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();
        
        $empId = $_SESSION['employee_id'];
        $requestType = $this->input('request_type'); // 'paid_leave', 'tidak_hadir', 'hourly_leave', 'sakit'
        $start = $this->input('start_date');
        $end   = $this->input('end_date') ?: $start;
        $reason = $this->input('reason');
        
        $days  = (strtotime($end) - strtotime($start)) / (60 * 60 * 24) + 1;

        $baseData = [
            'employee_id'     => $empId,
            'request_type'    => $requestType,
            'attendance_date' => $start,
            'workflow_status' => ($requestType === 'sakit') ? 'pending_hrd' : 'pending_supervisor',
            'submitted_at'    => date('Y-m-d H:i:s'),
            'notes'           => $reason
        ];

        $detailTable = null;
        $detailData = [];

        try {
            if ($requestType === 'sakit') {
                $detailTable = 'sick_requests';
                $documentPath = null;
                
                // Handle file upload
                if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = APP_PATH . '/../public/uploads/documents/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    
                    $filename = uniqid('sakit_') . '_' . basename($_FILES['document']['name']);
                    $targetPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['document']['tmp_name'], $targetPath)) {
                        $documentPath = '/uploads/documents/' . $filename;
                    }
                }
                
                $partnerId = $this->inputInt('health_partner_id');
                $providerType = 'klinik';
                $providerName = 'Umum';

                if ($partnerId > 0) {
                    $hp = $this->db->query("SELECT name, type FROM health_partners WHERE id = ?", [$partnerId])->fetch();
                    if ($hp) {
                        $providerType = $hp['type'];
                        $providerName = $hp['name'];
                    }
                } else {
                    $partnerId = null; // null for foreign key
                    $providerType = $this->input('provider_type', 'klinik');
                    $providerName = $this->input('provider_name', 'Umum');
                }
                
                $detailData = [
                    'provider_type'       => $providerType,
                    'provider_name'       => $providerName,
                    'health_partner_id'   => $partnerId,
                    'illness_description' => $reason,
                    'document_type'       => 'upload',
                    'document_path'       => $documentPath,
                    'upload_deadline'     => date('Y-m-d', strtotime('+2 days'))
                ];
            } elseif ($requestType === 'hourly_leave') {
                $detailTable = 'hourly_leave_requests';
                $detailData = [
                    'hours_requested' => $this->inputInt('hours_requested', 1),
                    'start_time'      => $this->input('start_time', '08:00:00'),
                    'reason'          => $reason
                ];
            } else {
                // paid_leave or tidak_hadir -> put to leave_requests
                $detailTable = 'leave_requests';
                $detailData = [
                    'leave_type' => 'annual', // Default since ENUM only has annual, half_day, legal
                    'start_date' => $start,
                    'end_date'   => $end,
                    'total_days' => $days,
                    'reason'     => $reason
                ];
            }

            if ($detailTable) {
                $this->requestModel->createWithDetails($baseData, $detailTable, $detailData);
            } else {
                $this->requestModel->insert($baseData); // Fallback if no details
            }
            
            $this->flash('success', 'Pengajuan berhasil dikirim.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal mengirim pengajuan: ' . $e->getMessage());
        }

        $this->redirect('requests');
    }

    public function cancelRequest(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();
        $id = $this->inputInt('id');
        $empId = $_SESSION['employee_id'];

        $req = $this->requestModel->find($id);
        if ($req && $req['employee_id'] == $empId) {
            // Delete request (cascade will handle details)
            $this->db->query("DELETE FROM attendance_requests WHERE id = ?", [$id]);
            $this->flash('success', 'Pengajuan berhasil dibatalkan dan dihapus.');
        } else {
            $this->flash('danger', 'Tidak dapat membatalkan pengajuan ini.');
        }
        $this->redirect('requests');
    }

    public function updateLeave(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();
        $id = $this->inputInt('id');
        $empId = $_SESSION['employee_id'];
        
        $start = $this->input('start_date');
        $end = $this->input('end_date') ?: $start;
        $reason = $this->input('reason');
        $days = (strtotime($end) - strtotime($start)) / (60 * 60 * 24) + 1;

        $req = $this->requestModel->find($id);
        if ($req && $req['employee_id'] == $empId && in_array($req['request_type'], ['paid_leave', 'tidak_hadir'])) {
            // Update base
            $this->requestModel->update($id, [
                'attendance_date' => $start,
                'workflow_status' => 'pending_supervisor', // Reset to pending
                'notes' => $reason
            ]);

            // Update details
            $this->db->query(
                "UPDATE leave_requests SET start_date = ?, end_date = ?, total_days = ?, reason = ? WHERE request_id = ?",
                [$start, $end, $days, $reason, $id]
            );

            // Log update
            $this->db->query(
                "INSERT INTO request_approvals (request_id, approver_id, role, decision, notes) VALUES (?, ?, ?, ?, ?)",
                [$id, $_SESSION['user_id'], 'employee', 'reject', 'Pengajuan diubah/direvisi oleh Karyawan. Menunggu approval ulang.']
            ); // Using 'reject' just to log a revision note since there is no 'revise' ENUM.

            $this->flash('success', 'Pengajuan berhasil diubah dan dikirim ulang untuk approval.');
        } else {
            $this->flash('danger', 'Tidak dapat mengubah pengajuan ini.');
        }
        $this->redirect('requests');
    }

    /**
     * Halaman Antrian Approval (Supervisor / HRD)
     */
    public function approvals(): void
    {
        $this->requireLogin();
        $role = $_SESSION['role'] ?? '';
        if ($role === 'employee') $this->redirect('dashboard');

        $empId = $_SESSION['employee_id'];
        
        // Ambil department ID user login
        $dept = $this->db->query("SELECT department_id FROM employees WHERE id = ?", [$empId])->fetch();
        $deptId = $dept['department_id'] ?? 0;

        if ($role === 'supervisor') {
            $requests = $this->requestModel->getPendingForSupervisor($deptId);
        } elseif ($role === 'hrd_admin' || $role === 'hrd_manager') {
            $status = ($role === 'hrd_admin') ? 'pending_hrd' : 'pending_manager';
            $requests = $this->requestModel->where(['workflow_status' => $status], 'created_at ASC');
            
            // Tambahkan data employee ke hasil query manually karena 'where' cuma return raw table
            foreach ($requests as &$req) {
                $emp = $this->db->query("SELECT first_name, last_name, employee_code FROM employees WHERE id = ?", [$req['employee_id']])->fetch();
                $req['first_name'] = $emp['first_name'] ?? '-';
                $req['last_name'] = $emp['last_name'] ?? '';
                $req['employee_code'] = $emp['employee_code'] ?? '-';

                // Ambil detail spesifik
                if ($req['request_type'] === 'sakit') {
                    $detail = $this->db->query("SELECT * FROM sick_requests WHERE request_id = ?", [$req['id']])->fetch();
                    $req['detail'] = $detail ?: [];
                } elseif (in_array($req['request_type'], ['paid_leave', 'tidak_hadir'])) {
                    $detail = $this->db->query("SELECT * FROM leave_requests WHERE request_id = ?", [$req['id']])->fetch();
                    $req['detail'] = $detail ?: [];
                } elseif ($req['request_type'] === 'hourly_leave') {
                    $detail = $this->db->query("SELECT * FROM hourly_leave_requests WHERE request_id = ?", [$req['id']])->fetch();
                    $req['detail'] = $detail ?: [];
                } elseif ($req['request_type'] === 'tidak_finger') {
                    $detail = $this->db->query("SELECT * FROM tidak_finger_requests WHERE request_id = ?", [$req['id']])->fetch();
                    $req['detail'] = $detail ?: [];
                } else {
                    $req['detail'] = [];
                }
            }
        } else {
            $requests = [];
        }

        $healthPartners = [];
        if (in_array($role, ['hrd_admin', 'hrd_manager'])) {
            $healthPartners = $this->db->query("SELECT * FROM health_partners ORDER BY name ASC")->fetchAll();
        }

        $this->render('requests.approvals', [
            'pageTitle'  => 'Antrian Approval',
            'activePage' => '/KehadiranApp/public/requests/approvals',
            'requests'   => $requests,
            'healthPartners' => $healthPartners,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Proses Approval / Rejection
     */
    public function processApproval(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();
        
        $requestId = $this->inputInt('request_id');
        $decision  = $this->input('decision'); // 'approve' or 'reject'
        $notes     = $this->input('notes');
        $role      = $_SESSION['role'] ?? '';

        if (!in_array($decision, ['approve', 'reject'])) {
            $this->flash('danger', 'Keputusan tidak valid.');
            $this->redirect('requests/approvals');
        }

        $success = false;
        if ($decision === 'approve') {
            $request = $this->requestModel->find($requestId);
            
            // Logika Verifikasi Sakit oleh HRD
            $statusOverride = null;
            if ($request['request_type'] === 'sakit' && $role === 'hrd_admin') {
                $partnerId = $this->inputInt('health_partner_id');
                if ($partnerId > 0) {
                    // Update sick_requests dengan health_partner_id
                    $this->db->query("UPDATE sick_requests SET health_partner_id = ? WHERE request_id = ?", [$partnerId, $requestId]);
                    $statusOverride = 'approved';
                } else {
                    // Jika Lainnya (tidak terdaftar), eskalasi ke Manager
                    $statusOverride = 'pending_manager';
                }
            }

            $success = $this->requestModel->approve($requestId, $_SESSION['user_id'], $role, $notes);
            
            // Override status jika ada logika khusus
            if ($success && $statusOverride) {
                $this->requestModel->update($requestId, ['workflow_status' => $statusOverride]);
                $request['workflow_status'] = $statusOverride; // Update local variable for next checks
            }
            
        } else {
            $success = $this->requestModel->update($requestId, [
                'workflow_status' => 'rejected',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            // Catat log rejection
            $this->db->query(
                "INSERT INTO request_approvals (request_id, approver_id, role, decision, notes) 
                 VALUES (?, ?, ?, 'reject', ?)",
                [$requestId, $_SESSION['user_id'], $role, $notes]
            );
        }

        if ($success) {
            $this->flash('success', 'Pengajuan berhasil di-' . ($decision === 'approve' ? 'setujui' : 'tolak') . '.');
            
            $request = $this->requestModel->find($requestId);
            
            // Add Notification Logic
            $targetUserId = $this->db->query("SELECT id FROM users WHERE employee_id = ?", [$request['employee_id']])->fetchColumn();
            if ($targetUserId) {
                $statusMsg = $decision === 'approve' ? 'Disetujui' : 'Ditolak';
                $notifType = $decision === 'approve' ? 'success' : 'danger';
                $typeLabel = ucwords(str_replace('_', ' ', $request['request_type']));
                $this->db->query(
                    "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)",
                    [
                        $targetUserId,
                        "Pengajuan {$typeLabel} {$statusMsg}",
                        "Pengajuan {$typeLabel} Anda untuk tanggal " . date('d M Y', strtotime($request['attendance_date'])) . " telah " . strtolower($statusMsg) . ".",
                        $notifType
                    ]
                );
            }

            // Jika sudah approved final, trigger update status kehadiran harian
            if ($request['workflow_status'] === 'approved') {
                $this->finalizeAttendanceStatus($request);
            }
        } else {
            $this->flash('danger', 'Gagal memproses pengajuan.');
        }

        $this->redirect('requests/approvals');
    }

    /**
     * Finalisasi status kehadiran harian setelah pengajuan disetujui
     */
    private function finalizeAttendanceStatus(array $request): void
    {
        require_once APP_PATH . '/models/Attendance.php';
        $attModel = new Attendance();
        
        $status = match($request['request_type']) {
            'tidak_finger' => 'HADIR',
            'paid_leave'   => 'PAID_LEAVE',
            'tidak_hadir'  => 'UNPAID_DENGAN',
            'sakit'        => 'SAKIT',
            'hourly_leave' => 'HOURLY_UNPAID',
            default        => 'HADIR'
        };

        $attModel->updateDailyStatus($request['employee_id'], $request['attendance_date'], [
            'final_status' => $status,
            'notes'        => 'Approved via ' . $request['request_type'] . ' request'
        ]);
    }
}

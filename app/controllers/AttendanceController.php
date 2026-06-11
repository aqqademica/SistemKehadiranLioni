<?php
class AttendanceController extends Controller
{
    private Attendance $attendanceModel;

    public function __construct()
    {
        parent::__construct();
        require_once APP_PATH . '/models/Attendance.php';
        $this->attendanceModel = new Attendance();
    }

    /**
     * Halaman Riwayat Kehadiran (Employee)
     */
    public function index(): void
    {
        $this->requireLogin();
        $empId = $_SESSION['employee_id'];
        
        $month = $this->input('month', date('m'));
        $year  = $this->input('year', date('Y'));
        
        $startDate = "$year-$month-01";
        $endDate   = date('Y-m-t', strtotime($startDate));
        
        $logs = $this->attendanceModel->getAttendanceRange($empId, $startDate, $endDate);
        
        $this->render('attendance.index', [
            'pageTitle'  => 'Riwayat Kehadiran',
            'activePage' => '/KehadiranApp/public/attendance',
            'logs'       => $logs,
            'month'      => $month,
            'year'       => $year
        ]);
    }

    /**
     * Proses Log Fingerprint Dummy
     */
    public function logFinger(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();
        
        $empId = $_SESSION['employee_id'];
        $type  = $this->input('type'); // 'in' or 'out'
        $now   = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        
        if (!in_array($type, ['in', 'out'])) {
            $this->flash('danger', 'Tipe log tidak valid.');
            $this->redirect('dashboard');
        }

        $success = $this->attendanceModel->logFinger($empId, $today, $type, $now);
        
        if ($success) {
            // Trigger Daily Processor (Sederhana untuk sekarang)
            $this->processDailyStatus($empId, $today);
            $this->flash('success', 'Berhasil mencatat finger ' . ($type === 'in' ? 'masuk' : 'keluar') . '.');
        } else {
            $this->flash('danger', 'Gagal mencatat kehadiran.');
        }
        
        $this->redirect('dashboard');
    }

    /**
     * Halaman Kamera Absensi
     */
    public function camera(): void
    {
        $this->requireLogin();
        $this->render('attendance.camera', [
            'pageTitle'  => 'Absensi Kamera',
            'activePage' => '/KehadiranApp/public/attendance/camera',
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    /**
     * Proses Camera Attendance
     */
    public function logCamera(): void
    {
        $this->requireLogin();
        $this->verifyCsrf();
        
        $empId = $_SESSION['employee_id'];
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');
        
        $data = [
            'employee_id' => $empId,
            'log_date'    => $today,
            'latitude'    => $this->input('latitude'),
            'longitude'   => $this->input('longitude'),
            'notes'       => $this->input('notes'),
            'created_at'  => $now
        ];

        // Tentukan apakah ini Masuk atau Keluar
        $existing = $this->db->query(
            "SELECT id, timestamp_in FROM camera_attendance_logs WHERE employee_id = ? AND log_date = ? LIMIT 1",
            [$empId, $today]
        )->fetch();

        if ($existing) {
            $data['timestamp_out'] = $now;
            $uploadId = $existing['id'];
        } else {
            $data['timestamp_in'] = $now;
        }

        // Handle Uploads
        $photos = ['photo_selfie', 'photo_colleague', 'photo_client'];
        foreach ($photos as $photo) {
            if (isset($_FILES[$photo]) && $_FILES[$photo]['error'] === UPLOAD_ERR_OK) {
                $filename = $this->uploadFile($_FILES[$photo], 'attendance');
                if ($filename) {
                    $data[$photo] = $filename;
                }
            }
        }

        if ($existing) {
            $this->db->query(
                "UPDATE camera_attendance_logs SET timestamp_out = ?, photo_selfie = COALESCE(?, photo_selfie), 
                 photo_colleague = COALESCE(?, photo_colleague), photo_client = COALESCE(?, photo_client),
                 latitude = ?, longitude = ?, notes = ? WHERE id = ?",
                [$now, $data['photo_selfie'] ?? null, $data['photo_colleague'] ?? null, $data['photo_client'] ?? null, 
                 $data['latitude'], $data['longitude'], $data['notes'], $uploadId]
            );
        } else {
            $this->attendanceModel->logCamera($data);
        }

        $this->flash('success', 'Absensi kamera berhasil disimpan.');
        $this->redirect('dashboard');
    }

    /**
     * [Fix 2.5] Use AttendanceService for proper lateness calculation
     */
    private function processDailyStatus(int $employeeId, string $date): void
    {
        require_once APP_PATH . '/services/AttendanceService.php';
        $service = new AttendanceService();
        $service->resolveStatus($employeeId, $date);
    }

    /**
     * Helper Upload File
     */
    private function uploadFile(array $file, string $subfolder): ?string
    {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('ATT_') . '_' . time() . '.' . $ext;
        $target = UPLOAD_PATH . '/' . $subfolder . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            return $filename;
        }
        return null;
    }
}

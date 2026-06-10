<?php
require_once ROOT_PATH . '/app/models/User.php';

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    // GET /login
    public function login(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('dashboard');
        }
        $this->view('auth.login', [
            'csrf'        => $this->generateCsrf(),
            'error'       => null,
            'oldUsername' => '',
            'expired'     => isset($_GET['expired']),
        ]);
    }

    // POST /login
    public function processLogin(): void
    {
        $this->verifyCsrf();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->view('auth.login', [
                'csrf'        => $this->generateCsrf(),
                'error'       => 'Username dan password wajib diisi.',
                'oldUsername' => $username,
            ]);
            return;
        }

        // Cari user
        $user = $this->userModel->findByUsername($username);

        if (!$user || !$user['is_active']) {
            $this->logFailedAttempt($username);
            $this->view('auth.login', [
                'csrf'        => $this->generateCsrf(),
                'error'       => 'Username tidak ditemukan atau akun tidak aktif.',
                'oldUsername' => $username,
            ]);
            return;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->logFailedAttempt($username);
            $this->view('auth.login', [
                'csrf'        => $this->generateCsrf(),
                'error'       => 'Password salah. Periksa kembali.',
                'oldUsername' => $username,
            ]);
            return;
        }

        // Login berhasil — buat session
        session_regenerate_id(true);

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['employee_id']   = $user['employee_id'];
        $_SESSION['role']          = $user['role_name'];
        $_SESSION['last_activity'] = time();
        $_SESSION['user']          = [
            'id'         => $user['id'],
            'username'   => $user['username'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'role'       => $user['role_name'],
        ];

        if (!empty($user['force_change_password'])) {
            $_SESSION['must_change_password'] = true;
            $this->redirect('change-password');
        }

        // Update last_login
        $this->db->query(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user['id']]
        );

        // Audit log
        $this->db->query(
            "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, 'login', 'users', ?, ?, ?)",
            [$user['id'], $user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
        );

        $this->redirect('dashboard');
    }

    // GET /logout
    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->db->query(
                "INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address) VALUES (?, 'logout', 'users', ?, ?)",
                [$_SESSION['user_id'], $_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']
            );
        }
        session_unset();
        session_destroy();
        $this->redirect('login');
    }

    // GET /change-password
    public function changePassword(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
        $this->view('auth.change_password', [
            'csrf' => $this->generateCsrf()
        ]);
    }

    // POST /update-password
    public function updatePassword(): void
    {
        $this->verifyCsrf();
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }

        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($password) || $password !== $confirm || strlen($password) < 6) {
            $this->view('auth.change_password', [
                'csrf' => $this->generateCsrf(),
                'error' => 'Password tidak cocok atau kurang dari 6 karakter.'
            ]);
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->query("UPDATE users SET password_hash = ?, force_change_password = 0 WHERE id = ?", [$hash, $_SESSION['user_id']]);
        
        unset($_SESSION['must_change_password']);
        $this->redirect('dashboard');
    }

    private function logFailedAttempt(string $username): void
    {
        $this->db->query(
            "INSERT INTO audit_logs (action, table_name, ip_address, user_agent, new_values) VALUES ('login_failed', 'users', ?, ?, ?)",
            [
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode(['username' => $username])
            ]
        );
    }
}

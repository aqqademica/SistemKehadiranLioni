<?php
// ============================================================
// Base Controller
// ============================================================

class Controller
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Render view dengan data
    protected function view(string $viewPath, array $data = []): void
    {
        extract($data);
        $fullPath = VIEW_PATH . '/' . str_replace('.', '/', $viewPath) . '.php';
        if (!file_exists($fullPath)) {
            die("View tidak ditemukan: {$fullPath}");
        }
        require $fullPath;
    }

    // Render view di dalam layout
    protected function render(string $viewPath, array $data = [], string $layout = 'shared.layout'): void
    {
        $data['content_view'] = VIEW_PATH . '/' . str_replace('.', '/', $viewPath) . '.php';
        $this->view($layout, $data);
    }

    // Redirect
    protected function redirect(string $path): void
    {
        header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
        exit;
    }

    // Response JSON
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // Cek login
    protected function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }

        if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] === true) {
            $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if (strpos($currentUrl, '/change-password') === false && strpos($currentUrl, '/update-password') === false && strpos($currentUrl, '/logout') === false) {
                $this->redirect('change-password');
            }
        }
    }

    // Cek role
    protected function requireRole(array $roles): void
    {
        $this->requireLogin();
        if (!in_array($_SESSION['role'] ?? '', $roles)) {
            $this->redirect('dashboard');
        }
    }

    // Ambil user yang sedang login
    protected function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    // Flash message
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    // Validasi CSRF token
    protected function verifyCsrf(): void
    {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('CSRF token tidak valid.');
        }
    }

    // Generate CSRF token
    /**
     * [Fix 5.1] Generate CSRF token — regenerate on every form render
     */
    protected function generateCsrf(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    // Sanitasi input
    protected function input(string $key, string $default = ''): string
    {
        return htmlspecialchars(trim($_POST[$key] ?? $_GET[$key] ?? $default), ENT_QUOTES, 'UTF-8');
    }

    protected function inputInt(string $key, int $default = 0): int
    {
        return (int) ($this->input($key, (string)$default));
    }

    // Cek request method
    protected function isPost(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
    protected function isGet():  bool { return $_SERVER['REQUEST_METHOD'] === 'GET';  }
}

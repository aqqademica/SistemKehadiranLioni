<?php
// ============================================================
// Router — Front Controller Pattern
// ============================================================

class Router
{
    private array $routes = [];

    // Daftarkan route GET
    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    // Daftarkan route POST
    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    // Jalankan routing
    public function dispatch(string $uri, string $method): void
    {
        $uri    = '/' . trim(parse_url($uri, PHP_URL_PATH), '/');
        $method = strtoupper($method);

        // Cari exact match
        if (isset($this->routes[$method][$uri])) {
            $this->call($this->routes[$method][$uri], []);
            return;
        }

        // Cari route dengan parameter dinamis
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $pattern);
            $regex = '@^' . $regex . '$@';
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // buang full match
                $this->call($handler, $matches);
                return;
            }
        }

        // 404
        http_response_code(404);
        $this->render404();
    }

    // Panggil Controller@method
    private function call(string $handler, array $params): void
    {
        [$controllerName, $method] = explode('@', $handler);
        $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            die("Controller tidak ditemukan: {$controllerName}");
        }
        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            die("Class tidak ditemukan: {$controllerName}");
        }
        $controller = new $controllerName();
        if (!method_exists($controller, $method)) {
            die("Method tidak ditemukan: {$controllerName}@{$method}");
        }
        call_user_func_array([$controller, $method], $params);
    }

    private function render404(): void
    {
        $view = VIEW_PATH . '/shared/404.php';
        if (file_exists($view)) require $view;
        else echo '<h1>404 — Halaman Tidak Ditemukan</h1>';
    }
}

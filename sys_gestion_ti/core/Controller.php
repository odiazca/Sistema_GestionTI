<?php
class Controller
{
    protected function model(string $name)
    {
        require_once APP_PATH . "/models/{$name}.php";
        return new $name();
    }

    /** Renderiza una vista dentro del layout principal */
    protected function render(string $view, array $data = [], string $title = ''): void
    {
        extract($data);
        ob_start();
        require APP_PATH . "/views/{$view}.php";
        $content = ob_get_clean();
        $pageTitle = $title;
        $menu = require ROOT_PATH . '/config/menu.php';
        $badges = $this->badges();
        try { $notifs = $this->model('Dashboard')->notificaciones(Auth::user()['rol'] ?? ''); } catch (Throwable $e) { $notifs = []; }
        require APP_PATH . '/views/layouts/main.php';
    }

    /** Renderiza una vista sin layout (login) */
    protected function renderPlain(string $view, array $data = []): void
    {
        extract($data);
        require APP_PATH . "/views/{$view}.php";
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) $this->redirect('login');
    }

    protected function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function flash(string $type, string $msg): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
    }

    /** Exige POST con CSRF válido; si falla vuelve a $back */
    protected function requirePost(string $back): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf'] ?? null)) {
            $this->flash('danger', 'La solicitud no es válida o la sesión expiró. Inténtalo de nuevo.');
            $this->redirect($back);
        }
    }

    protected function requireRole(array $roles): void
    {
        $this->requireAuth();
        if (!in_array(Auth::user()['rol'], $roles, true)) {
            http_response_code(403);
            $this->render('modulos/403', [], 'Acceso denegado');
            exit;
        }
    }

    protected function paginate(int $total, int $per, int $page): array
    {
        $pages = max(1, (int)ceil($total / $per));
        $page = min(max(1, $page), $pages);
        return ['page' => $page, 'pages' => $pages, 'per' => $per, 'total' => $total, 'offset' => ($page - 1) * $per];
    }

    private function badges(): array
    {
        try {
            $d = $this->model('Dashboard');
            return $d->badges();
        } catch (Throwable $e) { return []; }
    }
}

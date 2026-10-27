<?php
class App
{
    public function run(): void
    {
        Auth::start();
        $url = trim($_GET['url'] ?? '', '/');
        $parts = $url === '' ? [] : explode('/', filter_var($url, FILTER_SANITIZE_URL));

        $slug   = strtolower($parts[0] ?? '');
        $method = strtolower($parts[1] ?? 'index');
        $params = array_slice($parts, 2);

        $esCliente = Auth::check() && (Auth::user()['rol'] ?? '') === 'Cliente';
        if ($slug === '') $slug = Auth::check() ? ($esCliente ? 'portal' : 'dashboard') : 'login';

        // Rutas de autenticación
        if ($slug === 'login' || $slug === 'logout') {
            require APP_PATH . '/controllers/AuthController.php';
            (new AuthController())->{$slug}();
            return;
        }

        // El rol Cliente solo puede usar el portal (y los recursos públicos de la PWA)
        if ($esCliente && $slug !== 'portal') { header('Location: ' . BASE_URL . '/portal'); return; }

        // Controladores existentes
        $class = ucfirst($slug) . 'Controller';
        $file  = APP_PATH . "/controllers/{$class}.php";
        if (preg_match('/^[a-z_]+$/', $slug) && is_file($file)) {
            require $file;
            $ctrl = new $class();
            if (preg_match('/^[a-z_]+$/', $method) && is_callable([$ctrl, $method])) {
                $ctrl->{$method}(...$params);
                return;
            }
        }

        // Módulos aún no desarrollados -> pantalla de módulo
        require APP_PATH . '/controllers/ModuloController.php';
        (new ModuloController())->mostrar($slug);
    }
}

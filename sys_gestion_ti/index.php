<?php
require __DIR__ . '/config/config.php';
require ROOT_PATH . '/core/helpers.php';
require ROOT_PATH . '/core/Database.php';
require ROOT_PATH . '/core/Auth.php';
require ROOT_PATH . '/core/Controller.php';
require ROOT_PATH . '/core/CrudController.php';
require ROOT_PATH . '/core/App.php';

// Zona horaria configurable (Empresa → Regional)
try { $__tz = cfg('zona_horaria'); if ($__tz && in_array($__tz, timezone_identifiers_list(), true)) date_default_timezone_set($__tz); } catch (Throwable $e) {}

try {
    (new App())->run();
} catch (PDOException $e) {
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:system-ui;padding:48px;max-width:640px;margin:auto">'
       . '<h2>No se pudo conectar a la base de datos</h2>'
       . '<p>Ejecuta primero el instalador: <a href="' . BASE_URL . '/install.php">install.php</a></p>'
       . '<pre style="background:#f3f4f6;padding:12px;border-radius:8px;white-space:pre-wrap">' . htmlspecialchars($e->getMessage()) . '</pre></body>';
}

<?php
// ============================================================
//  SYS GESTION TI - Configuración general
// ============================================================
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'sys_gestion_ti');
define('DB_USER', 'root');
define('DB_PASS', '');          // <-- coloca tu contraseña si la tienes

define('APP_NAME', 'TechDesk Pro');
define('APP_SUBTITLE', 'Sistema de Gestión de Soporte TI');
define('APP_VERSION', '1.0.0');

date_default_timezone_set('America/Lima');

// URL base calculada automáticamente (funciona en /sys_gestion_ti o en un virtual host)
$__base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $__base);
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

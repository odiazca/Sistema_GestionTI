<?php
class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('techdesk_sess');
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
            session_start();
        }
    }
    public static function check(): bool { return !empty($_SESSION['user']); }
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function login(array $u): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $u['id'], 'nombre' => $u['nombres'] . ' ' . $u['apellidos'],
            'nombres' => $u['nombres'], 'email' => $u['email'], 'rol' => $u['rol'],
            'cargo' => $u['cargo'], 'color' => $u['avatar_color'],
        ];
    }
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
    public static function csrf(): string
    {
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf'];
    }
    public static function verifyCsrf(?string $t): bool
    {
        return !empty($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
    }
}

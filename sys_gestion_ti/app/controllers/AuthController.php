<?php
class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) $this->redirect((Auth::user()['rol'] ?? '') === 'Cliente' ? 'portal' : 'dashboard');
        $error = '';
        $old = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old = trim($_POST['login'] ?? '');
            $pass = $_POST['password'] ?? '';
            $_SESSION['intentos'] = $_SESSION['intentos'] ?? 0;
            $bloqueo = $_SESSION['bloqueo'] ?? 0;
            if ($bloqueo > time()) {
                $error = 'Demasiados intentos. Espera ' . ($bloqueo - time()) . ' segundos.';
            } elseif (!Auth::verifyCsrf($_POST['csrf'] ?? null)) {
                $error = 'Sesión expirada. Recarga la página e inténtalo de nuevo.';
            } elseif ($old === '' || $pass === '') {
                $error = 'Ingresa tu usuario y contraseña.';
            } else {
                $u = $this->model('Usuario')->buscarLogin($old);
                if ($u && password_verify($pass, $u['password'])) {
                    $_SESSION['intentos'] = 0;
                    Auth::login($u);
                    $this->model('Usuario')->registrarAcceso((int)$u['id']);
                    try { Database::conn()->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([$u['id'], 'Inició sesión', $_SERVER['REMOTE_ADDR'] ?? '', 'bi-box-arrow-in-right', '#64748b']); } catch (Throwable $e) {}
                    $this->redirect($u['rol'] === 'Cliente' ? 'portal' : 'dashboard');
                }
                $_SESSION['intentos']++;
                if ($_SESSION['intentos'] >= 5) { $_SESSION['bloqueo'] = time() + 60; $_SESSION['intentos'] = 0; }
                $error = 'Usuario o contraseña incorrectos.';
            }
        }
        $this->renderPlain('auth/login', ['error' => $error, 'old' => $old, 'csrf' => Auth::csrf()]);
    }

    public function logout(): void
    {
        if (Auth::check()) { try { Database::conn()->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([Auth::user()['id'], 'Cerró sesión', $_SERVER['REMOTE_ADDR'] ?? '', 'bi-box-arrow-left', '#64748b']); } catch (Throwable $e) {} }
        Auth::logout();
        $this->redirect('login');
    }
}

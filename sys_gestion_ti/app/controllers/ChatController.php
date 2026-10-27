<?php
require_once ROOT_PATH . '/core/ia.php';

/** Chat con IA para consultar el sistema (solo Administrador). El historial vive en la sesión. */
class ChatController extends Controller
{
    private int $uid = 0;

    private function out(array $d, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($d + ['ok' => true], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    private function fail(string $msg, int $code = 400): void { $this->out(['ok' => false, 'error' => $msg], $code); }
    private function body(): array { static $b = null; if ($b === null) $b = json_decode((string)file_get_contents('php://input'), true) ?: []; return $b; }

    private function guard(bool $escribe): void
    {
        if (!Auth::check()) $this->fail('Tu sesión expiró. Vuelve a iniciar sesión.', 401);
        if (Auth::user()['rol'] !== 'Administrador') $this->fail('Solo el administrador puede usar el chat de IA.', 403);
        if ($escribe && ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf((string)($this->body()['csrf'] ?? '')))) $this->fail('Solicitud no válida. Recarga la página.', 419);
        $this->uid = (int)Auth::user()['id'];
    }

    public function index(): void
    {
        $this->requireRole(['Administrador']);
        $this->render('chat/index', [], 'Chat IA');
    }

    public function estado(): void
    {
        $this->guard(false); $c = ia_cfg();
        $this->out(['listo' => ia_listo($c), 'proveedor' => IA_PRESETS[$c['proveedor']]['nombre'], 'empresa' => cfg('empresa_nombre', APP_NAME),
                    'historial' => array_map(fn($h) => ['q' => $h['q'], 'a' => $h['a']], $_SESSION['chat'] ?? [])]);
    }

    public function enviar(): void
    {
        $this->guard(true);
        $msg = trim((string)($this->body()['mensaje'] ?? ''));
        if (mb_strlen($msg) < 2) $this->fail('Escribe tu mensaje.');
        if (mb_strlen($msg) > 400) $this->fail('El mensaje es demasiado largo (máx. 400 caracteres).');
        $now = time(); $_SESSION['chat_t'] = array_values(array_filter($_SESSION['chat_t'] ?? [], fn($t) => $t > $now - 600));
        if (count($_SESSION['chat_t']) >= 40) $this->fail('Demasiados mensajes seguidos. Espera unos minutos.', 429);
        $_SESSION['chat_t'][] = $now;
        $hist = $_SESSION['chat'] ?? [];
        try { ia_instalar(); $r = ia_chat($msg, $hist); } catch (Throwable $e) { $this->fail($e->getMessage()); }
        $hist[] = ['q' => $msg, 'sql' => $r['sql'] ?? null, 'a' => mb_substr($r['texto'], 0, 800)];
        $_SESSION['chat'] = array_slice($hist, -12);
        if (!empty($r['sql'])) {
            try { Database::conn()->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([$this->uid, 'Consulta al Chat IA', mb_substr($msg, 0, 190), 'bi-chat-dots-fill', '#7c3aed']); } catch (Throwable $e) {}
        }
        $this->out(['texto' => $r['texto'], 'tabla' => $r['tabla'] ?? null, 'sql' => $r['sql'] ?? null]);
    }

    public function limpiar(): void
    {
        $this->guard(true); $_SESSION['chat'] = []; $this->out([]);
    }
}

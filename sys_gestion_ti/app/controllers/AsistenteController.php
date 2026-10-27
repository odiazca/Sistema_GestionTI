<?php
require_once ROOT_PATH . '/core/ia.php';

/** Asistente de reportes con IA (solo Administrador). Los endpoints responden JSON. */
class AsistenteController extends Controller
{
    private int $uid = 0;

    /** Respuesta JSON con tipos nativos */
    private function out(array $d, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($d + ['ok' => true], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    private function fail(string $msg, int $code = 400): void { $this->out(['ok' => false, 'error' => $msg], $code); }

    private function body(): array
    {
        static $b = null;
        if ($b === null) $b = json_decode((string)file_get_contents('php://input'), true) ?: [];
        return $b;
    }

    /** Solo administrador; las acciones que escriben exigen POST + CSRF */
    private function guard(bool $escribe = true): void
    {
        if (!Auth::check()) $this->fail('Tu sesión expiró. Vuelve a iniciar sesión.', 401);
        if (Auth::user()['rol'] !== 'Administrador') $this->fail('Solo el administrador puede usar el asistente de IA.', 403);
        if ($escribe && ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf((string)($this->body()['csrf'] ?? '')))) $this->fail('Solicitud no válida. Recarga la página.', 419);
        $this->uid = (int)Auth::user()['id'];
        try { ia_instalar(); } catch (Throwable $e) { $this->fail('No se pudieron preparar las vistas del asistente: ' . $e->getMessage(), 500); }
    }

    private function audit(string $accion, string $detalle): void
    {
        try { Database::conn()->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([$this->uid, $accion, mb_substr($detalle, 0, 190), 'bi-stars', '#7c3aed']); } catch (Throwable $e) {}
    }

    private function consulta(int $id): array
    {
        $s = Database::conn()->prepare('SELECT * FROM ia_consultas WHERE id=? AND usuario_id=?'); $s->execute([$id, $this->uid]);
        $c = $s->fetch(); if (!$c) $this->fail('Consulta no encontrada.', 404);
        return $c;
    }

    // ---------------------------------------------------------------- Página
    public function index(): void
    {
        $this->requireRole(['Administrador']);
        $this->render('asistente/index', ['scripts' => '<script src="' . asset('js/asistente.js') . '?v=' . APP_VERSION . '"></script>'], 'Asistente IA');
    }

    // ---------------------------------------------------------------- API
    public function estado(): void
    {
        $this->guard(false); $db = Database::conn(); $c = ia_cfg();
        $f = $db->prepare('SELECT id,pregunta,titulo FROM ia_consultas WHERE usuario_id=? AND favorito=1 ORDER BY ultimo_uso DESC'); $f->execute([$this->uid]);
        $h = $db->prepare('SELECT id,pregunta,titulo,favorito FROM ia_consultas WHERE usuario_id=? AND favorito=0 ORDER BY ultimo_uso DESC LIMIT 15'); $h->execute([$this->uid]);
        $this->out(['cfg' => ['proveedor' => $c['proveedor'], 'url' => $c['url'], 'modelo' => $c['modelo'], 'clave_puesta' => $c['clave'] !== '', 'resumen' => $c['resumen']],
                    'listo' => ia_listo($c), 'presets' => IA_PRESETS, 'favoritos' => $f->fetchAll(), 'historial' => $h->fetchAll()]);
    }

    public function guardar_config(): void
    {
        $this->guard(); $b = $this->body(); $p = (string)($b['proveedor'] ?? '');
        if (!isset(IA_PRESETS[$p])) $this->fail('Proveedor no válido.');
        $url = trim((string)($b['url'] ?? '')); $modelo = trim((string)($b['modelo'] ?? ''));
        if (!preg_match('#^https?://[^\s]+$#i', $url)) $this->fail('La URL debe empezar por http:// o https://');
        if ($modelo === '' || mb_strlen($modelo) > 100) $this->fail('Indica el nombre del modelo.');
        $k = trim((string)($b['clave'] ?? ''));
        if ($k !== '' && $k !== '__borrar__' && (mb_strlen($k) < 8 || mb_strlen($k) > 200 || preg_match('/\s/', $k))) $this->fail('La clave no parece válida.');
        ia_conf_set('ia_proveedor', $p); ia_conf_set('ia_url', $url); ia_conf_set('ia_modelo', $modelo);
        ia_conf_set('ia_resumen', !empty($b['resumen']) ? '1' : '0');
        if ($k === '__borrar__') ia_conf_set('ia_clave', ''); elseif ($k !== '') ia_conf_set('ia_clave', ia_cifrar($k));
        $this->audit('Configuró el Asistente IA', $p . ' / ' . $modelo);
        $this->out([]);
    }

    public function probar(): void
    {
        $this->guard();
        try { $t = ia_llamar([['role' => 'user', 'content' => 'Responde solo con la palabra OK.']], 20); } catch (Throwable $e) { $this->fail($e->getMessage()); }
        $this->out(['respuesta' => mb_substr(trim($t), 0, 60)]);
    }

    public function preguntar(): void
    {
        $this->guard(); $b = $this->body(); $pregunta = trim((string)($b['pregunta'] ?? ''));
        if (mb_strlen($pregunta) < 4) $this->fail('Escribe tu pregunta (mínimo 4 caracteres).');
        if (mb_strlen($pregunta) > 500) $this->fail('La pregunta es demasiado larga (máx. 500 caracteres).');
        $now = time(); $_SESSION['ia_hist'] = array_values(array_filter($_SESSION['ia_hist'] ?? [], fn($t) => $t > $now - 600));
        if (count($_SESSION['ia_hist']) >= 30) $this->fail('Demasiadas consultas seguidas. Espera unos minutos.', 429);
        $_SESSION['ia_hist'][] = $now;
        $previo = is_array($b['previo'] ?? null) ? $b['previo'] : null;
        try { $r = ia_preguntar($pregunta, $previo); } catch (Throwable $e) { $this->fail($e->getMessage()); }
        if (isset($r['mensaje'])) $this->out(['mensaje' => $r['mensaje']]);
        $db = Database::conn();
        $db->prepare('INSERT INTO ia_consultas (usuario_id,pregunta,titulo,sql_texto,grafico) VALUES (?,?,?,?,?)')->execute([$this->uid, $pregunta, $r['titulo'], $r['sql'], json_encode($r['grafico'])]);
        $id = (int)$db->lastInsertId();
        $db->prepare('DELETE FROM ia_consultas WHERE usuario_id=? AND favorito=0 AND id NOT IN (SELECT id FROM (SELECT id FROM ia_consultas WHERE usuario_id=? AND favorito=0 ORDER BY id DESC LIMIT 60) x)')->execute([$this->uid, $this->uid]);
        $this->audit('Consulta al Asistente IA', $pregunta);
        $this->out(['id' => $id] + $r);
    }

    /** Vuelve a ejecutar una consulta guardada, sin gastar IA */
    public function ejecutar(): void
    {
        $this->guard(); $c = $this->consulta((int)($this->body()['id'] ?? 0));
        try { $r = ia_ejecutar($c['sql_texto']); } catch (Throwable $e) { $this->fail($e->getMessage()); }
        Database::conn()->prepare('UPDATE ia_consultas SET veces=veces+1, ultimo_uso=NOW() WHERE id=?')->execute([$c['id']]);
        $this->out(['id' => (int)$c['id'], 'pregunta' => $c['pregunta'], 'titulo' => $c['titulo'] ?: $c['pregunta'], 'grafico' => json_decode((string)$c['grafico'], true) ?: ['tipo' => 'none'], 'favorito' => (int)$c['favorito']] + $r);
    }

    public function resumir(): void
    {
        $this->guard(); if (!ia_cfg()['resumen']) $this->fail('El resumen está desactivado.');
        $c = $this->consulta((int)($this->body()['id'] ?? 0));
        try {
            $r = ia_ejecutar($c['sql_texto']);
            if (!$r['filas']) $this->out(['resumen' => 'La consulta no devolvió resultados para ese período.']);
            $t = ia_resumir($c['pregunta'], $r['columnas'], $r['filas']);
        } catch (Throwable $e) { $this->fail($e->getMessage()); }
        $this->out(['resumen' => $t]);
    }

    public function favorito(): void
    {
        $this->guard(); $c = $this->consulta((int)($this->body()['id'] ?? 0)); $v = !empty($this->body()['valor']) ? 1 : 0;
        Database::conn()->prepare('UPDATE ia_consultas SET favorito=? WHERE id=?')->execute([$v, $c['id']]);
        $this->out(['favorito' => $v]);
    }

    public function borrar(): void
    {
        $this->guard(); $c = $this->consulta((int)($this->body()['id'] ?? 0));
        Database::conn()->prepare('DELETE FROM ia_consultas WHERE id=?')->execute([$c['id']]);
        $this->out([]);
    }

    public function csv(): void
    {
        $this->guard(false); $c = $this->consulta((int)($_GET['id'] ?? 0));
        try { $r = ia_ejecutar($c['sql_texto']); } catch (Throwable $e) { http_response_code(400); exit($e->getMessage()); }
        $name = 'consulta_' . preg_replace('/[^a-z0-9]+/i', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)($c['titulo'] ?: 'reporte'))) . '_' . date('Ymd') . '.csv';
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $name . '"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        $safe = fn($v) => is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false ? "'" . $v : $v;   // evita fórmulas en Excel
        fputcsv($o, $r['columnas'], ';'); foreach ($r['filas'] as $f) fputcsv($o, array_map($safe, $f), ';');
        $this->audit('Exportó consulta IA a CSV', (string)$c['pregunta']); exit;
    }

    public function esquema(): void
    {
        $this->guard(false);
        header('Content-Type: text/plain; charset=utf-8'); echo ia_esquema_texto(); exit;
    }
}

<?php
/** Constantes, helpers y controlador base del módulo CRM */
const CRM_ROLES = ['Administrador', 'Supervisor', 'Comercial'];
const LEAD_ORIGENES = ['web' => 'Sitio web', 'referido' => 'Referido', 'llamada' => 'Llamada', 'evento' => 'Evento', 'redes' => 'Redes sociales', 'correo' => 'Correo', 'otro' => 'Otro'];
const LEAD_ESTADOS = ['nuevo' => 'Nuevo', 'contactado' => 'Contactado', 'calificado' => 'Calificado', 'descartado' => 'Descartado', 'convertido' => 'Convertido'];
const OPP_TIPOS = ['contrato' => 'Contrato de soporte', 'renovacion' => 'Renovación', 'proyecto' => 'Proyecto / instalación', 'equipos' => 'Venta de equipos', 'servicio' => 'Servicio puntual'];
const ACT_TIPOS = [
    'llamada' => ['Llamada', 'bi-telephone-fill', '#0ea5e9'], 'reunion' => ['Reunión', 'bi-people-fill', '#8b5cf6'], 'correo' => ['Correo', 'bi-envelope-fill', '#f59e0b'],
    'tarea' => ['Tarea', 'bi-check2-square', '#4f46e5'], 'visita' => ['Visita', 'bi-geo-alt-fill', '#10b981'], 'nota' => ['Nota', 'bi-sticky-fill', '#64748b'],
];
const COT_ESTADOS = ['borrador' => 'Borrador', 'enviada' => 'Enviada', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'vencida' => 'Vencida'];

function lead_pill(string $e): string { return pill(LEAD_ESTADOS[$e] ?? $e, ['nuevo' => 'soft-primary', 'contactado' => 'soft-info', 'calificado' => 'success', 'descartado' => 'muted', 'convertido' => 'soft-warning'][$e] ?? 'muted'); }
function cot_pill(string $e): string { return pill(COT_ESTADOS[$e] ?? $e, ['borrador' => 'muted', 'enviada' => 'soft-info', 'aprobada' => 'success', 'rechazada' => 'danger', 'vencida' => 'warning'][$e] ?? 'muted'); }
function etapa_pill(string $nombre, string $color): string { return '<span class="pill" style="background:' . e($color) . '1f;color:' . e($color) . '"><i class="bi bi-circle-fill" style="font-size:.45rem;vertical-align:middle"></i> ' . e($nombre) . '</span>'; }
function score_bar(int $s): string
{
    $c = $s >= 70 ? '#10b981' : ($s >= 40 ? '#f59e0b' : '#94a3b8');
    return '<div class="score" title="Puntaje de calidad del lead"><div class="score-bar"><span style="width:' . $s . '%;background:' . $c . '"></span></div><b>' . $s . '</b></div>';
}
function act_icon(string $t): string { $a = ACT_TIPOS[$t] ?? ACT_TIPOS['nota']; return '<span class="tl-ic" style="background:' . $a[2] . '1f;color:' . $a[2] . '"><i class="bi ' . $a[1] . '"></i></span>'; }
function crm_user(): array { return Auth::user() ?: []; }

/** Puntaje de calidad de un lead (0–100) */
function lead_score(array $l, int $actividades = 0): int
{
    $s = ['referido' => 30, 'evento' => 20, 'web' => 15, 'llamada' => 15, 'correo' => 10, 'redes' => 10][$l['origen'] ?? 'otro'] ?? 5;
    if (!empty($l['email'])) $s += 10;
    if (!empty($l['telefono'])) $s += 10;
    if (!empty($l['interes'])) $s += 10;
    if (!empty($l['cargo']) && preg_match('/gerente|director|jefe|ti\b|sistemas|propietari|due[ñn]o|notari|administrador/i', $l['cargo'])) $s += 15;
    if (!empty($l['rubro'])) $s += 5;
    $s += min(20, $actividades * 7);
    if (($l['estado'] ?? '') === 'calificado') $s += 10;
    return min(100, $s);
}

abstract class CrmBase extends Controller
{
    protected function guard(): void { $this->requireRole(CRM_ROLES); }
    protected function esGestor(): bool { return in_array(Auth::user()['rol'] ?? '', ['Administrador', 'Supervisor'], true); }
    protected function db(): PDO { return Database::conn(); }
    protected function all(string $sql, array $p = []): array { $s = $this->db()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    protected function one(string $sql, array $p = []): ?array { $s = $this->db()->prepare($sql); $s->execute($p); return $s->fetch() ?: null; }
    protected function val(string $sql, array $p = []) { $s = $this->db()->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
    protected function run(string $sql, array $p = []): void { $this->db()->prepare($sql)->execute($p); }

    protected function vendedores(): array
    {
        $in = "'" . implode("','", CRM_ROLES) . "'";
        return $this->all("SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) AS nombre, u.avatar_color color FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE r.nombre IN ($in) AND u.estado=1 ORDER BY u.nombres");
    }
    protected function clientes(): array { return $this->all('SELECT id, razon_social FROM clientes WHERE estado=1 ORDER BY razon_social'); }
    protected function etapas(): array { return $this->all('SELECT * FROM crm_etapas ORDER BY orden'); }

    protected function codigo(string $tabla, string $pref): string
    {
        $n = (int)$this->val("SELECT COALESCE(MAX(id),0)+1 FROM $tabla");
        do { $c = sprintf('%s-%04d', $pref, $n++); } while ((int)$this->val("SELECT COUNT(*) FROM $tabla WHERE codigo=?", [$c]));
        return $c;
    }
    protected function audit(string $accion, string $detalle, string $icono = 'bi-graph-up-arrow', string $color = '#4f46e5'): void
    {
        try { $this->run('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)', [Auth::user()['id'], $accion, mb_substr($detalle, 0, 190), $icono, $color]); } catch (Throwable $e) {}
    }
    /** Nota automática en la línea de tiempo (cambios de etapa, conversiones, etc.) */
    protected function evento(string $texto, ?int $cliente = null, ?int $lead = null, ?int $op = null, string $tipo = 'nota'): void
    {
        $this->run('INSERT INTO crm_actividades(tipo,asunto,completada,completada_at,auto,cliente_id,lead_id,oportunidad_id,fecha,creado_por,responsable_id) VALUES(?,?,1,NOW(),1,?,?,?,NOW(),?,?)',
            [$tipo, mb_substr($texto, 0, 180), $cliente, $lead, $op, Auth::user()['id'], Auth::user()['id']]);
    }
    /** Cuerpo JSON de una petición AJAX con verificación CSRF */
    protected function jsonBody(): array
    {
        $this->requireAuth();
        $b = json_decode((string)file_get_contents('php://input'), true) ?: [];
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf((string)($b['csrf'] ?? ''))) $this->json(['ok' => false, 'error' => 'Solicitud no válida. Recarga la página.'], 419);
        if (!in_array(Auth::user()['rol'], CRM_ROLES, true)) $this->json(['ok' => false, 'error' => 'No tienes permiso.'], 403);
        return $b;
    }
    protected function str(string $k, int $max = 255): ?string { $v = trim((string)($_POST[$k] ?? '')); return $v === '' ? null : mb_substr($v, 0, $max); }
    protected function back(string $default): void
    {
        $r = (string)($_POST['volver'] ?? '');
        $this->redirect(preg_match('#^[a-z_]+(/[\w\-/]*)?(\?[\w=&%\-]*)?$#i', $r) ? $r : $default);
    }
    protected function csv(string $nombre, array $cols, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $nombre . '_' . date('Ymd') . '.csv"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        $safe = fn($v) => is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false ? "'" . $v : $v;
        fputcsv($o, $cols, ';'); foreach ($rows as $r) fputcsv($o, array_map($safe, array_values($r)), ';'); exit;
    }
}

/** Tarjeta de actividad (pendiente o realizada). $links=false oculta los vínculos a cliente/lead/oportunidad. */
function act_item(array $a, bool $links = true): string
{
    $t = ACT_TIPOS[$a['tipo']] ?? ACT_TIPOS['nota']; $auto = !empty($a['auto']); $done = !empty($a['completada']);
    $f = $a['fecha'] ?? null; $vencida = !$done && $f && strtotime($f) < time() - 3600;
    $hoy = !$done && $f && substr($f, 0, 10) === date('Y-m-d');
    $h = '<div class="act-it' . ($done ? ' done' : '') . ($vencida ? ' late' : '') . '"><span class="act-ic" style="background:' . $t[2] . '1f;color:' . $t[2] . '"><i class="bi ' . ($auto ? 'bi-lightning-charge-fill' : $t[1]) . '"></i></span><div class="act-body">';
    $h .= '<div class="act-top"><b>' . e($a['asunto']) . '</b>';
    if ($vencida) $h .= ' <span class="pill pill-danger">Vencida</span>'; elseif ($hoy) $h .= ' <span class="pill pill-soft-primary">Hoy</span>';
    $h .= '</div><div class="act-sub">';
    $h .= '<span><i class="bi ' . $t[1] . '"></i> ' . ($auto ? 'Sistema' : $t[0]) . '</span>';
    if ($f) $h .= '<span><i class="bi bi-clock"></i> ' . ($done ? fdate($a['completada_at'] ?? $f, 'd/m/Y H:i') : fdate($f, 'd/m/Y H:i')) . '</span>';
    if (!empty($a['responsable'])) $h .= '<span><i class="bi bi-person"></i> ' . e($a['responsable']) . '</span>';
    if ($links) {
        if (!empty($a['op_codigo'])) $h .= '<a href="' . url('oportunidades/ver/' . $a['oportunidad_id']) . '"><i class="bi bi-bullseye"></i> ' . e($a['op_codigo']) . '</a>';
        if (!empty($a['lead_nombre'])) $h .= '<a href="' . url('leads/ver/' . $a['lead_id']) . '"><i class="bi bi-person-plus"></i> ' . e($a['lead_nombre']) . '</a>';
        elseif (!empty($a['cliente'])) $h .= '<a href="' . url('clientes/ver/' . $a['cliente_id']) . '"><i class="bi bi-building"></i> ' . e($a['cliente']) . '</a>';
    }
    $h .= '</div>';
    if (!empty($a['detalle']) && !$auto) $h .= '<div class="act-det">' . nl2br(e($a['detalle'])) . '</div>';
    if ($done && !empty($a['resultado'])) $h .= '<div class="act-res"><i class="bi bi-check2-circle"></i> ' . nl2br(e($a['resultado'])) . '</div>';
    $h .= '</div>';
    if (!$done && !$auto) $h .= '<div class="act-actions"><button type="button" class="btn btn-soft btn-sm" data-completar="' . (int)$a['id'] . '" data-asunto="' . e($a['asunto']) . '" title="Completar"><i class="bi bi-check2"></i></button>'
        . '<button type="button" class="btn btn-ghost btn-sm" data-reprog="' . (int)$a['id'] . '" title="Reprogramar"><i class="bi bi-calendar2-plus"></i></button>'
        . '<button type="button" class="btn btn-ghost btn-sm text-danger" data-delact="' . (int)$a['id'] . '" title="Eliminar"><i class="bi bi-trash3"></i></button></div>';
    return $h . '</div>';
}

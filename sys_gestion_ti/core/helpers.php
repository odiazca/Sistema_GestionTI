<?php
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function asset(string $p): string { return BASE_URL . '/assets/' . ltrim($p, '/'); }
function iniciales(string $n): string {
    $p = preg_split('/\s+/', trim($n)); $i = mb_substr($p[0] ?? '', 0, 1);
    if (count($p) > 1) $i .= mb_substr(end($p), 0, 1);
    return mb_strtoupper($i);
}
function hace(string $fecha): string {
    $d = time() - strtotime($fecha);
    if ($d < 60) return 'ahora';
    if ($d < 3600) return 'hace ' . floor($d / 60) . ' min';
    if ($d < 86400) return 'hace ' . floor($d / 3600) . ' h';
    return 'hace ' . floor($d / 86400) . ' d';
}

function pill(string $text, string $class = 'muted'): string { return '<span class="pill pill-' . e($class) . '">' . e($text) . '</span>'; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(Auth::csrf()) . '">'; }
function fdate($d, ?string $fmt = null): string { return $d ? date($fmt ?? cfg('formato_fecha', 'd/m/Y'), strtotime($d)) : '—'; }
/** Formato numérico y de moneda configurable (Empresa → Moneda y números) */
function fmt_cfg(): array
{
    static $f = null;
    if ($f === null) {
        $dm = cfg('sep_decimal', '.'); $mi = cfg('sep_miles', ',');
        if ($mi === 'none') $mi = ''; elseif ($mi === 'space') $mi = ' ';
        $f = ['simbolo' => cfg('moneda_simbolo', 'S/'), 'codigo' => cfg('moneda_codigo', 'PEN'), 'pos' => cfg('moneda_pos', 'antes'), 'espacio' => cfg('moneda_espacio', '1') === '1',
              'dec' => max(0, min(4, (int)cfg('moneda_decimales', '2'))), 'sd' => $dm, 'sm' => $mi];
    }
    return $f;
}
/** Número con los separadores de la empresa */
function num($n, ?int $dec = null): string { $f = fmt_cfg(); return number_format((float)$n, $dec ?? $f['dec'], $f['sd'], $f['sm']); }
/** Importe con símbolo según la configuración de moneda */
function money($n, ?int $dec = null): string
{
    $f = fmt_cfg(); $v = num($n, $dec); $sp = $f['espacio'] ? "\u{00A0}" : '';
    return $f['pos'] === 'despues' ? $v . $sp . $f['simbolo'] : $f['simbolo'] . $sp . $v;
}
/** Logo de la empresa (URL con versión para caché) o null */
function logo_url(): ?string { return cfg('empresa_logo_ver') !== '' ? url('empresa/logo?v=' . cfg('empresa_logo_ver')) : null; }
/** Cuadro de marca: logo propio o ícono por defecto */
function brand_mark(string $style = ''): string
{
    $l = logo_url();
    return '<span class="brand-mark' . ($l ? ' has-logo' : '') . '"' . ($style ? ' style="' . $style . '"' : '') . '>' . ($l ? '<img src="' . e($l) . '" alt="">' : '<i class="bi bi-headset"></i>') . '</span>';
}
function empresa_nombre(): string { return cfg('empresa_nombre', APP_NAME); }

const PRIORIDADES = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Crítica'];
const ESTADOS_TK  = ['abierto' => 'Abierto', 'en_proceso' => 'En proceso', 'pendiente' => 'Pendiente', 'resuelto' => 'Resuelto', 'cerrado' => 'Cerrado'];
const CANALES     = ['web' => 'Portal web', 'correo' => 'Correo', 'telefono' => 'Teléfono', 'whatsapp' => 'WhatsApp', 'presencial' => 'Presencial'];

function prio_pill(string $p): string {
    $c = ['critica' => 'danger', 'alta' => 'warning', 'media' => 'info', 'baja' => 'muted'][$p] ?? 'muted';
    return pill(PRIORIDADES[$p] ?? $p, $c);
}
function estado_pill(string $e): string {
    $c = ['abierto' => 'soft-primary', 'en_proceso' => 'soft-info', 'pendiente' => 'soft-warning', 'resuelto' => 'success', 'cerrado' => 'muted'][$e] ?? 'muted';
    return pill(ESTADOS_TK[$e] ?? $e, $c);
}
function avatar_html(string $nombre, string $color = '#4f46e5', string $cls = ''): string {
    return '<span class="avatar ' . e($cls) . '" style="background:' . e($color) . '" title="' . e($nombre) . '">' . e(iniciales($nombre)) . '</span>';
}

/** Paginador que conserva los filtros de la URL */
function pager(array $pg): string {
    if ($pg['pages'] <= 1) return '';
    $q = $_GET; unset($q['url']);
    $link = function (int $p) use ($q) { $q['page'] = $p; return '?' . http_build_query($q); };
    $h = '<nav class="pager"><span class="pager-info">Mostrando ' . ($pg['offset'] + 1) . '–' . min($pg['offset'] + $pg['per'], $pg['total']) . ' de ' . $pg['total'] . '</span><ul>';
    $h .= '<li class="' . ($pg['page'] <= 1 ? 'disabled' : '') . '"><a href="' . e($link(max(1, $pg['page'] - 1))) . '"><i class="bi bi-chevron-left"></i></a></li>';
    $from = max(1, $pg['page'] - 2); $to = min($pg['pages'], $pg['page'] + 2);
    if ($from > 1) $h .= '<li><a href="' . e($link(1)) . '">1</a></li>' . ($from > 2 ? '<li class="gap">…</li>' : '');
    for ($i = $from; $i <= $to; $i++) $h .= '<li class="' . ($i == $pg['page'] ? 'active' : '') . '"><a href="' . e($link($i)) . '">' . $i . '</a></li>';
    if ($to < $pg['pages']) $h .= ($to < $pg['pages'] - 1 ? '<li class="gap">…</li>' : '') . '<li><a href="' . e($link($pg['pages'])) . '">' . $pg['pages'] . '</a></li>';
    $h .= '<li class="' . ($pg['page'] >= $pg['pages'] ? 'disabled' : '') . '"><a href="' . e($link(min($pg['pages'], $pg['page'] + 1))) . '"><i class="bi bi-chevron-right"></i></a></li></ul></nav>';
    return $h;
}

/** Estado del SLA de un ticket: [clase css, texto corto, texto largo] */
function sla_info(array $t): array
{
    $lim = strtotime($t['fecha_limite'] ?? '');
    if (!$lim) return ['muted', '—', 'Sin SLA'];
    if (in_array($t['estado'], ['resuelto', 'cerrado'])) {
        $res = strtotime($t['resuelto_at'] ?? '') ?: $lim;
        return $res <= $lim ? ['ok', 'Cumplido', 'Resuelto dentro del plazo'] : ['bad', 'Fuera de SLA', 'Resuelto fuera del plazo'];
    }
    $d = $lim - time();
    $txt = function (int $s): string { $s = abs($s); $h = intdiv($s, 3600); $m = intdiv($s % 3600, 60); return $h >= 24 ? intdiv($h, 24) . 'd ' . ($h % 24) . 'h' : ($h ? "{$h}h {$m}m" : "{$m}m"); };
    if ($d < 0) return ['bad', 'Vencido', 'Vencido hace ' . $txt($d)];
    $tot = max(1, $lim - strtotime($t['created_at']));
    return [$d / $tot < 0.25 ? 'warn' : 'ok', $txt($d), 'Vence en ' . $txt($d)];
}


/** Lee un parámetro de la tabla configuracion (con caché por petición) */
function cfg(string $clave, $defecto = '')
{
    static $c = null;
    if ($c === null) {
        try { $c = array_column(Database::conn()->query('SELECT clave, valor FROM configuracion WHERE clave <> \'empresa_logo_data\'')->fetchAll(PDO::FETCH_NUM), 1, 0); }
        catch (Throwable $e) { $c = []; }
    }
    return isset($c[$clave]) && $c[$clave] !== '' ? $c[$clave] : $defecto;
}

/** Colores de marca [principal, secundario] o null si es el índigo por defecto */
function EmpresaController_colores(string $k): ?array
{
    $m = ['azul' => ['#2563eb', '#3b82f6'], 'cian' => ['#0891b2', '#06b6d4'], 'esmeralda' => ['#059669', '#10b981'], 'violeta' => ['#7c3aed', '#8b5cf6'], 'rosa' => ['#e11d48', '#f43f5e'], 'naranja' => ['#ea580c', '#f97316'], 'pizarra' => ['#334155', '#475569']];
    return $m[$k] ?? null;
}
function doc_label(): string { return cfg('doc_etiqueta', 'RUC'); }
function impuesto_nombre(): string { return cfg('impuesto_nombre', 'IGV'); }

/** <style> con los colores de marca elegidos en Empresa (vacío si es el índigo por defecto) */
function brand_css(): string
{
    $c = EmpresaController_colores(cfg('color_marca', 'indigo')); if (!$c) return '';
    return '<style>:root{--brand:' . $c[0] . ';--brand-2:' . $c[1] . ';--brand-soft:color-mix(in srgb,' . $c[0] . ' 11%,transparent)}[data-theme=dark]{--brand-soft:color-mix(in srgb,' . $c[1] . ' 18%,transparent)}</style>';
}

<?php
/** Datos de la empresa, logo, moneda, formatos regionales y apariencia (solo Administrador) */
class EmpresaController extends Controller
{
    const TABS = ['datos' => ['Datos de la empresa', 'bi-building'], 'moneda' => ['Moneda y números', 'bi-cash-coin'], 'regional' => ['Regional y operación', 'bi-globe-americas'], 'apariencia' => ['Apariencia y documentos', 'bi-palette']];
    const MONEDAS = [ // código => [nombre, símbolo, decimales, sep. decimal, sep. miles, posición]
        'PEN' => ['Sol peruano', 'S/', 2, '.', ',', 'antes'], 'USD' => ['Dólar estadounidense', '$', 2, '.', ',', 'antes'], 'EUR' => ['Euro', '€', 2, ',', '.', 'despues'],
        'MXN' => ['Peso mexicano', '$', 2, '.', ',', 'antes'], 'COP' => ['Peso colombiano', '$', 0, ',', '.', 'antes'], 'CLP' => ['Peso chileno', '$', 0, ',', '.', 'antes'],
        'ARS' => ['Peso argentino', '$', 2, ',', '.', 'antes'], 'BOB' => ['Boliviano', 'Bs', 2, ',', '.', 'antes'], 'UYU' => ['Peso uruguayo', '$U', 2, ',', '.', 'antes'],
        'PYG' => ['Guaraní', '₲', 0, ',', '.', 'antes'], 'BRL' => ['Real brasileño', 'R$', 2, ',', '.', 'antes'], 'GTQ' => ['Quetzal', 'Q', 2, '.', ',', 'antes'],
        'DOP' => ['Peso dominicano', 'RD$', 2, '.', ',', 'antes'], 'CRC' => ['Colón costarricense', '₡', 2, ',', '.', 'antes'], 'PAB' => ['Balboa', 'B/.', 2, '.', ',', 'antes'],
    ];
    const FECHAS = ['d/m/Y' => '31/12/2026', 'd-m-Y' => '31-12-2026', 'Y-m-d' => '2026-12-31', 'm/d/Y' => '12/31/2026', 'd.m.Y' => '31.12.2026'];
    const COLORES = ['indigo' => ['Índigo', '#4f46e5', '#6366f1'], 'azul' => ['Azul', '#2563eb', '#3b82f6'], 'cian' => ['Cian', '#0891b2', '#06b6d4'], 'esmeralda' => ['Esmeralda', '#059669', '#10b981'],
        'violeta' => ['Violeta', '#7c3aed', '#8b5cf6'], 'rosa' => ['Rosa', '#e11d48', '#f43f5e'], 'naranja' => ['Naranja', '#ea580c', '#f97316'], 'pizarra' => ['Pizarra', '#334155', '#475569']];
    const DOCS = ['RUC', 'NIT', 'RFC', 'CUIT', 'RUT', 'NIF', 'CIF', 'ID fiscal'];

    private function guard(): void { $this->requireRole(['Administrador']); }
    private function set(string $k, string $v): void { Database::conn()->prepare('INSERT INTO configuracion(clave,valor) VALUES(?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)')->execute([$k, $v]); }

    public function index(): void
    {
        $this->guard(); $tab = isset(self::TABS[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'datos';
        $zonas = []; foreach (DateTimeZone::listIdentifiers() as $z) { $p = explode('/', $z, 2); $zonas[count($p) > 1 ? $p[0] : 'Otras'][] = $z; }
        $this->render('empresa/index', ['tab' => $tab, 'tabs' => self::TABS, 'zonas' => $zonas], 'Empresa');
    }

    public function guardar(): void
    {
        $this->guard(); $tab = $_POST['tab'] ?? 'datos'; $this->requirePost('empresa?tab=' . $tab); $back = 'empresa?tab=' . $tab; $P = fn($k) => trim((string)($_POST[$k] ?? ''));
        $len = fn($s, $n) => mb_substr($s, 0, $n);
        if ($tab === 'datos') {
            if ($P('empresa_nombre') === '') { $this->flash('danger', 'El nombre de la empresa es obligatorio.'); $this->redirect($back); }
            if ($P('empresa_email') !== '' && !filter_var($P('empresa_email'), FILTER_VALIDATE_EMAIL)) { $this->flash('danger', 'El correo de la empresa no es válido.'); $this->redirect($back); }
            $web = $P('empresa_web'); if ($web !== '' && !preg_match('#^https?://#i', $web)) $web = 'https://' . $web;
            foreach (['empresa_nombre' => 100, 'empresa_razon_social' => 150, 'empresa_ruc' => 20, 'empresa_giro' => 100, 'empresa_direccion' => 200, 'empresa_ciudad' => 80, 'empresa_pais' => 60, 'empresa_telefono' => 30, 'empresa_email' => 120, 'empresa_eslogan' => 60] as $k => $n) $this->set($k, $len($P($k), $n));
            $this->set('empresa_web', $len($web, 150)); $this->set('doc_etiqueta', in_array($P('doc_etiqueta'), self::DOCS, true) ? $P('doc_etiqueta') : 'RUC');
        } elseif ($tab === 'moneda') {
            $sd = $P('sep_decimal'); $sm = $P('sep_miles'); $dec = (int)$P('moneda_decimales'); $sim = $len($P('moneda_simbolo'), 6);
            if ($sim === '') { $this->flash('danger', 'Indica el símbolo de la moneda.'); $this->redirect($back); }
            if (!in_array($sd, ['.', ','], true) || !in_array($sm, [',', '.', 'space', "'", 'none'], true)) { $this->flash('danger', 'Separadores no válidos.'); $this->redirect($back); }
            if ($dec < 0 || $dec > 4) { $this->flash('danger', 'Los decimales deben estar entre 0 y 4.'); $this->redirect($back); }
            if ($sm === $sd && $dec > 0) { $this->flash('danger', 'El separador de miles no puede ser igual al decimal.'); $this->redirect($back); }
            $this->set('moneda_codigo', isset(self::MONEDAS[$P('moneda_codigo')]) ? $P('moneda_codigo') : $len(strtoupper($P('moneda_codigo')), 3)); $this->set('moneda_simbolo', $sim); $this->set('moneda_decimales', (string)$dec);
            $this->set('sep_decimal', $sd); $this->set('sep_miles', $sm); $this->set('moneda_pos', $P('moneda_pos') === 'despues' ? 'despues' : 'antes'); $this->set('moneda_espacio', !empty($_POST['moneda_espacio']) ? '1' : '0');
        } elseif ($tab === 'regional') {
            if (!in_array($P('zona_horaria'), DateTimeZone::listIdentifiers(), true)) { $this->flash('danger', 'Zona horaria no válida.'); $this->redirect($back); }
            if (!isset(self::FECHAS[$P('formato_fecha')])) { $this->flash('danger', 'Formato de fecha no válido.'); $this->redirect($back); }
            foreach (['tarifa_hora', 'igv'] as $k) if ($P($k) === '' || !is_numeric($P($k)) || (float)$P($k) < 0) { $this->flash('danger', 'Ingresa valores numéricos válidos para la tarifa y el impuesto.'); $this->redirect($back); }
            $this->set('zona_horaria', $P('zona_horaria')); $this->set('formato_fecha', $P('formato_fecha')); $this->set('tarifa_hora', $P('tarifa_hora')); $this->set('igv', $P('igv')); $this->set('impuesto_nombre', $len($P('impuesto_nombre') ?: 'IGV', 12)); $this->set('horario_atencion', $len($P('horario_atencion'), 120));
        } elseif ($tab === 'apariencia') {
            $this->set('color_marca', isset(self::COLORES[$P('color_marca')]) ? $P('color_marca') : 'indigo'); $this->set('pie_documentos', $len($P('pie_documentos'), 300));
        } else $this->redirect('empresa');
        $this->flash('success', 'Cambios guardados.'); $this->redirect($back);
    }

    /** Imagen del logo: pública (la usan el login y el portal) */
    public function logo(): void
    {
        $s = Database::conn()->query("SELECT clave, valor FROM configuracion WHERE clave IN ('empresa_logo_data','empresa_logo_mime','empresa_logo_ver')")->fetchAll(PDO::FETCH_KEY_PAIR);
        if (empty($s['empresa_logo_data'])) { http_response_code(404); exit; }
        $etag = '"' . ($s['empresa_logo_ver'] ?? '0') . '"'; header('ETag: ' . $etag); header('Cache-Control: public, max-age=86400');
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
        $img = base64_decode($s['empresa_logo_data']); header('Content-Type: ' . ($s['empresa_logo_mime'] ?: 'image/png')); header('Content-Length: ' . strlen($img)); header('X-Content-Type-Options: nosniff'); echo $img; exit;
    }

    public function logo_subir(): void
    {
        $this->guard(); $this->requirePost('empresa'); $f = $_FILES['logo'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) { $this->flash('danger', 'Selecciona una imagen (PNG, JPG o WebP) de hasta 2 MB.'); $this->redirect('empresa'); }
        if ($f['size'] > 2 * 1024 * 1024) { $this->flash('danger', 'La imagen supera los 2 MB.'); $this->redirect('empresa'); }
        $info = @getimagesize($f['tmp_name']); $raw = file_get_contents($f['tmp_name']);
        if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)) { $this->flash('danger', 'Formato no admitido. Usa PNG, JPG o WebP.'); $this->redirect('empresa'); }
        $mime = $info['mime'];
        if (function_exists('imagecreatefromstring') && ($im = @imagecreatefromstring($raw))) {
            $w = imagesx($im); $h = imagesy($im); $k = min(1, 480 / max($w, $h)); $nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k));
            $dst = imagecreatetruecolor($nw, $nh); imagealphablending($dst, false); imagesavealpha($dst, true); imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
            imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h); ob_start(); imagepng($dst, null, 7); $raw = ob_get_clean(); $mime = 'image/png';
        } elseif (strlen($raw) > 400 * 1024) { $this->flash('danger', 'Este servidor no puede reducir imágenes; sube un logo de hasta 400 KB.'); $this->redirect('empresa'); }
        $this->set('empresa_logo_data', base64_encode($raw)); $this->set('empresa_logo_mime', $mime); $this->set('empresa_logo_ver', (string)time());
        $this->flash('success', 'Logo actualizado.'); $this->redirect('empresa');
    }

    public function logo_quitar(): void
    {
        $this->guard(); $this->requirePost('empresa');
        Database::conn()->exec("DELETE FROM configuracion WHERE clave IN ('empresa_logo_data','empresa_logo_mime','empresa_logo_ver')");
        $this->flash('success', 'Logo eliminado.'); $this->redirect('empresa');
    }
}

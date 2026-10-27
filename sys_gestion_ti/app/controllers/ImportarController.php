<?php
require_once ROOT_PATH . '/core/CrmBase.php';

/** Carga masiva desde Excel (.xlsx) o CSV: clientes, leads y equipos, con vista previa y validación */
class ImportarController extends Controller
{
    private const MAX_FILAS = 2000;
    private const TIPOS = [
        'clientes' => ['Clientes', 'bi-buildings-fill', '#4f46e5', ['razon_social' => ['Razón social', true], 'ruc' => ['RUC', false], 'rubro' => ['Rubro', false], 'contacto' => ['Contacto', false], 'email' => ['Correo', false], 'telefono' => ['Teléfono', false], 'direccion' => ['Dirección', false]],
            ['Tecnologías Andinas SAC', '20512345678', 'Tecnología', 'Rosa Quispe', 'rquispe@andinas.pe', '987654321', 'Av. Larco 123, Miraflores']],
        'leads' => ['Leads', 'bi-person-plus-fill', '#8b5cf6', ['empresa' => ['Empresa', true], 'contacto' => ['Contacto', false], 'cargo' => ['Cargo', false], 'email' => ['Correo', false], 'telefono' => ['Teléfono', false], 'rubro' => ['Rubro', false], 'origen' => ['Origen', false], 'interes' => ['Interés', false], 'notas' => ['Notas', false]],
            ['Clínica San Martín', 'Luis Ramos', 'Gerente TI', 'lramos@sanmartin.pe', '999111222', 'Salud', 'referido', 'Cableado y servidor', 'Pidió propuesta']],
        'activos' => ['Equipos (activos)', 'bi-pc-display-horizontal', '#0ea5e9', ['cliente' => ['Cliente (razón social o RUC)', true], 'tipo' => ['Tipo', true], 'marca' => ['Marca', false], 'modelo' => ['Modelo', false], 'serie' => ['N.º de serie', false], 'ubicacion' => ['Ubicación', false], 'fecha_compra' => ['Fecha de compra', false], 'garantia_hasta' => ['Garantía hasta', false]],
            ['Tecnologías Andinas SAC', 'Laptop', 'Lenovo', 'ThinkPad E14', 'PF3ABC12', 'Oficina 2', '2025-03-15', '2028-03-15']],
    ];

    private function guard(?string $tipo = null): void
    {
        $roles = $tipo === 'leads' ? ['Administrador', 'Supervisor', 'Comercial'] : ['Administrador', 'Supervisor'];
        $this->requireRole($tipo ? $roles : ['Administrador', 'Supervisor', 'Comercial']);
    }
    private function visibles(): array
    {
        return array_filter(self::TIPOS, fn($k) => in_array(Auth::user()['rol'], $k === 'leads' ? ['Administrador', 'Supervisor', 'Comercial'] : ['Administrador', 'Supervisor'], true), ARRAY_FILTER_USE_KEY);
    }

    public function index(): void
    {
        $this->guard(); $this->render('importar/index', ['tipos' => $this->visibles()], 'Importar datos');
    }

    public function plantilla($tipo = ''): void
    {
        $this->guard($tipo); if (!isset(self::TIPOS[$tipo])) $this->redirect('importar');
        $t = self::TIPOS[$tipo]; header('Content-Type: text/csv; charset=UTF-8'); header("Content-Disposition: attachment; filename=plantilla_$tipo.csv");
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, array_map(fn($c) => $c[0] . ($c[1] ? ' *' : ''), array_values($t[3])), ';'); fputcsv($o, $t[4], ';'); fclose($o); exit;
    }

    // ---- lectura de archivos -------------------------------------------------
    private function norm(string $s): string
    {
        $s = mb_strtolower(trim($s)); $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
        return preg_replace('/[^a-z0-9]+/', '', $s);
    }
    private function leerCsv(string $f): array
    {
        $txt = file_get_contents($f); if (substr($txt, 0, 3) === "\xEF\xBB\xBF") $txt = substr($txt, 3);
        if (!mb_check_encoding($txt, 'UTF-8')) $txt = mb_convert_encoding($txt, 'UTF-8', 'Windows-1252');
        $l1 = strtok($txt, "\n"); $sep = [';' => substr_count($l1, ';'), ',' => substr_count($l1, ','), "\t" => substr_count($l1, "\t")]; arsort($sep); $sep = array_key_first($sep);
        $h = fopen('php://temp', 'r+'); fwrite($h, $txt); rewind($h); $rows = [];
        while (($r = fgetcsv($h, 0, $sep)) !== false) { $rows[] = $r; if (count($rows) > self::MAX_FILAS + 1) break; } fclose($h); return $rows;
    }
    private function colIdx(string $ref): int { $n = 0; foreach (str_split(preg_replace('/\d+/', '', $ref)) as $ch) $n = $n * 26 + ord($ch) - 64; return $n - 1; }
    private function leerXlsx(string $f): array
    {
        $z = new ZipArchive(); if ($z->open($f) !== true) throw new RuntimeException('El archivo .xlsx está dañado.');
        $shared = []; if (($x = $z->getFromName('xl/sharedStrings.xml')) !== false) { $s = @simplexml_load_string($x); if ($s) foreach ($s->si as $si) { $t = ''; if (isset($si->t)) $t = (string)$si->t; else foreach ($si->r as $r) $t .= (string)$r->t; $shared[] = $t; } }
        $sheet = null; foreach (['xl/worksheets/sheet1.xml'] as $n) if (($x = $z->getFromName($n)) !== false) $sheet = $x;
        if ($sheet === null) for ($i = 0; $i < $z->numFiles; $i++) { $n = $z->getNameIndex($i); if (preg_match('#^xl/worksheets/[^/]+\.xml$#', $n)) { $sheet = $z->getFromName($n); break; } }
        $z->close(); if ($sheet === null) throw new RuntimeException('No se encontró ninguna hoja en el archivo.');
        $s = @simplexml_load_string($sheet); if (!$s) throw new RuntimeException('No se pudo leer la hoja de cálculo.'); $rows = [];
        foreach ($s->sheetData->row as $row) {
            $r = []; foreach ($row->c as $c) {
                $i = $this->colIdx((string)$c['r']); $t = (string)$c['t']; $v = '';
                if ($t === 's') $v = $shared[(int)$c->v] ?? ''; elseif ($t === 'inlineStr') $v = (string)$c->is->t; else $v = (string)$c->v;
                $r[$i] = $v;
            }
            if ($r) { $max = max(array_keys($r)); $o = []; for ($i = 0; $i <= $max; $i++) $o[] = $r[$i] ?? ''; $rows[] = $o; }
            if (count($rows) > self::MAX_FILAS + 1) break;
        }
        return $rows;
    }
    private function fecha(string $v): ?string
    {
        $v = trim($v); if ($v === '') return null;
        if (is_numeric($v) && $v > 20000 && $v < 80000) return gmdate('Y-m-d', (int)(($v - 25569) * 86400));
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $v, $m)) return checkdate((int)$m[2], (int)$m[1], (int)$m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : '!';
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})#', $v, $m)) return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? "$m[1]-$m[2]-$m[3]" : '!';
        return '!';
    }

    // ---- validación ----------------------------------------------------------
    private function validar(string $tipo, array $d, array &$vistos): array
    {
        $db = Database::conn(); $err = []; $campos = self::TIPOS[$tipo][3];
        foreach ($campos as $k => [$l, $req]) { $d[$k] = trim((string)($d[$k] ?? '')); if ($req && $d[$k] === '') $err[] = "Falta $l"; }
        foreach (['email'] as $k) if (($d[$k] ?? '') !== '' && !filter_var($d[$k], FILTER_VALIDATE_EMAIL)) $err[] = 'Correo no válido';
        if (($d['ruc'] ?? '') !== '' && !preg_match('/^\d{8,11}$/', $d['ruc'])) $err[] = 'RUC/documento debe tener 8 a 11 dígitos';
        if ($tipo === 'clientes' && !$err) {
            $k = $d['ruc'] !== '' ? 'r' . $d['ruc'] : 'n' . mb_strtolower($d['razon_social']);
            $ex = $db->prepare('SELECT id FROM clientes WHERE ' . ($d['ruc'] !== '' ? 'ruc=?' : 'LOWER(razon_social)=?')); $ex->execute([$d['ruc'] !== '' ? $d['ruc'] : mb_strtolower($d['razon_social'])]);
            if ($ex->fetch()) $err[] = 'Ya existe un cliente igual'; elseif (isset($vistos[$k])) $err[] = 'Repetido en el archivo'; $vistos[$k] = 1;
        }
        if ($tipo === 'leads') {
            $o = $this->norm($d['origen'] ?? ''); $map = []; foreach (LEAD_ORIGENES as $k => $l) { $map[$this->norm($k)] = $k; $map[$this->norm($l)] = $k; } $d['origen'] = $map[$o] ?? 'otro';
            if (!$err) { $k = ($d['email'] !== '' ? 'e' . mb_strtolower($d['email']) : 'n' . mb_strtolower($d['empresa']));
                $ex = $db->prepare('SELECT id FROM crm_leads WHERE ' . ($d['email'] !== '' ? 'LOWER(email)=?' : 'LOWER(empresa)=?')); $ex->execute([$d['email'] !== '' ? mb_strtolower($d['email']) : mb_strtolower($d['empresa'])]);
                if ($ex->fetch()) $err[] = 'Ya existe un lead igual'; elseif (isset($vistos[$k])) $err[] = 'Repetido en el archivo'; $vistos[$k] = 1; }
        }
        if ($tipo === 'activos') {
            foreach (['fecha_compra', 'garantia_hasta'] as $f) { $r = $this->fecha($d[$f] ?? ''); if ($r === '!') $err[] = 'Fecha no válida en ' . $campos[$f][0]; $d[$f] = $r === '!' ? null : $r; }
            if ($d['cliente'] !== '') { $c = $db->prepare('SELECT id FROM clientes WHERE LOWER(razon_social)=? OR ruc=? LIMIT 1'); $c->execute([mb_strtolower($d['cliente']), $d['cliente']]); $cid = $c->fetchColumn(); if (!$cid) $err[] = 'Cliente no encontrado'; else $d['cliente_id'] = (int)$cid; }
            if (!$err && $d['serie'] !== '') { $k = 's' . mb_strtolower($d['serie']); $ex = $db->prepare('SELECT id FROM activos WHERE cliente_id=? AND serie=?'); $ex->execute([$d['cliente_id'], $d['serie']]); if ($ex->fetch()) $err[] = 'Ya existe un equipo con esa serie'; elseif (isset($vistos[$k])) $err[] = 'Serie repetida en el archivo'; $vistos[$k] = 1; }
        }
        foreach (['razon_social' => 150, 'empresa' => 150, 'tipo' => 40, 'marca' => 40, 'modelo' => 60, 'serie' => 60] as $k => $m) if (isset($d[$k])) $d[$k] = mb_substr($d[$k], 0, $m);
        return [$d, $err];
    }

    public function subir(): void
    {
        $tipo = $_POST['tipo'] ?? ''; $this->guard($tipo); $this->requirePost('importar'); if (!isset(self::TIPOS[$tipo])) $this->redirect('importar');
        $f = $_FILES['archivo'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1024 * 1024) { $this->flash('danger', 'Selecciona un archivo .xlsx o .csv de hasta 5 MB.'); $this->redirect('importar'); }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        try {
            if ($ext === 'xlsx') { if (!class_exists('ZipArchive')) throw new RuntimeException('Este servidor no tiene la extensión ZIP; guarda el Excel como CSV e inténtalo otra vez.'); $filas = $this->leerXlsx($f['tmp_name']); }
            elseif (in_array($ext, ['csv', 'txt'], true)) $filas = $this->leerCsv($f['tmp_name']);
            else throw new RuntimeException('Formato no admitido. Usa .xlsx o .csv (puedes guardar tu Excel como CSV).');
        } catch (Throwable $e) { $this->flash('danger', $e->getMessage()); $this->redirect('importar'); }
        $filas = array_values(array_filter($filas, fn($r) => trim(implode('', $r)) !== ''));
        if (count($filas) < 2) { $this->flash('warning', 'El archivo no tiene filas de datos debajo del encabezado.'); $this->redirect('importar'); }
        $campos = self::TIPOS[$tipo][3]; $alias = [];
        foreach ($campos as $k => [$l]) { $alias[$this->norm($k)] = $k; $alias[$this->norm(str_replace(' *', '', $l))] = $k; }
        $alias += ['razonsocial' => 'razon_social', 'nombre' => $tipo === 'leads' ? 'empresa' : 'razon_social', 'ndeserie' => 'serie', 'nserie' => 'serie', 'numeroserie' => 'serie', 'correo' => 'email', 'mail' => 'email', 'celular' => 'telefono', 'cliente' => 'cliente', 'garantia' => 'garantia_hasta', 'clienterazonsocialoruc' => 'cliente', 'documento' => 'ruc'];
        $map = []; foreach ($filas[0] as $i => $h) { $n = $this->norm(str_replace('*', '', (string)$h)); if (isset($alias[$n]) && !in_array($alias[$n], $map, true)) $map[$i] = $alias[$n]; }
        $faltan = []; foreach ($campos as $k => [$l, $req]) if ($req && !in_array($k, $map, true)) $faltan[] = $l;
        if ($faltan) { $this->flash('danger', 'No se encontró la columna obligatoria: ' . implode(', ', $faltan) . '. Descarga la plantilla para ver los nombres esperados.'); $this->redirect('importar'); }
        $vistos = []; $res = []; $ok = 0;
        foreach (array_slice($filas, 1, self::MAX_FILAS) as $n => $r) {
            $d = []; foreach ($map as $i => $k) $d[$k] = trim((string)($r[$i] ?? ''));
            [$d, $err] = $this->validar($tipo, $d, $vistos); $res[] = ['n' => $n + 2, 'd' => $d, 'err' => $err]; if (!$err) $ok++;
        }
        $_SESSION['import'] = ['tipo' => $tipo, 'filas' => array_map(fn($x) => $x['d'], array_filter($res, fn($x) => !$x['err'])), 'nombre' => $f['name'], 't' => time()];
        $this->render('importar/preview', ['tipo' => $tipo, 'def' => self::TIPOS[$tipo], 'res' => $res, 'ok' => $ok, 'nombre' => $f['name'], 'cols' => array_values(array_filter(array_keys($campos), fn($k) => in_array($k, $map, true)))], 'Vista previa de importación');
    }

    public function confirmar(): void
    {
        $im = $_SESSION['import'] ?? null; $this->guard($im['tipo'] ?? 'clientes'); $this->requirePost('importar');
        if (!$im || time() - $im['t'] > 1800 || !$im['filas']) { unset($_SESSION['import']); $this->flash('warning', 'No hay datos pendientes de importar (o la vista previa expiró). Sube el archivo otra vez.'); $this->redirect('importar'); }
        $tipo = $im['tipo']; $db = Database::conn(); $uid = (int)Auth::user()['id']; $n = 0;
        try {
            $db->beginTransaction();
            foreach ($im['filas'] as $d) {
                if ($tipo === 'clientes') { $db->prepare('INSERT INTO clientes(razon_social,ruc,rubro,direccion,telefono,email,contacto) VALUES(?,?,?,?,?,?,?)')->execute([$d['razon_social'], $d['ruc'] ?: null, $d['rubro'] ?? null ?: null, $d['direccion'] ?? null ?: null, $d['telefono'] ?? null ?: null, $d['email'] ?? null ?: null, $d['contacto'] ?? null ?: null]); }
                elseif ($tipo === 'leads') {
                    $c = (int)$db->query('SELECT COALESCE(MAX(id),0)+1 FROM crm_leads')->fetchColumn(); do { $cod = sprintf('LD-%04d', $c++); $e = $db->prepare('SELECT COUNT(*) FROM crm_leads WHERE codigo=?'); $e->execute([$cod]); } while ((int)$e->fetchColumn());
                    $sc = lead_score($d + ['estado' => 'nuevo']);
                    $db->prepare('INSERT INTO crm_leads(codigo,empresa,contacto,cargo,email,telefono,rubro,origen,interes,notas,estado,score,responsable_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$cod, $d['empresa'], $d['contacto'] ?: null, $d['cargo'] ?: null, $d['email'] ?: null, $d['telefono'] ?: null, $d['rubro'] ?: null, $d['origen'], $d['interes'] ?: null, $d['notas'] ?: null, 'nuevo', $sc, $uid]);
                } else {
                    $c = (int)$db->query('SELECT COALESCE(MAX(id),0)+1 FROM activos')->fetchColumn(); do { $cod = sprintf('ACT-%04d', $c++); $e = $db->prepare('SELECT COUNT(*) FROM activos WHERE codigo=?'); $e->execute([$cod]); } while ((int)$e->fetchColumn());
                    $db->prepare("INSERT INTO activos(cliente_id,codigo,tipo,marca,modelo,serie,ubicacion,fecha_compra,garantia_hasta,estado) VALUES(?,?,?,?,?,?,?,?,?,'operativo')")->execute([$d['cliente_id'], $cod, $d['tipo'], $d['marca'] ?: null, $d['modelo'] ?: null, $d['serie'] ?: null, $d['ubicacion'] ?: null, $d['fecha_compra'] ?: null, $d['garantia_hasta'] ?: null]);
                }
                $n++;
            }
            $db->commit();
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); $this->flash('danger', 'La importación se canceló y no se guardó nada: ' . $e->getMessage()); $this->redirect('importar'); }
        unset($_SESSION['import']);
        $db->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([$uid, 'Importación de ' . self::TIPOS[$tipo][0], "$n registros desde " . mb_substr($im['nombre'], 0, 120), 'bi-file-earmark-arrow-up-fill', '#0ea5e9']);
        $this->flash('success', "Se importaron $n registros de " . mb_strtolower(self::TIPOS[$tipo][0]) . '.'); $this->redirect($tipo === 'activos' ? 'activos' : $tipo);
    }
}

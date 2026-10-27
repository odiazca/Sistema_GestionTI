<?php
require_once ROOT_PATH . '/core/Respaldo.php';

/** Copias de seguridad, restauración y reinicio del sistema (solo Administrador) */
class RespaldoController extends Controller
{
    private function guard(): void { $this->requireRole(['Administrador']); }
    private function db(): PDO { return Database::conn(); }
    private function conf(string $k, string $v): void { $this->db()->prepare('INSERT INTO configuracion(clave,valor) VALUES(?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)')->execute([$k, $v]); }
    private function log(string $a, string $d, string $ic = 'bi-hdd-fill', string $c = '#64748b'): void { try { $this->db()->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([Auth::user()['id'], $a, mb_substr($d, 0, 190), $ic, $c]); } catch (Throwable $e) {} }
    /** Doble confirmación: contraseña del administrador + frase escrita */
    private function confirmado(string $frase, string $volver): void
    {
        $s = $this->db()->prepare('SELECT password FROM usuarios WHERE id=?'); $s->execute([Auth::user()['id']]);
        if (trim($_POST['frase'] ?? '') !== $frase) { $this->flash('danger', "Escribe exactamente «$frase» para confirmar."); $this->redirect($volver); }
        if (!password_verify($_POST['clave'] ?? '', (string)$s->fetchColumn())) { $this->flash('danger', 'La contraseña no es correcta. No se hizo ningún cambio.'); $this->redirect($volver); }
    }

    public function index(): void
    {
        $this->guard(); $db = $this->db(); $tab = in_array($_GET['tab'] ?? '', ['restaurar', 'reiniciar'], true) ? $_GET['tab'] : 'copias';
        $lista = Respaldo::listar();
        $tablas = (int)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE'")->fetchColumn();
        $filas = (int)$db->query("SELECT COALESCE(SUM(table_rows),0) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_type='BASE TABLE'")->fetchColumn();
        $mb = round((float)$db->query("SELECT COALESCE(SUM(data_length+index_length),0)/1024/1024 FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchColumn(), 2);
        $max = min(self::bytes(ini_get('upload_max_filesize')), self::bytes(ini_get('post_max_size')));
        $this->render('respaldo/index', [
            'tab' => $tab, 'lista' => $lista, 'tablas' => $tablas, 'filas' => $filas, 'mb' => $mb, 'ultimo' => $lista[0] ?? null, 'zlib' => Respaldo::zlib(), 'maxSubida' => $max,
            'auto' => cfg('respaldo_auto', 'no'), 'ret' => (int)cfg('respaldo_retencion', '10'),
            'mov' => Respaldo::conteo(Respaldo::MOVIMIENTOS), 'mae' => Respaldo::conteo(Respaldo::MAESTROS), 'cat' => Respaldo::conteo(Respaldo::CATALOGOS),
            'usuariosInternos' => (int)$db->query('SELECT COUNT(*) FROM usuarios WHERE cliente_id IS NULL')->fetchColumn() - 1,
        ], 'Respaldo y restauración');
    }
    private static function bytes(string $v): int { $n = (int)$v; return match (strtolower(substr(trim($v), -1))) { 'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n }; }

    public function crear(): void
    {
        $this->guard(); $this->requirePost('respaldo');
        try { $n = Respaldo::crear('manual', !empty($_POST['comprimir'])); }
        catch (Throwable $e) { $this->flash('danger', 'No se pudo crear el respaldo: ' . $e->getMessage()); $this->redirect('respaldo'); }
        $this->log('Creó un respaldo de la base de datos', $n);
        if (!empty($_POST['descargar'])) { $this->enviar($n); }
        $this->flash('success', 'Respaldo creado: ' . $n); $this->redirect('respaldo');
    }

    private function enviar(string $nombre): void
    {
        $r = Respaldo::ruta($nombre); if (!$r) { $this->flash('danger', 'El archivo no existe.'); $this->redirect('respaldo'); }
        header('Content-Type: ' . (substr($nombre, -3) === '.gz' ? 'application/gzip' : 'application/sql')); header('Content-Length: ' . filesize($r));
        header('Content-Disposition: attachment; filename="' . $nombre . '"'); readfile($r); exit;
    }
    public function descargar($nombre = ''): void { $this->guard(); $this->log('Descargó un respaldo', (string)$nombre); $this->enviar((string)$nombre); }

    public function eliminar($nombre = ''): void
    {
        $this->guard(); $this->requirePost('respaldo');
        if ($r = Respaldo::ruta((string)$nombre)) { @unlink($r); $this->log('Eliminó un respaldo', (string)$nombre); $this->flash('success', 'Respaldo eliminado.'); } else $this->flash('danger', 'El archivo no existe.');
        $this->redirect('respaldo');
    }

    public function auto(): void
    {
        $this->guard(); $this->requirePost('respaldo');
        $m = in_array($_POST['modo'] ?? '', ['diario', 'semanal'], true) ? $_POST['modo'] : 'no'; $ret = max(1, min(60, (int)($_POST['retencion'] ?? 10)));
        $this->conf('respaldo_auto', $m); $this->conf('respaldo_retencion', (string)$ret); $this->conf('respaldo_auto_ts', '0');
        $this->flash('success', $m === 'no' ? 'Respaldo automático desactivado.' : "Respaldo automático $m activado (se conservan los últimos $ret).");
        $this->redirect('respaldo');
    }

    public function restaurar(): void
    {
        $this->guard(); $this->requirePost('respaldo?tab=restaurar'); $v = 'respaldo?tab=restaurar';
        $this->confirmado('RESTAURAR', $v);
        $ruta = null; $origen = '';
        if (!empty($_FILES['archivo']['name']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $nom = $_FILES['archivo']['name']; if (!preg_match('/\.sql(\.gz)?$/i', $nom)) { $this->flash('danger', 'Sube un archivo .sql o .sql.gz.'); $this->redirect($v); }
            $ruta = $_FILES['archivo']['tmp_name']; $origen = $nom;
            if (preg_match('/\.gz$/i', $nom)) { $tmp = $ruta . '.gz'; rename($ruta, $tmp); $ruta = $tmp; }
        } elseif (($_POST['servidor'] ?? '') !== '') {
            $ruta = Respaldo::ruta($_POST['servidor']); $origen = $_POST['servidor'];
        } elseif (!empty($_FILES['archivo']['name'])) { $this->flash('danger', 'No se pudo subir el archivo (¿supera el límite de ' . ini_get('upload_max_filesize') . '?). Cópialo a la carpeta storage/respaldos y elígelo de la lista.'); $this->redirect($v); }
        if (!$ruta) { $this->flash('warning', 'Elige un respaldo de la lista o sube un archivo.'); $this->redirect($v); }

        try { $previo = Respaldo::crear('previo'); } catch (Throwable $e) { $this->flash('danger', 'No se pudo crear el respaldo de seguridad previo, así que no se restauró nada: ' . $e->getMessage()); $this->redirect($v); }
        $uid = Auth::user()['id']; $res = ['ok' => true, 'origen' => $origen, 'previo' => $previo];
        try { [$n, $t] = Respaldo::restaurar($ruta); $res += ['sent' => $n, 'tablas' => $t]; }
        catch (Throwable $e) {
            $res['ok'] = false; $res['error'] = $e->getMessage();
            try { Respaldo::restaurar(Respaldo::ruta($previo)); $res['revertido'] = true; } catch (Throwable $e2) { $res['revertido'] = false; $res['error2'] = $e2->getMessage(); }
        }
        if ($res['ok']) { try { Database::conn()->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([$uid, 'Restauró la base de datos', $origen, 'bi-arrow-counterclockwise', '#f59e0b']); } catch (Throwable $e) {} Auth::logout(); }
        $this->renderPlain('respaldo/resultado', ['r' => $res]);
    }

    public function reiniciar(): void
    {
        $this->guard(); $this->requirePost('respaldo?tab=reiniciar'); $v = 'respaldo?tab=reiniciar';
        $this->confirmado('REINICIAR', $v);
        $modo = ($_POST['modo'] ?? '') === 'completo' ? 'completo' : 'movimientos';
        try { $previo = Respaldo::crear('reinicio'); } catch (Throwable $e) { $this->flash('danger', 'No se pudo crear el respaldo de seguridad previo, así que no se borró nada: ' . $e->getMessage()); $this->redirect($v); }
        try { Respaldo::reiniciar($modo, ['usuarios' => !empty($_POST['c_usuarios']), 'catalogos' => !empty($_POST['c_catalogos']), 'empresa' => !empty($_POST['c_empresa'])], (int)Auth::user()['id']); }
        catch (Throwable $e) { $this->flash('danger', 'El reinicio falló: ' . $e->getMessage() . ' · Tienes un respaldo previo: ' . $previo); $this->redirect($v); }
        $this->log($modo === 'completo' ? 'Reinició el sistema para una empresa nueva' : 'Limpió los datos de movimiento', 'Respaldo previo: ' . $previo, 'bi-arrow-repeat', '#ef4444');
        if ($modo === 'completo') { $this->flash('success', 'Sistema reiniciado. Configura ahora los datos de tu nueva empresa. (Respaldo previo guardado: ' . $previo . ')'); $this->redirect('empresa'); }
        $this->flash('success', 'Datos de movimiento eliminados. Respaldo previo guardado: ' . $previo); $this->redirect('respaldo?tab=reiniciar');
    }
}

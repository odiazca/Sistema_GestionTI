<?php
class ConfiguracionController extends Controller
{
    private const TABS = ['categorias' => ['Categorías', 'bi-tags'], 'seguridad' => ['Mi seguridad', 'bi-shield-lock'], 'sistema' => ['Sistema y respaldo', 'bi-hdd-network']];
    private const ICONOS = ['bi-cpu' => 'Procesador / hardware', 'bi-window-stack' => 'Software', 'bi-wifi' => 'Red / WiFi', 'bi-hdd-rack' => 'Servidores', 'bi-envelope-at' => 'Correo', 'bi-printer' => 'Impresoras', 'bi-shield-lock' => 'Seguridad', 'bi-telephone' => 'Telefonía', 'bi-camera-video' => 'Cámaras', 'bi-database' => 'Base de datos', 'bi-cloud' => 'Nube', 'bi-tag' => 'General'];

    public function index(): void
    {
        $this->requireAuth();
        if (in_array($_GET['tab'] ?? '', ['empresa', 'operacion'], true)) $this->redirect('empresa');
        $admin = Auth::user()['rol'] === 'Administrador';
        $tabs = $admin ? self::TABS : ['seguridad' => self::TABS['seguridad']];
        $tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : array_key_first($tabs);
        $db = Database::conn();
        $data = ['tab' => $tab, 'tabs' => $tabs, 'iconos' => self::ICONOS];
        if ($tab === 'categorias') $data['cats'] = $db->query('SELECT c.*, (SELECT COUNT(*) FROM tickets t WHERE t.categoria_id=c.id) usos FROM categorias c ORDER BY c.nombre')->fetchAll();
        if ($tab === 'sistema') {
            $data['info'] = [
                'PHP' => PHP_VERSION, 'MySQL' => $db->query('SELECT VERSION()')->fetchColumn(), 'Base de datos' => DB_NAME,
                'Tamaño de la BD' => round((float)$db->query("SELECT SUM(data_length+index_length)/1024/1024 FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchColumn(), 2) . ' MB',
                'Zona horaria' => date_default_timezone_get(), 'Versión del sistema' => APP_VERSION,
            ];
            $c = [];
            foreach (['usuarios', 'clientes', 'tickets', 'ordenes_servicio', 'activos', 'contratos', 'repuestos', 'kb_articulos'] as $t) { try { $c[$t] = (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); } catch (Throwable $e) { $c[$t] = '—'; } }
            $data['conteos'] = $c;
        }
        $this->render('configuracion/index', $data, 'Configuración');
    }

    public function guardar(): void
    {
        $this->requireRole(['Administrador']);
        $tab = $_POST['tab'] ?? 'empresa';
        $this->requirePost('configuracion?tab=' . $tab);
        $claves = ['empresa' => ['empresa_nombre', 'empresa_ruc', 'empresa_direccion', 'empresa_telefono', 'empresa_email'], 'operacion' => ['tarifa_hora', 'igv', 'horario_atencion']];
        if (!isset($claves[$tab])) $this->redirect('configuracion');
        $st = Database::conn()->prepare('INSERT INTO configuracion(clave,valor) VALUES(?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)');
        foreach ($claves[$tab] as $k) {
            $v = trim($_POST[$k] ?? '');
            if (in_array($k, ['tarifa_hora', 'igv'], true) && ($v === '' || !is_numeric($v) || $v < 0)) { $this->flash('danger', 'Ingresa valores numéricos válidos para tarifa e IGV.'); $this->redirect('configuracion?tab=' . $tab); }
            $st->execute([$k, $v]);
        }
        $this->flash('success', 'Configuración guardada.');
        $this->redirect('configuracion?tab=' . $tab);
    }

    public function categoria(): void
    {
        $this->requireRole(['Administrador']);
        $this->requirePost('configuracion?tab=categorias');
        $id = (int)($_POST['id'] ?? 0); $n = trim($_POST['nombre'] ?? ''); $ic = $_POST['icono'] ?? 'bi-tag'; $col = $_POST['color'] ?? '#4f46e5';
        if ($n === '' || mb_strlen($n) > 60 || !isset(self::ICONOS[$ic]) || !preg_match('/^#[0-9a-fA-F]{6}$/', $col)) { $this->flash('danger', 'Revisa el nombre, el ícono y el color de la categoría.'); $this->redirect('configuracion?tab=categorias'); }
        $db = Database::conn();
        if ($id) $db->prepare('UPDATE categorias SET nombre=?, icono=?, color=? WHERE id=?')->execute([$n, $ic, $col, $id]);
        else $db->prepare('INSERT INTO categorias(nombre,icono,color) VALUES(?,?,?)')->execute([$n, $ic, $col]);
        $this->flash('success', $id ? 'Categoría actualizada.' : 'Categoría creada.');
        $this->redirect('configuracion?tab=categorias');
    }

    public function categoria_eliminar($id = 0): void
    {
        $this->requireRole(['Administrador']);
        $this->requirePost('configuracion?tab=categorias');
        $db = Database::conn();
        $n = (int)$db->query('SELECT COUNT(*) FROM tickets WHERE categoria_id=' . (int)$id)->fetchColumn();
        if ($n > 0) $this->flash('warning', "No se puede eliminar: hay $n tickets con esta categoría.");
        else { $db->prepare('UPDATE kb_articulos SET categoria_id=NULL WHERE categoria_id=?')->execute([(int)$id]); $db->prepare('DELETE FROM categorias WHERE id=?')->execute([(int)$id]); $this->flash('success', 'Categoría eliminada.'); }
        $this->redirect('configuracion?tab=categorias');
    }

    /** Cambio de contraseña propia (disponible para todos los roles) */
    public function password(): void
    {
        $this->requireAuth();
        $this->requirePost('configuracion?tab=seguridad');
        $db = Database::conn(); $uid = (int)Auth::user()['id'];
        $actual = $_POST['actual'] ?? ''; $nueva = $_POST['nueva'] ?? ''; $conf = $_POST['confirmar'] ?? '';
        $hash = $db->query('SELECT password FROM usuarios WHERE id=' . $uid)->fetchColumn();
        if (!password_verify($actual, $hash)) $this->flash('danger', 'La contraseña actual es incorrecta.');
        elseif (mb_strlen($nueva) < 6) $this->flash('danger', 'La nueva contraseña debe tener al menos 6 caracteres.');
        elseif ($nueva !== $conf) $this->flash('danger', 'La confirmación no coincide con la nueva contraseña.');
        else { $db->prepare('UPDATE usuarios SET password=? WHERE id=?')->execute([password_hash($nueva, PASSWORD_DEFAULT), $uid]); $this->flash('success', 'Contraseña actualizada correctamente.'); }
        $this->redirect('configuracion?tab=seguridad');
    }

    /** Descarga una copia de seguridad completa (.sql) */
    public function respaldo(): void
    {
        $this->requireRole(['Administrador']);
        $this->requirePost('configuracion?tab=sistema');
        $db = Database::conn();
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="respaldo_' . DB_NAME . '_' . date('Ymd_His') . '.sql"');
        echo "-- " . APP_NAME . " · respaldo " . date('Y-m-d H:i:s') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        $tables = $db->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);   // sin vistas (las v_ia_* se recrean solas)
        // orden: tablas sin dependencias primero no es necesario con FK desactivadas
        foreach ($tables as $t) {
            echo "DROP TABLE IF EXISTS `$t`;\n" . $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1] . ";\n\n";
            $st = $db->query("SELECT * FROM `$t`", PDO::FETCH_NUM);
            $buf = [];
            while ($row = $st->fetch()) {
                $buf[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), $row)) . ')';
                if (count($buf) >= 200) { echo "INSERT INTO `$t` VALUES\n" . implode(",\n", $buf) . ";\n"; $buf = []; }
            }
            if ($buf) echo "INSERT INTO `$t` VALUES\n" . implode(",\n", $buf) . ";\n";
            echo "\n";
        }
        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        try { $db->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([Auth::user()['id'], 'Descargó un respaldo de la base de datos', '', 'bi-hdd-fill', '#64748b']); } catch (Throwable $e) {}
        exit;
    }
}

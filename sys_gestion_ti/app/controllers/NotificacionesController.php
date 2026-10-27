<?php
require_once ROOT_PATH . '/core/Notifier.php';

class NotificacionesController extends Controller
{
    private const CLAVES = ['smtp_host', 'smtp_port', 'smtp_seguridad', 'smtp_user', 'smtp_from', 'smtp_nombre', 'notif_copia'];
    private const TOGGLES = ['notif_ticket_creado', 'notif_ticket_estado', 'notif_ticket_respuesta'];

    public function index(): void
    {
        $this->requireRole(['Administrador', 'Supervisor']);
        $db = Database::conn(); $tab = ($_GET['tab'] ?? 'bandeja') === 'config' ? 'config' : 'bandeja';
        $w = []; $p = [];
        if (($e = $_GET['estado'] ?? '') !== '' && in_array($e, ['enviado', 'simulado', 'error'], true)) { $w[] = 'n.estado=?'; $p[] = $e; }
        if (($q = trim($_GET['q'] ?? '')) !== '') { $w[] = '(n.destinatario LIKE ? OR n.asunto LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
        $W = $w ? ' WHERE ' . implode(' AND ', $w) : '';
        $c = $db->prepare("SELECT COUNT(*) FROM notif_envios n$W"); $c->execute($p);
        $pg = $this->paginate((int)$c->fetchColumn(), 12, (int)($_GET['page'] ?? 1));
        $s = $db->prepare("SELECT n.*, c.razon_social cliente FROM notif_envios n LEFT JOIN clientes c ON c.id=n.cliente_id$W ORDER BY n.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}"); $s->execute($p);
        $cnt = $db->query("SELECT estado, COUNT(*) n FROM notif_envios GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);
        $cfg = []; foreach (array_merge(self::CLAVES, self::TOGGLES) as $k) $cfg[$k] = Notifier::conf($k, in_array($k, self::TOGGLES, true) ? '1' : ($k === 'smtp_port' ? '587' : ($k === 'smtp_seguridad' ? 'tls' : '')));
        $cfg['tiene_pass'] = Notifier::conf('smtp_pass') !== '';
        $this->render('notificaciones/index', ['tab' => $tab, 'rows' => $s->fetchAll(), 'pg' => $pg, 'cnt' => $cnt, 'cfg' => $cfg, 'listo' => Notifier::smtpListo()], 'Notificaciones');
    }

    public function guardar(): void
    {
        $this->requireRole(['Administrador']); $this->requirePost('notificaciones?tab=config');
        foreach (self::CLAVES as $k) ia_conf_set($k, trim($_POST[$k] ?? ''));
        foreach (self::TOGGLES as $k) ia_conf_set($k, !empty($_POST[$k]) ? '1' : '0');
        if (($pw = $_POST['smtp_pass'] ?? '') !== '') ia_conf_set('smtp_pass', ia_cifrar($pw));
        if (!empty($_POST['smtp_pass_borrar'])) ia_conf_set('smtp_pass', '');
        $this->flash('success', 'Configuración de notificaciones guardada.'); $this->redirect('notificaciones?tab=config');
    }

    public function probar(): void
    {
        $this->requireRole(['Administrador']); $this->requirePost('notificaciones?tab=config');
        $to = trim($_POST['destino'] ?? Auth::user()['email'] ?? '');
        $r = Notifier::enviar($to, 'Prueba de correo · ' . cfg('empresa_nombre', APP_NAME), Notifier::plantilla('Prueba de correo', '<p>Si lees esto, el envío de correos funciona correctamente.</p>'));
        $this->flash($r === 'enviado' ? 'success' : ($r === 'simulado' ? 'warning' : 'danger'), $r === 'enviado' ? "Correo de prueba enviado a $to." : ($r === 'simulado' ? 'SMTP no configurado: el correo quedó como «simulado» en la bandeja.' : 'No se pudo enviar. Revisa el detalle en la bandeja.'));
        $this->redirect('notificaciones?tab=config');
    }

    public function reenviar($id = 0): void
    {
        $this->requireRole(['Administrador', 'Supervisor']); $this->requirePost('notificaciones');
        $db = Database::conn(); $s = $db->prepare("SELECT * FROM notif_envios WHERE id=? AND canal='correo'"); $s->execute([(int)$id]);
        if ($n = $s->fetch()) {
            $r = Notifier::enviar($n['destinatario'], $n['asunto'], $n['cuerpo'], ['cliente_id' => $n['cliente_id'], 'ticket_id' => $n['ticket_id'], 'cotizacion_id' => $n['cotizacion_id']]);
            $this->flash($r === 'enviado' ? 'success' : 'warning', $r === 'enviado' ? 'Correo reenviado.' : ($r === 'simulado' ? 'SMTP sin configurar: quedó simulado.' : 'El reenvío falló; revisa la configuración SMTP.'));
        }
        $this->redirect('notificaciones');
    }

    public function ver($id = 0): void
    {
        $this->requireRole(['Administrador', 'Supervisor']);
        $s = Database::conn()->prepare('SELECT cuerpo FROM notif_envios WHERE id=?'); $s->execute([(int)$id]);
        header('Content-Type: text/html; charset=UTF-8'); header("Content-Security-Policy: script-src 'none'"); echo $s->fetchColumn() ?: 'No encontrado'; exit;
    }
}

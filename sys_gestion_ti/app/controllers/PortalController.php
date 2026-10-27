<?php
/** Portal de autoservicio del cliente: tickets propios, contratos, equipos y facturas pendientes */
class PortalController extends Controller
{
    private int $cid = 0;
    private PDO $db;

    public function __construct() { $this->db = Database::conn(); }

    private function guard(): array
    {
        $this->requireAuth(); $u = Auth::user();
        if (($u['rol'] ?? '') !== 'Cliente') { $this->redirect('dashboard'); }
        $c = $this->db->prepare('SELECT c.* FROM usuarios u JOIN clientes c ON c.id=u.cliente_id WHERE u.id=? AND u.estado=1'); $c->execute([$u['id']]); $cli = $c->fetch();
        if (!$cli) { Auth::logout(); Auth::start(); $this->flash('danger', 'Tu cuenta no está vinculada a una empresa. Contacta a soporte.'); $this->redirect('login'); }
        $this->cid = (int)$cli['id'];
        return $cli;
    }
    private function all(string $sql, array $p = []): array { $s = $this->db->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    private function one(string $sql, array $p = []): ?array { $s = $this->db->prepare($sql); $s->execute($p); return $s->fetch() ?: null; }

    private function vista(string $view, array $data, string $title, array $cli): void
    {
        $u = Auth::user(); extract($data); ob_start(); require APP_PATH . "/views/portal/$view.php"; $content = ob_get_clean();
        $pageTitle = $title; $u = Auth::user(); require APP_PATH . '/views/layouts/portal.php';
    }
    private function ticketPropio(int $id): array
    {
        $t = $this->one("SELECT t.*, cat.nombre categoria, CONCAT(u.nombres,' ',u.apellidos) tecnico, a.codigo act_codigo, a.tipo act_tipo, a.marca act_marca FROM tickets t JOIN categorias cat ON cat.id=t.categoria_id LEFT JOIN usuarios u ON u.id=t.tecnico_id LEFT JOIN activos a ON a.id=t.activo_id WHERE t.id=? AND t.cliente_id=?", [$id, $this->cid]);
        if (!$t) { $this->flash('danger', 'No encontramos ese ticket.'); $this->redirect('portal/tickets'); }
        return $t;
    }

    public function index(): void
    {
        $cli = $this->guard(); $c = $this->cid;
        $k = $this->one("SELECT COALESCE(SUM(estado IN ('abierto','en_proceso','pendiente')),0) abiertos, COALESCE(SUM(estado IN ('resuelto','cerrado')),0) resueltos, COUNT(*) total FROM tickets WHERE cliente_id=?", [$c]);
        $data = ['k' => $k,
            'recientes' => $this->all("SELECT t.id, t.codigo, t.asunto, t.estado, t.prioridad, t.created_at, t.fecha_limite FROM tickets t WHERE t.cliente_id=? ORDER BY t.created_at DESC LIMIT 6", [$c]),
            'contratos' => $this->all("SELECT *, DATEDIFF(fecha_fin, CURDATE()) dias FROM contratos WHERE cliente_id=? ORDER BY fecha_fin DESC", [$c]),
            'equipos' => $this->all("SELECT codigo, tipo, marca, modelo, estado FROM activos WHERE cliente_id=? AND estado<>'baja' ORDER BY codigo LIMIT 8", [$c]),
            'pendientes' => $this->listaFacturas($c, true)];
        $this->vista('index', $data, 'Inicio', $cli);
    }

    private function listaFacturas(int $c, bool $soloPend): array
    {
        try {
            return $this->all("SELECT f.id, f.codigo, f.tipo, f.fecha_emision, f.fecha_vencimiento, f.total, f.pagado, f.estado, (f.total-f.pagado) saldo, (f.estado='emitida' AND f.fecha_vencimiento < CURDATE()) vencida FROM fac_comprobantes f WHERE f.cliente_id=? AND f.estado<>'anulada'" . ($soloPend ? " AND f.estado='emitida'" : '') . " ORDER BY f.fecha_emision DESC LIMIT 50", [$c]);
        } catch (Throwable $e) { return []; }
    }

    public function tickets(): void
    {
        $cli = $this->guard(); $est = $_GET['estado'] ?? ''; $w = 'cliente_id=?'; $p = [$this->cid];
        if ($est === 'abiertos') $w .= " AND estado IN ('abierto','en_proceso','pendiente')"; elseif ($est === 'resueltos') $w .= " AND estado IN ('resuelto','cerrado')";
        $this->vista('tickets', ['rows' => $this->all("SELECT id, codigo, asunto, estado, prioridad, created_at, fecha_limite FROM tickets WHERE $w ORDER BY created_at DESC LIMIT 100", $p), 'est' => $est], 'Mis tickets', $cli);
    }

    public function nuevo(): void
    {
        $cli = $this->guard(); require_once APP_PATH . '/models/Ticket.php'; $row = ['categoria_id' => '', 'prioridad' => 'media', 'asunto' => '', 'descripcion' => '', 'activo_id' => ''];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost('portal/nuevo'); $d = []; foreach (['categoria_id', 'prioridad', 'asunto', 'descripcion', 'activo_id'] as $k) $d[$k] = trim($_POST[$k] ?? '');
            $err = [];
            if (!$this->one('SELECT id FROM categorias WHERE id=?', [(int)$d['categoria_id']])) $err['categoria_id'] = 'Selecciona una categoría.';
            if ($d['asunto'] === '' || mb_strlen($d['asunto']) > 180) $err['asunto'] = 'Describe brevemente el problema (máx. 180 caracteres).';
            if (!in_array($d['prioridad'], ['baja', 'media', 'alta'], true)) $d['prioridad'] = 'media';
            if ($d['activo_id'] !== '' && !$this->one('SELECT id FROM activos WHERE id=? AND cliente_id=?', [(int)$d['activo_id'], $this->cid])) $d['activo_id'] = '';
            if (!$err) {
                $m = new Ticket(); $id = $m->crear($d + ['cliente_id' => $this->cid, 'canal' => 'web', 'tecnico_id' => ''], (int)Auth::user()['id']);
                try { require_once ROOT_PATH . '/core/Notifier.php'; Notifier::ticketCreado($id); Notifier::avisoInterno($id); } catch (Throwable $e) {}
                $this->flash('success', 'Recibimos tu solicitud. Un técnico la atenderá según tu plan de soporte.'); $this->redirect("portal/ticket/$id");
            }
            $row = $d; $errors = $err;
        }
        $this->vista('nuevo', ['row' => $row, 'errors' => $errors ?? [], 'categorias' => $this->all('SELECT id, nombre FROM categorias ORDER BY nombre'), 'activos' => $this->all("SELECT id, codigo, tipo, marca, modelo FROM activos WHERE cliente_id=? AND estado<>'baja' ORDER BY codigo", [$this->cid])], 'Nuevo ticket', $cli);
    }

    public function ticket($id = 0): void
    {
        $cli = $this->guard(); $t = $this->ticketPropio((int)$id);
        $com = $this->all("SELECT m.*, CONCAT(u.nombres,' ',u.apellidos) autor, u.avatar_color color, r.nombre rol FROM ticket_comentarios m LEFT JOIN usuarios u ON u.id=m.usuario_id LEFT JOIN roles r ON r.id=u.rol_id WHERE m.ticket_id=? AND m.interno=0 ORDER BY m.created_at, m.id", [$t['id']]);
        $this->vista('ticket', ['t' => $t, 'com' => $com], $t['codigo'], $cli);
    }

    public function comentar($id = 0): void
    {
        $this->guard(); $t = $this->ticketPropio((int)$id); $this->requirePost('portal/ticket/' . $t['id']); $txt = trim($_POST['comentario'] ?? '');
        if ($txt === '' || mb_strlen($txt) > 3000) { $this->flash('warning', 'Escribe tu mensaje (máx. 3000 caracteres).'); $this->redirect('portal/ticket/' . $t['id']); }
        if ($t['estado'] === 'cerrado') { $this->flash('warning', 'El ticket está cerrado. Abre uno nuevo para continuar.'); $this->redirect('portal/ticket/' . $t['id']); }
        require_once APP_PATH . '/models/Ticket.php'; (new Ticket())->comentar($t['id'], (int)Auth::user()['id'], $txt, false);
        if ($t['estado'] === 'resuelto') (new Ticket())->actualizar($t['id'], ['estado' => 'abierto'], (int)Auth::user()['id']);
        $this->flash('success', 'Mensaje enviado a soporte.'); $this->redirect('portal/ticket/' . $t['id']);
    }

    public function facturas(): void
    {
        $cli = $this->guard(); $this->vista('facturas', ['rows' => $this->listaFacturas($this->cid, false)], 'Mis facturas', $cli);
    }

    public function comprobante($id = 0): void
    {
        $this->guard();
        try {
            $f = $this->one('SELECT f.*, f.estado est0, (f.total-f.pagado) saldo, c.razon_social cliente, c.ruc, c.direccion FROM fac_comprobantes f JOIN clientes c ON c.id=f.cliente_id WHERE f.id=? AND f.cliente_id=? AND f.estado<>\'anulada\'', [(int)$id, $this->cid]);
        } catch (Throwable $e) { $f = null; }
        if (!$f) { $this->flash('danger', 'Comprobante no encontrado.'); $this->redirect('portal'); }
        require_once ROOT_PATH . '/core/FacBase.php'; $items = $this->all('SELECT * FROM fac_items WHERE comprobante_id=? ORDER BY id', [$f['id']]);
        $this->renderPlain('facturacion/imprimir', ['f' => $f, 'items' => $items]);
    }

    public function clave(): void
    {
        $cli = $this->guard();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost('portal/clave'); $a = $_POST['actual'] ?? ''; $n = $_POST['nueva'] ?? ''; $r = $_POST['repetir'] ?? '';
            $h = $this->one('SELECT password FROM usuarios WHERE id=?', [Auth::user()['id']]);
            if (!$h || !password_verify($a, $h['password'])) $this->flash('danger', 'La contraseña actual no es correcta.');
            elseif (strlen($n) < 8 || !preg_match('/[A-Za-z]/', $n) || !preg_match('/\d/', $n)) $this->flash('danger', 'La nueva contraseña debe tener al menos 8 caracteres, con letras y números.');
            elseif ($n !== $r) $this->flash('danger', 'Las contraseñas nuevas no coinciden.');
            else { $this->db->prepare('UPDATE usuarios SET password=? WHERE id=?')->execute([password_hash($n, PASSWORD_DEFAULT), Auth::user()['id']]); $this->flash('success', 'Contraseña actualizada.'); }
            $this->redirect('portal/clave');
        }
        $this->vista('clave', [], 'Mi contraseña', $cli);
    }
}

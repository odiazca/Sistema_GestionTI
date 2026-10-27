<?php
class TicketsController extends Controller
{
    private Ticket $m;
    public function __construct() { require_once APP_PATH . '/models/Ticket.php'; $this->m = new Ticket(); }

    public function index(): void
    {
        $this->requireAuth();
        $f = [];
        foreach (['q', 'estado', 'prioridad', 'categoria', 'tecnico', 'cliente', 'vencidos'] as $k) $f[$k] = trim($_GET[$k] ?? '');
        $f['orden'] = in_array($f['estado'], ['abierto', 'en_proceso', 'pendiente'], true) || $f['vencidos'];
        $page = (int)($_GET['page'] ?? 1);
        $total = $this->m->listar($f, 1, 0)[1];
        $pg = $this->paginate($total, 10, $page);
        [$rows] = $this->m->listar($f, $pg['per'], $pg['offset']);
        $this->render('tickets/index', [
            'rows' => $rows, 'pg' => $pg, 'f' => $f, 'conteos' => $this->m->conteos(),
            'categorias' => $this->m->categorias(), 'tecnicos' => $this->m->tecnicos(), 'clientes' => $this->m->clientes(),
        ], 'Tickets');
    }

    // ------------------------------------------------------------ alta / edición
    public function crear(): void
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar(null); return; }
        $row = ['cliente_id' => (int)($_GET['cliente'] ?? 0) ?: '', 'activo_id' => '', 'categoria_id' => '', 'prioridad' => 'media', 'canal' => 'web',
                'tecnico_id' => '', 'asunto' => '', 'descripcion' => ''];
        $this->form($row, [], null);
    }

    public function editar($id = 0): void
    {
        $this->requireAuth();
        $t = $this->m->obtener((int)$id);
        if (!$t) { $this->flash('danger', 'El ticket no existe.'); $this->redirect('tickets'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar((int)$id); return; }
        $this->form($t, [], (int)$id);
    }

    private function form(array $row, array $errors, ?int $id): void
    {
        $this->render('tickets/form', [
            'row' => $row, 'errors' => $errors, 'id' => $id,
            'clientes' => $this->m->clientes(), 'categorias' => $this->m->categorias(), 'tecnicos' => $this->m->tecnicos(),
            'activos' => !empty($row['cliente_id']) ? $this->m->activosDe((int)$row['cliente_id']) : [],
            'sla_base' => !empty($row['cliente_id']) ? $this->m->slaBase((int)$row['cliente_id']) : 48,
        ], $id ? 'Editar ticket' : 'Nuevo ticket');
    }

    private function guardar(?int $id): void
    {
        $this->requirePost($id ? "tickets/editar/$id" : 'tickets/crear');
        $d = [];
        foreach (['cliente_id', 'activo_id', 'categoria_id', 'prioridad', 'canal', 'tecnico_id', 'asunto', 'descripcion'] as $k) $d[$k] = trim($_POST[$k] ?? '');
        $e = [];
        if ($d['cliente_id'] === '') $e['cliente_id'] = 'Selecciona un cliente.';
        if ($d['categoria_id'] === '') $e['categoria_id'] = 'Selecciona una categoría.';
        if ($d['asunto'] === '') $e['asunto'] = 'Describe brevemente el problema.';
        elseif (mb_strlen($d['asunto']) > 180) $e['asunto'] = 'Máximo 180 caracteres.';
        if (!isset(PRIORIDADES[$d['prioridad']])) $e['prioridad'] = 'Prioridad no válida.';
        if (!isset(CANALES[$d['canal']])) $e['canal'] = 'Canal no válido.';
        if ($e) { $this->flash('danger', 'Revisa los campos marcados.'); $this->form($d + ['id' => $id], $e, $id); return; }
        $uid = (int)Auth::user()['id'];
        if ($id) { $this->m->editar($id, $d, $uid); $this->flash('success', 'Ticket actualizado.'); $this->redirect("tickets/ver/$id"); }
        $nid = $this->m->crear($d, $uid); try { require_once ROOT_PATH . '/core/Notifier.php'; Notifier::ticketCreado($nid); } catch (Throwable $e) {}
        $this->flash('success', 'Ticket creado correctamente. El SLA fue calculado según el contrato del cliente.');
        $this->redirect("tickets/ver/$nid");
    }

    // ------------------------------------------------------------ detalle
    public function ver($id = 0): void
    {
        $this->requireAuth();
        $t = $this->m->obtener((int)$id);
        if (!$t) { $this->flash('danger', 'El ticket no existe.'); $this->redirect('tickets'); }
        $this->render('tickets/ver', ['t' => $t, 'coments' => $this->m->comentarios((int)$id), 'tecnicos' => $this->m->tecnicos()], $t['codigo']);
    }

    public function actualizar($id = 0): void
    {
        $this->requireAuth();
        $id = (int)$id;
        $this->requirePost("tickets/ver/$id");
        $uid = (int)Auth::user()['id'];
        $new = [];
        switch ($_POST['accion'] ?? '') {
            case 'tomar':   $new = ['tecnico_id' => $uid]; break;
            case 'resolver': $new = ['estado' => 'resuelto']; break;
            case 'cerrar':  $new = ['estado' => 'cerrado']; break;
            case 'reabrir': $new = ['estado' => 'abierto']; break;
            default:
                $new = ['estado' => $_POST['estado'] ?? null, 'prioridad' => $_POST['prioridad'] ?? null, 'tecnico_id' => (int)($_POST['tecnico_id'] ?? 0)];
        }
        $this->m->actualizar($id, array_filter($new, fn($v, $k) => $v !== null || $k === 'tecnico_id', ARRAY_FILTER_USE_BOTH), $uid);
        $this->flash('success', 'Ticket actualizado.');
        $this->redirect("tickets/ver/$id");
    }

    public function comentar($id = 0): void
    {
        $this->requireAuth();
        $id = (int)$id;
        $this->requirePost("tickets/ver/$id");
        $txt = trim($_POST['comentario'] ?? '');
        if ($txt === '') { $this->flash('warning', 'Escribe un comentario antes de enviar.'); $this->redirect("tickets/ver/$id"); }
        $this->m->comentar($id, (int)Auth::user()['id'], $txt, !empty($_POST['interno']));
        $this->flash('success', !empty($_POST['interno']) ? 'Nota interna agregada.' : 'Comentario publicado.');
        $this->redirect("tickets/ver/$id");
    }

    public function eliminar($id = 0): void
    {
        $this->requireRole(['Administrador', 'Supervisor']);
        $this->requirePost('tickets');
        $this->m->eliminar((int)$id);
        $this->flash('success', 'Ticket eliminado.');
        $this->redirect('tickets');
    }

    /** JSON: equipos del cliente + SLA base (para el formulario) */
    public function activos($cid = 0): void
    {
        $this->requireAuth();
        $this->json(['activos' => $this->m->activosDe((int)$cid), 'sla_base' => $this->m->slaBase((int)$cid)]);
    }
}

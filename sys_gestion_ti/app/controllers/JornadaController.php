<?php
/** «Mi jornada»: vista móvil del técnico con sus visitas, órdenes y tickets del día */
class JornadaController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['Administrador', 'Supervisor', 'Técnico']);
        $db = Database::conn(); $u = Auth::user(); $uid = (int)$u['id']; $todos = $u['rol'] !== 'Técnico' && isset($_GET['todos']);
        $f = $todos ? '' : ' AND %s=' . $uid;
        $q = function (string $sql) use ($db) { return $db->query($sql)->fetchAll(); };
        $visitas = $q("SELECT v.*, c.razon_social cliente, c.direccion, c.telefono, c.contacto, CONCAT(t.codigo) ticket FROM visitas v JOIN clientes c ON c.id=v.cliente_id LEFT JOIN tickets t ON t.id=v.ticket_id
            WHERE v.estado IN ('programada','en_curso') AND DATE(v.fecha_programada) BETWEEN CURDATE() - INTERVAL 1 DAY AND CURDATE() + INTERVAL 3 DAY" . sprintf($f, 'v.tecnico_id') . ' ORDER BY v.fecha_programada');
        $ordenes = $q("SELECT o.*, c.razon_social cliente, c.direccion, c.telefono FROM ordenes_servicio o JOIN clientes c ON c.id=o.cliente_id WHERE o.estado IN ('pendiente','en_curso')" . sprintf($f, 'o.tecnico_id') . ' ORDER BY (o.estado=\'en_curso\') DESC, COALESCE(o.fecha_programada, o.created_at) LIMIT 15');
        $tickets = $q("SELECT t.id, t.codigo, t.asunto, t.prioridad, t.estado, t.fecha_limite, c.razon_social cliente FROM tickets t JOIN clientes c ON c.id=t.cliente_id WHERE t.estado IN ('abierto','en_proceso','pendiente')" . sprintf($f, 't.tecnico_id') . " ORDER BY FIELD(t.prioridad,'critica','alta','media','baja'), t.fecha_limite LIMIT 10");
        $this->render('jornada/index', ['visitas' => $visitas, 'ordenes' => $ordenes, 'tickets' => $tickets, 'todos' => $todos], 'Mi jornada');
    }
}

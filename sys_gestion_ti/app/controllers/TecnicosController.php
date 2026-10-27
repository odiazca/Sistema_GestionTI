<?php
class TecnicosController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $db = Database::conn();
        $tec = $db->query("SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) nombre, u.cargo, u.avatar_color color, u.email, u.ultimo_acceso, r.nombre rol,
              (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id AND t.estado IN ('abierto','en_proceso','pendiente')) activos,
              (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id AND t.estado IN ('abierto','en_proceso','pendiente') AND t.fecha_limite < NOW()) vencidos,
              (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id AND t.resuelto_at >= NOW() - INTERVAL 30 DAY) resueltos,
              (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id AND t.resuelto_at >= NOW() - INTERVAL 30 DAY AND t.resuelto_at <= t.fecha_limite) en_sla,
              (SELECT AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.resuelto_at)) FROM tickets t WHERE t.tecnico_id=u.id AND t.resuelto_at >= NOW() - INTERVAL 30 DAY) tmr,
              (SELECT COUNT(*) FROM ordenes_servicio o WHERE o.tecnico_id=u.id AND o.estado IN ('pendiente','en_curso')) ordenes,
              (SELECT COUNT(*) FROM visitas v WHERE v.tecnico_id=u.id AND v.estado='programada' AND v.fecha_programada >= NOW()) visitas,
              (SELECT COALESCE(SUM(o.horas),0) FROM ordenes_servicio o WHERE o.tecnico_id=u.id AND o.estado='completada' AND o.fecha_fin >= NOW() - INTERVAL 30 DAY) horas
            FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.estado=1 AND r.nombre IN ('Técnico','Supervisor') ORDER BY resueltos DESC")->fetchAll();
        $this->render('tecnicos/index', ['tec' => $tec], 'Equipo Técnico');
    }
}

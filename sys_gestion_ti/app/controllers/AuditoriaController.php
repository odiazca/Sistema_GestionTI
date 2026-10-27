<?php
class AuditoriaController extends Controller
{
    private function consulta(int $limit = 0, int $offset = 0): array
    {
        $w = []; $p = [];
        if (($q = trim($_GET['q'] ?? '')) !== '') { $w[] = '(a.accion LIKE :q1 OR a.detalle LIKE :q2)'; $p[':q1'] = $p[':q2'] = "%$q%"; }
        if (($u = $_GET['usuario'] ?? '') !== '') { $w[] = 'a.usuario_id = :u'; $p[':u'] = (int)$u; }
        $ok = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v);
        if ($ok($_GET['desde'] ?? null)) { $w[] = 'a.created_at >= :d'; $p[':d'] = $_GET['desde'] . ' 00:00:00'; }
        if ($ok($_GET['hasta'] ?? null)) { $w[] = 'a.created_at <= :h'; $p[':h'] = $_GET['hasta'] . ' 23:59:59'; }
        $W = $w ? ' WHERE ' . implode(' AND ', $w) : '';
        $db = Database::conn();
        $c = $db->prepare("SELECT COUNT(*) FROM actividad a$W"); $c->execute($p);
        $sql = "SELECT a.*, CONCAT(u.nombres,' ',u.apellidos) usuario, u.avatar_color color FROM actividad a LEFT JOIN usuarios u ON u.id=a.usuario_id$W ORDER BY a.created_at DESC, a.id DESC"
             . ($limit ? " LIMIT $limit OFFSET $offset" : ' LIMIT 5000');
        $s = $db->prepare($sql); $s->execute($p);
        return [$s->fetchAll(), (int)$c->fetchColumn()];
    }

    public function index(): void
    {
        $this->requireRole(['Administrador', 'Supervisor']);
        $total = $this->consulta(1, 0)[1];
        $pg = $this->paginate($total, 15, (int)($_GET['page'] ?? 1));
        [$rows] = $this->consulta($pg['per'], $pg['offset']);
        $db = Database::conn();
        $this->render('auditoria/index', [
            'rows' => $rows, 'pg' => $pg,
            'usuarios' => $db->query("SELECT id, CONCAT(nombres,' ',apellidos) n FROM usuarios ORDER BY nombres")->fetchAll(),
            'hoy' => (int)$db->query('SELECT COUNT(*) FROM actividad WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
            'accesos' => (int)$db->query("SELECT COUNT(*) FROM actividad WHERE accion='Inició sesión' AND created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn(),
            'activos' => (int)$db->query('SELECT COUNT(DISTINCT usuario_id) FROM actividad WHERE created_at >= NOW() - INTERVAL 7 DAY')->fetchColumn(),
            'total' => (int)$db->query('SELECT COUNT(*) FROM actividad')->fetchColumn(),
        ], 'Auditoría');
    }

    public function exportar(): void
    {
        $this->requireRole(['Administrador', 'Supervisor']);
        [$rows] = $this->consulta();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria_' . date('Ymd') . '.csv"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, ['Fecha', 'Usuario', 'Acción', 'Detalle'], ';');
        foreach ($rows as $r) fputcsv($o, [$r['created_at'], $r['usuario'] ?? 'Sistema', $r['accion'], $r['detalle']], ';');
        fclose($o); exit;
    }
}

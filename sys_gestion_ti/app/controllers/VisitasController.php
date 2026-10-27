<?php
class VisitasController extends CrudController
{
    public const TIPOS = ['correctiva' => 'Correctiva', 'preventiva' => 'Preventiva', 'instalacion' => 'Instalación', 'emergencia' => 'Emergencia'];
    public const TIPO_COLOR = ['correctiva' => '#4f46e5', 'preventiva' => '#10b981', 'instalacion' => '#0ea5e9', 'emergencia' => '#ef4444'];
    public const ESTADOS = ['programada' => 'Programada', 'en_curso' => 'En curso', 'completada' => 'Completada', 'cancelada' => 'Cancelada'];

    protected function cfg(): array
    {
        return [
            'slug' => 'visitas', 'table' => 'visitas', 'title' => 'Visitas Técnicas', 'singular' => 'Visita', 'group' => 'Mesa de Ayuda',
            'subtitle' => 'Agenda de visitas a clientes.', 'label_field' => 'notas',
            'select' => 'v.*', 'from' => 'visitas v', 'order' => 'v.fecha_programada DESC',
            'fields' => [
                ['name' => 'cliente_id', 'label' => 'Cliente', 'type' => 'select', 'options_sql' => 'SELECT id, razon_social FROM clientes ORDER BY razon_social', 'required' => true, 'col' => 6, 'section' => 'Datos de la visita'],
                ['name' => 'tipo', 'label' => 'Tipo de visita', 'type' => 'select', 'options' => self::TIPOS, 'required' => true, 'col' => 3, 'default' => 'correctiva'],
                ['name' => 'fecha_programada', 'label' => 'Fecha y hora', 'type' => 'datetime-local', 'required' => true, 'col' => 3, 'default' => date('Y-m-d\TH:00', strtotime('+1 day 10:00'))],
                ['name' => 'tecnico_id', 'label' => 'Técnico asignado', 'type' => 'select', 'options_sql' => "SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.estado=1 AND r.nombre IN ('Técnico','Supervisor','Administrador') ORDER BY u.nombres", 'col' => 4],
                ['name' => 'ticket_id', 'label' => 'Ticket relacionado', 'type' => 'select', 'options_sql' => "SELECT id, CONCAT(codigo,' · ',LEFT(asunto,40)) FROM tickets WHERE estado IN ('abierto','en_proceso','pendiente') ORDER BY id DESC", 'col' => 5],
                ['name' => 'estado', 'label' => 'Estado', 'type' => 'select', 'options' => self::ESTADOS, 'required' => true, 'col' => 3, 'default' => 'programada'],
                ['name' => 'notas', 'label' => 'Notas / objetivo de la visita', 'type' => 'textarea', 'rows' => 3, 'col' => 12, 'maxlength' => 255],
            ],
        ];
    }

    protected function validate(array $d, ?int $id): array
    {
        // evita cruces: el mismo técnico no puede tener dos visitas a menos de 60 min
        if (!empty($d['tecnico_id']) && !empty($d['fecha_programada']) && in_array($d['estado'] ?? 'programada', ['programada', 'en_curso'])) {
            $st = Database::conn()->prepare("SELECT COUNT(*) FROM visitas WHERE tecnico_id=? AND estado IN ('programada','en_curso') AND id<>? AND ABS(TIMESTAMPDIFF(MINUTE, fecha_programada, ?)) < 60");
            $st->execute([$d['tecnico_id'], $id ?? 0, $d['fecha_programada']]);
            if ($st->fetchColumn() > 0) return ['tecnico_id' => 'El técnico ya tiene otra visita en ese horario (±60 min).'];
        }
        return [];
    }

    // ------------------------------------------------------------ agenda
    public function index(): void
    {
        $this->guard();
        $db = Database::conn();
        $mes = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : date('Y-m');
        $ini = strtotime($mes . '-01'); $dias = (int)date('t', $ini);
        $tec = $_GET['tecnico'] ?? '';
        $w = ' AND (:t = "" OR v.tecnico_id = :t2)';
        $st = $db->prepare("SELECT v.*, c.razon_social cliente, CONCAT(u.nombres,' ',u.apellidos) tecnico, u.avatar_color color FROM visitas v
            JOIN clientes c ON c.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.tecnico_id
            WHERE v.fecha_programada >= :a AND v.fecha_programada < :b $w ORDER BY v.fecha_programada");
        $st->execute([':a' => date('Y-m-d', $ini), ':b' => date('Y-m-d', strtotime('+1 month', $ini)), ':t' => $tec, ':t2' => $tec]);
        $porDia = [];
        foreach ($st->fetchAll() as $v) $porDia[(int)date('j', strtotime($v['fecha_programada']))][] = $v;

        $prox = $db->prepare("SELECT v.*, c.razon_social cliente, c.direccion, CONCAT(u.nombres,' ',u.apellidos) tecnico, u.avatar_color color FROM visitas v
            JOIN clientes c ON c.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.tecnico_id
            WHERE v.estado IN ('programada','en_curso') AND v.fecha_programada >= CURDATE() AND (:t = '' OR v.tecnico_id = :t2) ORDER BY v.fecha_programada LIMIT 8");
        $prox->execute([':t' => $tec, ':t2' => $tec]);
        $stats = $db->query("SELECT SUM(estado='programada' AND fecha_programada>=NOW()) prog, SUM(DATE(fecha_programada)=CURDATE() AND estado IN ('programada','en_curso')) hoy,
            SUM(estado='completada' AND fecha_programada >= NOW() - INTERVAL 30 DAY) comp, SUM(tipo='emergencia' AND estado IN ('programada','en_curso')) emerg FROM visitas")->fetch();

        $this->render('visitas/index', [
            'mes' => $mes, 'ini' => $ini, 'dias' => $dias, 'porDia' => $porDia, 'prox' => $prox->fetchAll(), 'stats' => $stats, 'tec' => $tec,
            'tecnicos' => $db->query("SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) nombre FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.estado=1 AND r.nombre IN ('Técnico','Supervisor','Administrador') ORDER BY u.nombres")->fetchAll(),
        ], 'Visitas Técnicas');
    }

    public function estado($id = 0): void
    {
        $this->guard(); $id = (int)$id;
        $this->requirePost('visitas');
        $e = $_POST['estado'] ?? '';
        if (isset(self::ESTADOS[$e])) {
            Database::conn()->prepare('UPDATE visitas SET estado=? WHERE id=?')->execute([$e, $id]);
            $this->log('Visita ' . mb_strtolower(self::ESTADOS[$e]), "Visita #$id", $e === 'completada' ? 'bi-check-circle-fill' : 'bi-geo-alt-fill', $e === 'completada' ? '#10b981' : '#f59e0b');
            $this->flash('success', 'Visita marcada como ' . mb_strtolower(self::ESTADOS[$e]) . '.');
        }
        if (($_POST['volver'] ?? '') === 'jornada') $this->redirect('jornada');
        $this->redirect('visitas?mes=' . urlencode($_POST['mes'] ?? date('Y-m')));
    }
}

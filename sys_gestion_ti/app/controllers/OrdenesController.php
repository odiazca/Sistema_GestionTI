<?php
class OrdenesController extends CrudController
{
    private const TIPOS = ['correctiva' => 'Correctiva', 'preventiva' => 'Preventiva', 'instalacion' => 'Instalación', 'diagnostico' => 'Diagnóstico'];
    private const ESTADOS = ['pendiente' => 'Pendiente', 'en_curso' => 'En curso', 'completada' => 'Completada', 'cancelada' => 'Cancelada'];
    private const EST_CLS = ['pendiente' => 'soft-warning', 'en_curso' => 'soft-info', 'completada' => 'success', 'cancelada' => 'muted'];

    protected function cfg(): array
    {
        $estCls = self::EST_CLS; $est = self::ESTADOS;
        return [
            'slug' => 'ordenes', 'table' => 'ordenes_servicio', 'title' => 'Órdenes de Servicio', 'singular' => 'Orden de servicio', 'group' => 'Mesa de Ayuda',
            'subtitle' => 'Órdenes de trabajo para mantenimientos, instalaciones y reparaciones.', 'label_field' => 'codigo', 'view' => true,
            'select' => "o.*, c.razon_social AS cliente, CONCAT(u.nombres,' ',u.apellidos) AS tecnico, u.avatar_color AS color, tk.codigo AS ticket,
                         (o.horas*o.tarifa_hora + COALESCE((SELECT SUM(r.cantidad*r.precio_unit) FROM ordenes_repuestos r WHERE r.orden_id=o.id),0)) AS total",
            'from' => 'ordenes_servicio o JOIN clientes c ON c.id=o.cliente_id LEFT JOIN usuarios u ON u.id=o.tecnico_id LEFT JOIN tickets tk ON tk.id=o.ticket_id',
            'order' => 'o.id DESC',
            'search' => ['o.codigo', 'c.razon_social', 'o.descripcion_trabajo', 'tk.codigo'],
            'filters' => [
                ['name' => 'estado', 'col' => 'o.estado', 'label' => 'Estado', 'options' => $est],
                ['name' => 'tipo', 'col' => 'o.tipo', 'label' => 'Tipo', 'options' => self::TIPOS],
                ['name' => 'tecnico', 'col' => 'o.tecnico_id', 'label' => 'Técnico', 'options_sql' => "SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE r.nombre IN ('Técnico','Supervisor','Administrador') ORDER BY u.nombres"],
            ],
            'stats' => [
                ['Pendientes', "SELECT COUNT(*) FROM ordenes_servicio WHERE estado='pendiente'", 'bi-hourglass-split', '#f59e0b'],
                ['En curso', "SELECT COUNT(*) FROM ordenes_servicio WHERE estado='en_curso'", 'bi-gear-wide-connected', '#0ea5e9'],
                ['Completadas (30 días)', "SELECT COUNT(*) FROM ordenes_servicio WHERE estado='completada' AND fecha_fin >= NOW() - INTERVAL 30 DAY", 'bi-check-circle-fill', '#10b981'],
                ['Facturable (30 días)', "SELECT COALESCE(SUM(o.horas*o.tarifa_hora),0)+COALESCE((SELECT SUM(r.cantidad*r.precio_unit) FROM ordenes_repuestos r JOIN ordenes_servicio x ON x.id=r.orden_id WHERE x.estado='completada' AND x.fecha_fin >= NOW() - INTERVAL 30 DAY),0) FROM ordenes_servicio o WHERE o.estado='completada' AND o.fecha_fin >= NOW() - INTERVAL 30 DAY", 'bi-cash-coin', '#4f46e5', 'money'],
            ],
            'columns' => [
                ['label' => 'Orden', 'html' => fn($r) => '<a class="tk-code fw-bold" href="' . url('ordenes/ver/' . $r['id']) . '">' . e($r['codigo']) . '</a><div class="tk-sub">' . ($r['ticket'] ? 'Ticket ' . e($r['ticket']) : 'Sin ticket') . '</div>'],
                ['label' => 'Cliente / trabajo', 'html' => fn($r) => '<b class="small">' . e($r['cliente']) . '</b><div class="tk-sub">' . e(mb_strimwidth($r['descripcion_trabajo'] ?? '', 0, 48, '…')) . '</div>'],
                ['label' => 'Tipo', 'html' => fn($r) => pill(self::TIPOS[$r['tipo']], 'soft-primary')],
                ['label' => 'Técnico', 'html' => fn($r) => $r['tecnico'] ? avatar_html($r['tecnico'], $r['color'], 'avatar-sm') : '<span class="text-muted small">Sin asignar</span>'],
                ['label' => 'Programada', 'html' => fn($r) => e(fdate($r['fecha_programada'], 'd/m H:i'))],
                ['label' => 'Total', 'html' => fn($r) => $r['estado'] === 'cancelada' ? '—' : '<b>' . e(money($r['total'])) . '</b>'],
                ['label' => 'Estado', 'html' => fn($r) => pill($est[$r['estado']], $estCls[$r['estado']])],
            ],
            'fields' => [
                ['name' => 'cliente_id', 'label' => 'Cliente', 'type' => 'select', 'options_sql' => 'SELECT id, razon_social FROM clientes ORDER BY razon_social', 'required' => true, 'col' => 6, 'section' => 'Datos de la orden'],
                ['name' => 'tipo', 'label' => 'Tipo de servicio', 'type' => 'select', 'options' => self::TIPOS, 'required' => true, 'col' => 3, 'default' => 'correctiva'],
                ['name' => 'fecha_programada', 'label' => 'Fecha programada', 'type' => 'datetime-local', 'col' => 3, 'default' => date('Y-m-d\TH:00', strtotime('+1 day'))],
                ['name' => 'ticket_id', 'label' => 'Ticket relacionado', 'type' => 'select', 'options_sql' => "SELECT id, CONCAT(codigo,' · ',LEFT(asunto,48)) FROM tickets WHERE estado IN ('abierto','en_proceso','pendiente') ORDER BY id DESC", 'col' => 6, 'help' => 'Opcional. Solo se listan tickets activos.'],
                ['name' => 'activo_id', 'label' => 'Equipo', 'type' => 'select', 'options_sql' => "SELECT a.id, CONCAT(a.codigo,' · ',a.tipo,' ',IFNULL(a.marca,''),' — ',c.razon_social) FROM activos a JOIN clientes c ON c.id=a.cliente_id ORDER BY c.razon_social, a.codigo", 'col' => 6],
                ['name' => 'tecnico_id', 'label' => 'Técnico responsable', 'type' => 'select', 'options_sql' => "SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.estado=1 AND r.nombre IN ('Técnico','Supervisor','Administrador') ORDER BY u.nombres", 'col' => 6],
                ['name' => 'tarifa_hora', 'label' => 'Tarifa por hora (S/)', 'type' => 'number', 'step' => '0.01', 'required' => true, 'col' => 3, 'default' => (float)cfg('tarifa_hora', 45)],
                ['name' => 'descripcion_trabajo', 'label' => 'Trabajo a realizar', 'type' => 'textarea', 'rows' => 4, 'required' => true, 'col' => 12, 'section' => 'Alcance del trabajo'],
            ],
        ];
    }

    protected function beforeSave(array $d, ?int $id): array
    {
        if (!$id) {
            $n = (int)Database::conn()->query('SELECT COALESCE(MAX(id),0)+1 FROM ordenes_servicio')->fetchColumn();
            $d['codigo'] = sprintf('OS-%s-%03d', date('Y'), $n);
            $d['estado'] = 'pendiente';
        }
        return $d;
    }

    // ------------------------------------------------------------ detalle
    private function orden(int $id): ?array
    {
        $st = Database::conn()->prepare("SELECT o.*, c.razon_social cliente, c.contacto, c.telefono cli_tel, tk.codigo ticket, tk.asunto ticket_asunto, tk.estado ticket_estado,
            a.codigo act_codigo, a.tipo act_tipo, a.marca act_marca, a.modelo act_modelo, CONCAT(u.nombres,' ',u.apellidos) tecnico, u.avatar_color color
            FROM ordenes_servicio o JOIN clientes c ON c.id=o.cliente_id LEFT JOIN tickets tk ON tk.id=o.ticket_id LEFT JOIN activos a ON a.id=o.activo_id LEFT JOIN usuarios u ON u.id=o.tecnico_id WHERE o.id=?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function ver($id = 0): void
    {
        $this->guard();
        $id = (int)$id; $db = Database::conn();
        $o = $this->orden($id);
        if (!$o) { $this->flash('danger', 'La orden no existe.'); $this->redirect('ordenes'); }
        $items = $db->prepare('SELECT x.*, r.nombre, r.codigo FROM ordenes_repuestos x JOIN repuestos r ON r.id=x.repuesto_id WHERE x.orden_id=? ORDER BY x.id');
        $items->execute([$id]);
        $this->render('ordenes/ver', [
            'o' => $o, 'items' => $items->fetchAll(), 'est' => self::ESTADOS, 'estCls' => self::EST_CLS, 'tipos' => self::TIPOS,
            'repuestos' => $db->query('SELECT id, codigo, nombre, stock, precio FROM repuestos WHERE stock > 0 ORDER BY nombre')->fetchAll(),
        ], $o['codigo']);
    }

    /** Firma digital del cliente (PNG en base64 desde el canvas) */
    public function firmar($id = 0): void
    {
        $this->guard(); $id = (int)$id; $this->requirePost("ordenes/ver/$id");
        $o = $this->orden($id); if (!$o) $this->redirect('ordenes');
        $f = $_POST['firma'] ?? ''; $n = trim($_POST['firma_nombre'] ?? '');
        if (!preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $f) || strlen($f) > 600000 || strlen($f) < 1500) { $this->flash('danger', 'La firma está vacía o no es válida. Dibuja la firma e inténtalo otra vez.'); $this->redirect("ordenes/ver/$id"); }
        if ($n === '' || mb_strlen($n) > 100) { $this->flash('danger', 'Indica el nombre de quien firma.'); $this->redirect("ordenes/ver/$id"); }
        Database::conn()->prepare('UPDATE ordenes_servicio SET firma=?, firma_nombre=?, firma_fecha=NOW(), conformidad=1 WHERE id=?')->execute([$f, $n, $id]);
        Database::conn()->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)')->execute([Auth::user()['id'], 'Firma del cliente en ' . $o['codigo'], mb_substr($n, 0, 190), 'bi-pen-fill', '#10b981']);
        $this->flash('success', 'Firma registrada y conformidad del cliente confirmada.'); $this->redirect("ordenes/ver/$id");
    }

    public function quitarfirma($id = 0): void
    {
        $this->requireRole(['Administrador', 'Supervisor']); $id = (int)$id; $this->requirePost("ordenes/ver/$id");
        Database::conn()->prepare('UPDATE ordenes_servicio SET firma=NULL, firma_nombre=NULL, firma_fecha=NULL, conformidad=0 WHERE id=?')->execute([$id]);
        $this->flash('info', 'Firma eliminada.'); $this->redirect("ordenes/ver/$id");
    }

    /** Cambios de estado: iniciar / completar / cancelar / reabrir */
    public function estado($id = 0): void
    {
        $this->guard(); $id = (int)$id;
        $this->requirePost("ordenes/ver/$id");
        $o = $this->orden($id);
        if (!$o) $this->redirect('ordenes');
        $db = Database::conn(); $uid = (int)Auth::user()['id'];
        switch ($_POST['accion'] ?? '') {
            case 'iniciar':
                $db->prepare("UPDATE ordenes_servicio SET estado='en_curso', fecha_inicio=COALESCE(fecha_inicio,NOW()), tecnico_id=COALESCE(tecnico_id,?) WHERE id=?")->execute([$uid, $id]);
                $this->log('Inició la orden ' . $o['codigo'], $o['cliente'], 'bi-play-circle-fill', '#0ea5e9');
                $this->flash('success', 'Orden iniciada.'); break;
            case 'completar':
                $diag = trim($_POST['diagnostico'] ?? ''); $sol = trim($_POST['solucion'] ?? ''); $h = (float)($_POST['horas'] ?? 0);
                if ($sol === '' || $h <= 0) { $this->flash('warning', 'Para completar la orden indica la solución aplicada y las horas trabajadas.'); $this->redirect("ordenes/ver/$id"); }
                $db->prepare("UPDATE ordenes_servicio SET estado='completada', diagnostico=?, solucion=?, horas=?, conformidad=?, fecha_inicio=COALESCE(fecha_inicio,NOW()), fecha_fin=NOW() WHERE id=?")
                   ->execute([$diag ?: null, $sol, $h, !empty($_POST['conformidad']) ? 1 : 0, $id]);
                $msg = 'Orden completada.';
                if (!empty($_POST['resolver_ticket']) && $o['ticket_id']) {
                    require_once APP_PATH . '/models/Ticket.php';
                    (new Ticket())->actualizar((int)$o['ticket_id'], ['estado' => 'resuelto'], $uid);
                    $msg .= ' El ticket ' . $o['ticket'] . ' fue marcado como resuelto.';
                }
                $this->log('Completó la orden ' . $o['codigo'], $o['cliente'], 'bi-check-circle-fill', '#10b981');
                $this->flash('success', $msg); break;
            case 'cancelar':
                $db->prepare("UPDATE ordenes_servicio SET estado='cancelada' WHERE id=? AND estado IN ('pendiente','en_curso')")->execute([$id]);
                $this->flash('success', 'Orden cancelada.'); break;
            case 'reabrir':
                $db->prepare("UPDATE ordenes_servicio SET estado='en_curso', fecha_fin=NULL WHERE id=?")->execute([$id]);
                $this->flash('success', 'Orden reabierta.'); break;
        }
        $this->redirect("ordenes/ver/$id");
    }

    public function informe($id = 0): void
    {
        $this->guard(); $id = (int)$id;
        $this->requirePost("ordenes/ver/$id");
        Database::conn()->prepare('UPDATE ordenes_servicio SET diagnostico=?, solucion=?, horas=?, conformidad=? WHERE id=?')
            ->execute([trim($_POST['diagnostico'] ?? '') ?: null, trim($_POST['solucion'] ?? '') ?: null, max(0, (float)($_POST['horas'] ?? 0)), !empty($_POST['conformidad']) ? 1 : 0, $id]);
        $this->flash('success', 'Informe técnico guardado.');
        $this->redirect("ordenes/ver/$id");
    }

    /** Consume un repuesto del almacén y lo carga a la orden */
    public function repuesto($id = 0): void
    {
        $this->guard(); $id = (int)$id;
        $this->requirePost("ordenes/ver/$id");
        $rid = (int)($_POST['repuesto_id'] ?? 0); $q = (int)($_POST['cantidad'] ?? 0);
        $db = Database::conn();
        $db->beginTransaction();
        $st = $db->prepare('SELECT * FROM repuestos WHERE id=? FOR UPDATE'); $st->execute([$rid]); $r = $st->fetch();
        if (!$r || $q < 1) { $db->rollBack(); $this->flash('warning', 'Selecciona un repuesto y una cantidad válida.'); $this->redirect("ordenes/ver/$id"); }
        if ($r['stock'] < $q) { $db->rollBack(); $this->flash('danger', "Stock insuficiente de {$r['nombre']}: disponible {$r['stock']}."); $this->redirect("ordenes/ver/$id"); }
        $db->prepare('UPDATE repuestos SET stock = stock - ? WHERE id=?')->execute([$q, $rid]);
        $db->prepare('INSERT INTO ordenes_repuestos(orden_id,repuesto_id,cantidad,precio_unit) VALUES(?,?,?,?)')->execute([$id, $rid, $q, $r['precio']]);
        $db->commit();
        $this->log('Consumió repuestos en orden', "{$r['nombre']} x$q", 'bi-box-arrow-up', '#f59e0b');
        $this->flash('success', "{$r['nombre']} x{$q} agregado a la orden y descontado del almacén.");
        $this->redirect("ordenes/ver/$id");
    }

    public function quitar($id = 0, $item = 0): void
    {
        $this->guard(); $id = (int)$id;
        $this->requirePost("ordenes/ver/$id");
        $db = Database::conn();
        $st = $db->prepare('SELECT * FROM ordenes_repuestos WHERE id=? AND orden_id=?'); $st->execute([(int)$item, $id]);
        if ($it = $st->fetch()) {
            $db->beginTransaction();
            $db->prepare('UPDATE repuestos SET stock = stock + ? WHERE id=?')->execute([$it['cantidad'], $it['repuesto_id']]);
            $db->prepare('DELETE FROM ordenes_repuestos WHERE id=?')->execute([$it['id']]);
            $db->commit();
            $this->flash('success', 'Repuesto retirado de la orden; el stock fue devuelto al almacén.');
        }
        $this->redirect("ordenes/ver/$id");
    }

    protected function canDelete(int $id): ?string
    {
        $o = $this->orden($id);
        return ($o && $o['estado'] === 'completada') ? 'No se puede eliminar una orden completada. Puedes cancelarla o reabrirla.' : null;
    }

    public function eliminar($id = 0): void
    {
        // devolver stock antes de eliminar
        $id = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf'] ?? null) && !$this->canDelete($id)) {
            Database::conn()->prepare('UPDATE repuestos r JOIN ordenes_repuestos x ON x.repuesto_id=r.id SET r.stock = r.stock + x.cantidad WHERE x.orden_id=?')->execute([$id]);
        }
        parent::eliminar($id);
    }
}

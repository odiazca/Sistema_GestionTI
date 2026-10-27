<?php
class ClientesController extends CrudController
{
    protected function cfg(): array
    {
        $rubros = ['Salud','Comercio','Educación','Servicios','Construcción','Turismo','Transporte','Retail','Industria','Tecnología','Gobierno','Otro'];
        $rubros = array_combine($rubros, $rubros);
        return [
            'slug' => 'clientes', 'table' => 'clientes', 'title' => 'Clientes', 'singular' => 'Cliente', 'group' => 'Clientes y Activos',
            'subtitle' => 'Empresas atendidas por el área de soporte técnico.', 'label_field' => 'razon_social', 'view' => true,
            'select' => "c.*, (SELECT COUNT(*) FROM tickets x WHERE x.cliente_id=c.id) AS n_tickets,
                         (SELECT COUNT(*) FROM activos a WHERE a.cliente_id=c.id) AS n_equipos,
                         (SELECT plan FROM contratos k WHERE k.cliente_id=c.id ORDER BY k.fecha_fin DESC LIMIT 1) AS plan",
            'from' => 'clientes c', 'order' => 'c.razon_social',
            'search' => ['c.razon_social', 'c.ruc', 'c.contacto', 'c.email'],
            'filters' => [
                ['name' => 'rubro', 'col' => 'c.rubro', 'label' => 'Rubro', 'options' => $rubros],
                ['name' => 'estado', 'col' => 'c.estado', 'label' => 'Estado', 'options' => ['1' => 'Activo', '0' => 'Inactivo']],
            ],
            'stats' => [
                ['Clientes registrados', 'SELECT COUNT(*) FROM clientes', 'bi-buildings-fill', '#4f46e5'],
                ['Clientes activos', 'SELECT COUNT(*) FROM clientes WHERE estado=1', 'bi-check-circle-fill', '#10b981'],
                ['Con contrato vigente', "SELECT COUNT(DISTINCT cliente_id) FROM contratos WHERE estado<>'vencido'", 'bi-file-earmark-check-fill', '#0ea5e9'],
                ['Equipos administrados', 'SELECT COUNT(*) FROM activos', 'bi-pc-display-horizontal', '#f59e0b'],
            ],
            'columns' => [
                ['label' => 'Cliente', 'key' => 'razon_social', 'sub' => 'ruc', 'main' => true],
                ['label' => 'Contacto', 'key' => 'contacto', 'sub' => 'telefono', 'main' => true],
                ['label' => 'Rubro', 'key' => 'rubro'],
                ['label' => 'Plan', 'html' => fn($r) => $r['plan'] ? pill($r['plan'], ['Premium' => 'soft-primary', 'Business' => 'soft-info', 'Básico' => 'muted'][$r['plan']]) : '<span class="text-muted small">Sin contrato</span>'],
                ['label' => 'Equipos', 'key' => 'n_equipos'],
                ['label' => 'Tickets', 'key' => 'n_tickets'],
                ['label' => 'Estado', 'html' => fn($r) => $r['estado'] ? pill('Activo', 'success') : pill('Inactivo', 'muted')],
            ],
            'fields' => [
                ['name' => 'razon_social', 'label' => 'Razón social', 'type' => 'text', 'required' => true, 'col' => 8, 'section' => 'Datos de la empresa', 'maxlength' => 150],
                ['name' => 'ruc', 'label' => 'RUC', 'type' => 'text', 'col' => 4, 'maxlength' => 15],
                ['name' => 'rubro', 'label' => 'Rubro', 'type' => 'select', 'options' => $rubros, 'col' => 4],
                ['name' => 'direccion', 'label' => 'Dirección', 'type' => 'text', 'col' => 8, 'maxlength' => 200],
                ['name' => 'contacto', 'label' => 'Persona de contacto', 'type' => 'text', 'col' => 4, 'section' => 'Contacto principal', 'maxlength' => 100],
                ['name' => 'telefono', 'label' => 'Teléfono', 'type' => 'text', 'col' => 4, 'maxlength' => 25],
                ['name' => 'email', 'label' => 'Correo electrónico', 'type' => 'email', 'col' => 4],
                ['name' => 'estado', 'label' => 'Cliente activo', 'type' => 'switch', 'col' => 12],
            ],
        ];
    }

    /** Ficha 360° del cliente */
    public function ver($id = 0): void
    {
        $this->guard();
        $db = Database::conn(); $id = (int)$id;
        $cli = $this->find($id);
        if (!$cli) { $this->flash('danger', 'El cliente no existe.'); $this->redirect('clientes'); }
        $q = fn($sql) => (function () use ($db, $sql, $id) { $s = $db->prepare($sql); $s->execute([$id]); return $s->fetchAll(); })();
        $crm = [];
        if (in_array(Auth::user()['rol'], ['Administrador', 'Supervisor', 'Comercial'], true)) {
            require_once ROOT_PATH . '/core/CrmBase.php'; require_once ROOT_PATH . '/core/FacBase.php';
            $crm = [
                'contactos' => $q('SELECT * FROM crm_contactos WHERE cliente_id=? ORDER BY principal DESC, nombre'),
                'ops' => $q("SELECT o.*, e.nombre etapa, e.color etapa_color FROM crm_oportunidades o JOIN crm_etapas e ON e.id=o.etapa_id WHERE o.cliente_id=? ORDER BY (o.estado='abierta') DESC, o.created_at DESC"),
                'cots' => $q('SELECT * FROM crm_cotizaciones WHERE cliente_id=? ORDER BY created_at DESC LIMIT 10'),
                'pend' => $q("SELECT a.*, CONCAT(u.nombres,' ',u.apellidos) AS responsable FROM crm_actividades a LEFT JOIN usuarios u ON u.id=a.responsable_id WHERE a.cliente_id=? AND a.completada=0 AND a.auto=0 ORDER BY a.fecha IS NULL, a.fecha"),
                'vendedores' => $db->query("SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) AS nombre FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE r.nombre IN ('Administrador','Supervisor','Comercial') AND u.estado=1 ORDER BY u.nombres")->fetchAll(),
            ];
            $crm['fac'] = [];
            if (in_array(Auth::user()['rol'], ['Administrador', 'Supervisor'], true)) {
                try {
                    $crm['fac'] = ['rows' => $q("SELECT f.id, f.codigo, f.total, f.pagado, f.fecha_emision, f.fecha_vencimiento, f.estado, (CASE WHEN f.estado='anulada' THEN 'anulada' WHEN f.estado='pagada' THEN 'pagada' WHEN f.fecha_vencimiento < CURDATE() THEN 'vencida' WHEN f.pagado > 0 THEN 'parcial' ELSE 'pendiente' END) est FROM fac_comprobantes f WHERE f.cliente_id=? ORDER BY f.fecha_emision DESC, f.id DESC LIMIT 12"),
                        'saldo' => (float)array_sum(array_map(fn($r) => $r['estado'] === 'emitida' ? $r['total'] - $r['pagado'] : 0, $q("SELECT total, pagado, estado FROM fac_comprobantes WHERE cliente_id=?"))), 'ok' => true];
                } catch (Throwable $e) {}
            }
            // línea de tiempo unificada: CRM + tickets + contratos
            $tl = [];
            foreach ($q("SELECT a.tipo, a.asunto, a.detalle, a.resultado, a.auto, a.completada, COALESCE(a.completada_at, a.fecha, a.created_at) f, CONCAT(u.nombres,' ',u.apellidos) quien FROM crm_actividades a LEFT JOIN usuarios u ON u.id=COALESCE(a.responsable_id,a.creado_por) WHERE a.cliente_id=? AND (a.completada=1 OR a.auto=1) ORDER BY f DESC LIMIT 40") as $a) {
                $t = ACT_TIPOS[$a['tipo']] ?? ACT_TIPOS['nota'];
                $tl[] = ['f' => $a['f'], 'ic' => $a['auto'] ? 'bi-lightning-charge-fill' : $t[1], 'color' => $a['auto'] ? '#8b5cf6' : $t[2], 'tit' => $a['asunto'], 'det' => $a['resultado'] ?: $a['detalle'], 'quien' => $a['quien'], 'tag' => $a['auto'] ? 'Sistema' : $t[0]];
            }
            foreach ($q('SELECT codigo, asunto, estado, prioridad, created_at FROM tickets WHERE cliente_id=? ORDER BY created_at DESC LIMIT 15') as $t)
                $tl[] = ['f' => $t['created_at'], 'ic' => 'bi-ticket-perforated-fill', 'color' => '#0ea5e9', 'tit' => 'Ticket ' . $t['codigo'] . ' · ' . $t['asunto'], 'det' => 'Prioridad ' . $t['prioridad'] . ' · ' . $t['estado'], 'quien' => '', 'tag' => 'Soporte'];
            foreach ($q('SELECT * FROM contratos WHERE cliente_id=? ORDER BY fecha_fin DESC') as $k) $tl[] = ['f' => $k['fecha_inicio'] . ' 00:00:00', 'ic' => 'bi-file-earmark-check-fill', 'color' => '#10b981', 'tit' => 'Contrato ' . $k['codigo'] . ' ' . $k['plan'], 'det' => 'Vigencia hasta ' . fdate($k['fecha_fin']) . ' · ' . money($k['monto_mensual']) . '/mes', 'quien' => '', 'tag' => 'Contrato'];
            usort($tl, fn($a, $b) => strcmp($b['f'], $a['f']));
            $crm['timeline'] = array_slice($tl, 0, 40);
        }
        $this->render('clientes/ver', [
            'cli' => $cli, 'crm' => $crm,
            'contratos' => $q('SELECT * FROM contratos WHERE cliente_id=? ORDER BY fecha_fin DESC'),
            'activos' => $q('SELECT * FROM activos WHERE cliente_id=? ORDER BY codigo LIMIT 12'),
            'tickets' => $q("SELECT t.*, cat.nombre categoria FROM tickets t JOIN categorias cat ON cat.id=t.categoria_id WHERE t.cliente_id=? ORDER BY t.created_at DESC LIMIT 8"),
            'resumen' => $q("SELECT COUNT(*) total, SUM(estado IN ('abierto','en_proceso','pendiente')) abiertos, SUM(resuelto_at IS NOT NULL AND resuelto_at<=fecha_limite) en_sla, SUM(resuelto_at IS NOT NULL) resueltos FROM tickets WHERE cliente_id=?")[0],
        ], $cli['razon_social']);
    }
}

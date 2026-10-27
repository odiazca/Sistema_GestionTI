<?php
require_once ROOT_PATH . '/core/CrmBase.php';

class OportunidadesController extends CrmBase
{
    private const PLANES = ['Básico' => [8, 72, 420], 'Business' => [4, 36, 950], 'Premium' => [2, 24, 1800]];

    private function filtros(): array
    {
        $w = []; $p = [];
        if (($q = trim($_GET['q'] ?? '')) !== '') { $w[] = '(o.titulo LIKE :q1 OR c.razon_social LIKE :q2 OR o.codigo LIKE :q3)'; $p[':q1'] = $p[':q2'] = $p[':q3'] = "%$q%"; }
        if (isset(OPP_TIPOS[$_GET['tipo'] ?? ''])) { $w[] = 'o.tipo = :t'; $p[':t'] = $_GET['tipo']; }
        $r = $_GET['resp'] ?? '';
        if ($r === 'mine') { $w[] = 'o.responsable_id = :r'; $p[':r'] = (int)Auth::user()['id']; } elseif (ctype_digit((string)$r) && $r !== '') { $w[] = 'o.responsable_id = :r'; $p[':r'] = (int)$r; }
        return [$w, $p];
    }
    private const SEL = "o.*, c.razon_social AS cliente, e.nombre AS etapa, e.color AS etapa_color, CONCAT(u.nombres,' ',u.apellidos) AS responsable, u.avatar_color AS color,
        (SELECT MIN(a.fecha) FROM crm_actividades a WHERE a.oportunidad_id=o.id AND a.completada=0) AS prox_act,
        (SELECT a.tipo FROM crm_actividades a WHERE a.oportunidad_id=o.id AND a.completada=0 ORDER BY a.fecha LIMIT 1) AS prox_tipo";
    private const FROM = "crm_oportunidades o JOIN clientes c ON c.id=o.cliente_id JOIN crm_etapas e ON e.id=o.etapa_id LEFT JOIN usuarios u ON u.id=o.responsable_id";

    public function index(): void
    {
        $this->guard(); [$w, $p] = $this->filtros(); $lista = ($_GET['vista'] ?? '') === 'lista';
        $etapas = $this->etapas();
        $data = ['etapas' => $etapas, 'vendedores' => $this->vendedores(), 'lista' => $lista];
        if ($lista) {
            $w2 = $w; if (isset(['abierta' => 1, 'ganada' => 1, 'perdida' => 1][$_GET['estado'] ?? ''])) { $w2[] = 'o.estado = :s'; $p[':s'] = $_GET['estado']; }
            $W = $w2 ? ' WHERE ' . implode(' AND ', $w2) : '';
            $total = (int)$this->val("SELECT COUNT(*) FROM " . self::FROM . $W, $p);
            $pg = $this->paginate($total, 12, (int)($_GET['page'] ?? 1));
            $data += ['rows' => $this->all("SELECT " . self::SEL . " FROM " . self::FROM . "$W ORDER BY FIELD(o.estado,'abierta','ganada','perdida'), o.fecha_cierre_estimada ASC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p), 'pg' => $pg];
        } else {
            $cols = [];
            foreach ($etapas as $e) {
                $ww = $w; $pp = $p; $ww[] = 'o.etapa_id = :et'; $pp[':et'] = $e['id'];
                if ($e['tipo'] !== 'abierta') $ww[] = 'o.fecha_cierre_real >= CURDATE() - INTERVAL 30 DAY';
                $W = ' WHERE ' . implode(' AND ', $ww);
                $sum = $this->one("SELECT COUNT(*) n, COALESCE(SUM(o.valor),0) valor, COALESCE(SUM(o.valor*o.probabilidad/100),0) pond FROM " . self::FROM . $W, $pp);
                $cols[] = $e + ['n' => (int)$sum['n'], 'valor' => (float)$sum['valor'], 'pond' => (float)$sum['pond'],
                                'cards' => $this->all("SELECT " . self::SEL . " FROM " . self::FROM . "$W ORDER BY o.valor DESC LIMIT 30", $pp)];
            }
            $data['cols'] = $cols;
        }
        $data['resumen'] = $this->one("SELECT COUNT(*) n, COALESCE(SUM(valor),0) valor, COALESCE(SUM(valor*probabilidad/100),0) pond, SUM(fecha_cierre_estimada < CURDATE()) atrasadas FROM crm_oportunidades WHERE estado='abierta'");
        $this->render('oportunidades/index', $data, 'Pipeline de ventas');
    }

    private function formData(?array $row, array $errors = []): array
    {
        $contactos = $this->all('SELECT id, cliente_id, nombre, cargo FROM crm_contactos ORDER BY principal DESC, nombre');
        return ['row' => $row, 'errors' => $errors, 'id' => (int)($row['id'] ?? 0), 'clientes' => $this->clientes(), 'contactos' => $contactos, 'etapas' => array_values(array_filter($this->etapas(), fn($e) => $e['tipo'] === 'abierta')), 'vendedores' => $this->vendedores()];
    }

    public function crear(): void
    {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar(0); return; }
        $et = $this->one("SELECT id, probabilidad FROM crm_etapas WHERE tipo='abierta' ORDER BY orden LIMIT 1");
        $pre = ['cliente_id' => (int)($_GET['cliente'] ?? 0) ?: '', 'etapa_id' => $et['id'], 'probabilidad' => $et['probabilidad'], 'responsable_id' => Auth::user()['id'], 'tipo' => 'contrato', 'fecha_cierre_estimada' => date('Y-m-d', strtotime('+30 days')), 'valor' => ''];
        $this->render('oportunidades/form', $this->formData($pre), 'Nueva oportunidad');
    }

    public function editar($id = 0): void
    {
        $this->guard(); $row = $this->one('SELECT * FROM crm_oportunidades WHERE id=?', [(int)$id]);
        if (!$row) { $this->flash('danger', 'La oportunidad no existe.'); $this->redirect('oportunidades'); }
        if ($row['estado'] !== 'abierta') { $this->flash('warning', 'Reabre la oportunidad para poder editarla.'); $this->redirect("oportunidades/ver/$id"); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar((int)$id); return; }
        $this->render('oportunidades/form', $this->formData($row), 'Editar oportunidad');
    }

    private function guardar(int $id): void
    {
        $this->requirePost($id ? "oportunidades/editar/$id" : 'oportunidades/crear');
        $et = $this->one("SELECT * FROM crm_etapas WHERE id=? AND tipo='abierta'", [(int)($_POST['etapa_id'] ?? 0)]);
        $d = ['titulo' => $this->str('titulo', 180), 'cliente_id' => (int)($_POST['cliente_id'] ?? 0), 'contacto_id' => (int)($_POST['contacto_id'] ?? 0) ?: null, 'tipo' => $_POST['tipo'] ?? '',
              'valor' => (float)str_replace(',', '', (string)($_POST['valor'] ?? 0)), 'probabilidad' => (int)($_POST['probabilidad'] ?? 0), 'cierre' => $_POST['fecha_cierre_estimada'] ?? '',
              'responsable_id' => (int)($_POST['responsable_id'] ?? 0) ?: null, 'descripcion' => $this->str('descripcion', 2000), 'etapa_id' => (int)($et['id'] ?? 0)];
        $err = [];
        if (!$d['titulo']) $err['titulo'] = 'Escribe un título descriptivo.';
        if (!$this->val('SELECT COUNT(*) FROM clientes WHERE id=?', [$d['cliente_id']])) $err['cliente_id'] = 'Selecciona un cliente.';
        if ($d['contacto_id'] && !$this->val('SELECT COUNT(*) FROM crm_contactos WHERE id=? AND cliente_id=?', [$d['contacto_id'], $d['cliente_id']])) $d['contacto_id'] = null;
        if (!isset(OPP_TIPOS[$d['tipo']])) $err['tipo'] = 'Tipo no válido.';
        if (!$et) $err['etapa_id'] = 'Selecciona la etapa.';
        if ($d['valor'] < 0 || $d['valor'] > 99999999) $err['valor'] = 'Valor no válido.';
        if ($d['probabilidad'] < 0 || $d['probabilidad'] > 100) $err['probabilidad'] = 'Entre 0 y 100.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['cierre'])) $err['fecha_cierre_estimada'] = 'Indica la fecha estimada de cierre.';
        if ($err) { $row = $d + ['id' => $id, 'fecha_cierre_estimada' => $d['cierre']]; $this->render('oportunidades/form', $this->formData($row, $err), $id ? 'Editar oportunidad' : 'Nueva oportunidad'); return; }
        if ($id) {
            $old = $this->one('SELECT etapa_id FROM crm_oportunidades WHERE id=?', [$id]);
            $this->run('UPDATE crm_oportunidades SET titulo=?, cliente_id=?, contacto_id=?, tipo=?, valor=?, probabilidad=?, fecha_cierre_estimada=?, responsable_id=?, descripcion=?, etapa_id=? WHERE id=?',
                [$d['titulo'], $d['cliente_id'], $d['contacto_id'], $d['tipo'], $d['valor'], $d['probabilidad'], $d['cierre'], $d['responsable_id'], $d['descripcion'], $d['etapa_id'], $id]);
            if ((int)$old['etapa_id'] !== $d['etapa_id']) $this->evento('Etapa cambiada a «' . $et['nombre'] . '»', $d['cliente_id'], null, $id);
            $this->flash('success', 'Oportunidad actualizada.'); $this->redirect("oportunidades/ver/$id");
        }
        $cod = $this->codigo('crm_oportunidades', 'OP');
        $this->run('INSERT INTO crm_oportunidades(codigo,titulo,cliente_id,contacto_id,etapa_id,tipo,valor,probabilidad,fecha_cierre_estimada,responsable_id,descripcion) VALUES(?,?,?,?,?,?,?,?,?,?,?)',
            [$cod, $d['titulo'], $d['cliente_id'], $d['contacto_id'], $d['etapa_id'], $d['tipo'], $d['valor'], $d['probabilidad'], $d['cierre'], $d['responsable_id'], $d['descripcion']]);
        $nid = (int)$this->db()->lastInsertId();
        $this->evento('Oportunidad creada en «' . $et['nombre'] . '» por ' . money($d['valor']), $d['cliente_id'], null, $nid);
        $this->audit("Creó oportunidad $cod", $d['titulo'], 'bi-bullseye', '#8b5cf6');
        $this->flash('success', "Oportunidad $cod creada. Agenda la próxima acción."); $this->redirect("oportunidades/ver/$nid");
    }

    public function ver($id = 0): void
    {
        $this->guard(); $id = (int)$id;
        $o = $this->one("SELECT " . self::SEL . ", e.tipo AS etapa_tipo, e.orden, ct.nombre AS contacto, ct.cargo AS contacto_cargo, ct.email AS contacto_email, ct.telefono AS contacto_tel, k.codigo AS contrato FROM " . self::FROM . " LEFT JOIN crm_contactos ct ON ct.id=o.contacto_id LEFT JOIN contratos k ON k.id=o.contrato_id WHERE o.id=?", [$id]);
        if (!$o) { $this->flash('danger', 'La oportunidad no existe.'); $this->redirect('oportunidades'); }
        $this->run("UPDATE crm_cotizaciones SET estado='vencida' WHERE oportunidad_id=? AND estado IN ('borrador','enviada') AND valida_hasta < CURDATE()", [$id]);
        $this->render('oportunidades/ver', [
            'o' => $o, 'etapas' => $this->etapas(), 'vendedores' => $this->vendedores(),
            'acts' => $this->all("SELECT a.*, CONCAT(u.nombres,' ',u.apellidos) AS responsable FROM crm_actividades a LEFT JOIN usuarios u ON u.id=a.responsable_id WHERE a.oportunidad_id=? ORDER BY (a.completada=0 AND a.auto=0) DESC, COALESCE(a.fecha, a.created_at) DESC", [$id]),
            'cots' => $this->all('SELECT * FROM crm_cotizaciones WHERE oportunidad_id=? ORDER BY id DESC', [$id]),
            'lead' => $o['lead_id'] ? $this->one('SELECT id, codigo, empresa FROM crm_leads WHERE id=?', [$o['lead_id']]) : null,
        ], $o['codigo'] . ' · ' . $o['titulo']);
    }

    /** Kanban: mover a una etapa abierta (AJAX). Ganada/Perdida usan sus propios formularios. */
    public function mover(): void
    {
        $b = $this->jsonBody(); $id = (int)($b['id'] ?? 0);
        $o = $this->one('SELECT * FROM crm_oportunidades WHERE id=?', [$id]); $e = $this->one('SELECT * FROM crm_etapas WHERE id=?', [(int)($b['etapa_id'] ?? 0)]);
        if (!$o || !$e) $this->json(['ok' => false, 'error' => 'Datos no válidos.'], 422);
        if ($o['estado'] !== 'abierta') $this->json(['ok' => false, 'error' => 'La oportunidad ya está cerrada. Reábrela para moverla.'], 422);
        if ($e['tipo'] !== 'abierta') $this->json(['ok' => false, 'error' => 'Para cerrar usa «Ganada» o «Perdida».', 'cerrar' => $e['tipo']], 422);
        if ((int)$o['etapa_id'] === (int)$e['id']) $this->json(['ok' => true]);
        $ant = $this->val('SELECT nombre FROM crm_etapas WHERE id=?', [$o['etapa_id']]);
        $this->run('UPDATE crm_oportunidades SET etapa_id=?, probabilidad=? WHERE id=?', [$e['id'], $e['probabilidad'], $id]);
        $this->evento("Etapa: $ant → {$e['nombre']}", (int)$o['cliente_id'], null, $id);
        $this->json(['ok' => true]);
    }

    public function ganar($id = 0): void
    {
        $this->guard(); $this->requirePost("oportunidades/ver/$id"); $id = (int)$id;
        $o = $this->one("SELECT * FROM crm_oportunidades WHERE id=? AND estado='abierta'", [$id]);
        if (!$o) { $this->flash('danger', 'La oportunidad no existe o ya está cerrada.'); $this->redirect('oportunidades'); }
        $etG = $this->one("SELECT id FROM crm_etapas WHERE tipo='ganada' LIMIT 1"); $db = $this->db(); $contrato = null;
        try {
            $db->beginTransaction();
            if (!empty($_POST['crear_contrato'])) {
                $plan = $_POST['plan'] ?? 'Básico'; if (!isset(self::PLANES[$plan])) $plan = 'Básico';
                $ini = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['fecha_inicio'] ?? '') ? $_POST['fecha_inicio'] : date('Y-m-d');
                $meses = max(1, min(60, (int)($_POST['meses'] ?? 12))); $fin = date('Y-m-d', strtotime("$ini +$meses months"));
                $monto = (float)($_POST['monto_mensual'] ?? 0) ?: self::PLANES[$plan][2];
                $n = (int)$this->val('SELECT COALESCE(MAX(id),0)+1 FROM contratos'); $cod = sprintf('CT-%s-%03d', date('Y'), $n);
                $dias = (strtotime($fin) - strtotime('today')) / 86400; $est = $dias < 0 ? 'vencido' : ($dias <= 45 ? 'por_vencer' : 'vigente');
                $this->run('INSERT INTO contratos(cliente_id,codigo,plan,sla_respuesta_horas,sla_resolucion_horas,fecha_inicio,fecha_fin,monto_mensual,estado) VALUES(?,?,?,?,?,?,?,?,?)',
                    [$o['cliente_id'], $cod, $plan, self::PLANES[$plan][0], self::PLANES[$plan][1], $ini, $fin, $monto, $est]);
                $contrato = (int)$db->lastInsertId();
            }
            $this->run("UPDATE crm_oportunidades SET etapa_id=?, estado='ganada', probabilidad=100, fecha_cierre_real=CURDATE(), contrato_id=?, motivo_perdida=NULL WHERE id=?", [$etG['id'], $contrato, $id]);
            $this->run('UPDATE clientes SET estado=1 WHERE id=?', [$o['cliente_id']]);
            $this->evento('¡Oportunidad ganada por ' . money($o['valor']) . '!' . ($contrato ? ' Contrato creado.' : ''), (int)$o['cliente_id'], null, $id);
            $db->commit();
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); $this->flash('danger', 'No se pudo cerrar la oportunidad: ' . $e->getMessage()); $this->redirect("oportunidades/ver/$id"); }
        $this->audit('Ganó oportunidad ' . $o['codigo'], $o['titulo'] . ' · ' . money($o['valor']), 'bi-trophy-fill', '#10b981');
        $this->flash('success', '🏆 ¡Oportunidad ganada!' . ($contrato ? ' Se creó el contrato y el cliente ya cuenta con su SLA.' : ''));
        $this->redirect($contrato ? 'contratos' : "oportunidades/ver/$id");
    }

    public function perder($id = 0): void
    {
        $this->guard(); $this->requirePost("oportunidades/ver/$id"); $id = (int)$id; $m = $this->str('motivo', 200);
        $o = $this->one("SELECT * FROM crm_oportunidades WHERE id=? AND estado='abierta'", [$id]);
        if (!$o) { $this->flash('danger', 'La oportunidad no existe o ya está cerrada.'); $this->redirect('oportunidades'); }
        if (!$m) { $this->flash('danger', 'Indica el motivo de pérdida: ayuda a mejorar las próximas ventas.'); $this->redirect("oportunidades/ver/$id"); }
        $etP = $this->one("SELECT id FROM crm_etapas WHERE tipo='perdida' LIMIT 1");
        $this->run("UPDATE crm_oportunidades SET etapa_id=?, estado='perdida', probabilidad=0, fecha_cierre_real=CURDATE(), motivo_perdida=? WHERE id=?", [$etP['id'], $m, $id]);
        $this->evento('Oportunidad perdida: ' . $m, (int)$o['cliente_id'], null, $id);
        $this->audit('Perdió oportunidad ' . $o['codigo'], $m, 'bi-x-octagon-fill', '#ef4444');
        $this->flash('warning', 'Oportunidad marcada como perdida.'); $this->redirect("oportunidades/ver/$id");
    }

    public function reabrir($id = 0): void
    {
        $this->guard(); $this->requirePost("oportunidades/ver/$id"); $id = (int)$id;
        $o = $this->one("SELECT * FROM crm_oportunidades WHERE id=? AND estado<>'abierta'", [$id]); if (!$o) $this->redirect("oportunidades/ver/$id");
        $e = $this->one("SELECT id, probabilidad FROM crm_etapas WHERE tipo='abierta' ORDER BY orden DESC LIMIT 1");
        $this->run("UPDATE crm_oportunidades SET etapa_id=?, estado='abierta', probabilidad=?, fecha_cierre_real=NULL, motivo_perdida=NULL WHERE id=?", [$e['id'], $e['probabilidad'], $id]);
        $this->evento('Oportunidad reabierta', (int)$o['cliente_id'], null, $id); $this->flash('success', 'Oportunidad reabierta en la última etapa.'); $this->redirect("oportunidades/ver/$id");
    }

    public function eliminar($id = 0): void
    {
        $this->requireRole(['Administrador', 'Supervisor']); $this->requirePost('oportunidades'); $id = (int)$id;
        $o = $this->one('SELECT codigo, titulo FROM crm_oportunidades WHERE id=?', [$id]); if (!$o) $this->redirect('oportunidades');
        $this->run('UPDATE crm_cotizaciones SET oportunidad_id=NULL WHERE oportunidad_id=?', [$id]); $this->run('DELETE FROM crm_actividades WHERE oportunidad_id=?', [$id]); $this->run('DELETE FROM crm_oportunidades WHERE id=?', [$id]);
        $this->audit('Eliminó oportunidad ' . $o['codigo'], $o['titulo'], 'bi-trash3-fill', '#ef4444'); $this->flash('success', 'Oportunidad eliminada.'); $this->redirect('oportunidades');
    }

    public function exportar(): void
    {
        $this->guard(); [$w, $p] = $this->filtros(); $W = $w ? ' WHERE ' . implode(' AND ', $w) : '';
        $rows = $this->all("SELECT o.codigo, o.titulo, c.razon_social, o.tipo, e.nombre, o.estado, o.valor, o.probabilidad, ROUND(o.valor*o.probabilidad/100,2) pond, o.fecha_cierre_estimada, o.fecha_cierre_real, CONCAT(u.nombres,' ',u.apellidos) resp, o.motivo_perdida FROM " . self::FROM . "$W ORDER BY o.created_at DESC", $p);
        $this->csv('oportunidades', ['Código', 'Título', 'Cliente', 'Tipo', 'Etapa', 'Estado', 'Valor', 'Prob. %', 'Valor ponderado', 'Cierre estimado', 'Cierre real', 'Responsable', 'Motivo pérdida'], $rows);
    }
}

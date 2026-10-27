<?php
require_once ROOT_PATH . '/core/CrmBase.php';

class LeadsController extends CrmBase
{
    private function filtros(): array
    {
        $w = []; $p = [];
        if (($q = trim($_GET['q'] ?? '')) !== '') { $w[] = '(l.empresa LIKE :q1 OR l.contacto LIKE :q2 OR l.email LIKE :q3 OR l.codigo LIKE :q4)'; $p[':q1'] = $p[':q2'] = $p[':q3'] = $p[':q4'] = "%$q%"; }
        if (isset(LEAD_ESTADOS[$_GET['estado'] ?? ''])) { $w[] = 'l.estado = :e'; $p[':e'] = $_GET['estado']; }
        elseif (($_GET['estado'] ?? '') === 'abiertos') $w[] = "l.estado IN ('nuevo','contactado','calificado')";
        if (isset(LEAD_ORIGENES[$_GET['origen'] ?? ''])) { $w[] = 'l.origen = :o'; $p[':o'] = $_GET['origen']; }
        $r = $_GET['resp'] ?? '';
        if ($r === 'mine') { $w[] = 'l.responsable_id = :r'; $p[':r'] = (int)Auth::user()['id']; } elseif (ctype_digit((string)$r) && $r !== '') { $w[] = 'l.responsable_id = :r'; $p[':r'] = (int)$r; }
        return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $p];
    }

    private const SEL = "l.*, CONCAT(u.nombres,' ',u.apellidos) AS responsable, u.avatar_color AS color, (SELECT COUNT(*) FROM crm_actividades a WHERE a.lead_id=l.id AND a.auto=0) AS n_act, (SELECT MIN(a.fecha) FROM crm_actividades a WHERE a.lead_id=l.id AND a.completada=0) AS prox";

    public function index(): void
    {
        $this->guard(); [$W, $p] = $this->filtros();
        $total = (int)$this->val("SELECT COUNT(*) FROM crm_leads l$W", $p);
        $pg = $this->paginate($total, 12, (int)($_GET['page'] ?? 1));
        $rows = $this->all("SELECT " . self::SEL . " FROM crm_leads l LEFT JOIN usuarios u ON u.id=l.responsable_id$W ORDER BY FIELD(l.estado,'nuevo','contactado','calificado','convertido','descartado'), l.score DESC, l.created_at DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
        $st = $this->one("SELECT COUNT(*) total, SUM(estado IN ('nuevo','contactado','calificado')) abiertos, SUM(estado='nuevo' AND created_at >= NOW() - INTERVAL 7 DAY) nuevos7, SUM(estado='calificado') calificados, SUM(estado='convertido') convertidos, COALESCE(ROUND(AVG(CASE WHEN estado IN ('nuevo','contactado','calificado') THEN score END)),0) score FROM crm_leads");
        $this->render('leads/index', ['rows' => $rows, 'pg' => $pg, 'st' => $st, 'vendedores' => $this->vendedores()], 'Leads y prospectos');
    }

    private function formData(?array $row, array $errors = []): array
    {
        return ['row' => $row, 'errors' => $errors, 'id' => (int)($row['id'] ?? 0), 'vendedores' => $this->vendedores()];
    }

    public function crear(): void
    {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar(0); return; }
        $this->render('leads/form', $this->formData(['responsable_id' => Auth::user()['id'], 'origen' => 'web', 'estado' => 'nuevo']), 'Nuevo lead');
    }

    public function editar($id = 0): void
    {
        $this->guard(); $row = $this->one('SELECT * FROM crm_leads WHERE id=?', [(int)$id]);
        if (!$row) { $this->flash('danger', 'El lead no existe.'); $this->redirect('leads'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar((int)$id); return; }
        $this->render('leads/form', $this->formData($row), 'Editar lead');
    }

    private function guardar(int $id): void
    {
        $this->requirePost($id ? "leads/editar/$id" : 'leads/crear');
        $d = ['empresa' => $this->str('empresa', 150), 'contacto' => $this->str('contacto', 100), 'cargo' => $this->str('cargo', 80), 'email' => $this->str('email', 120),
              'telefono' => $this->str('telefono', 25), 'rubro' => $this->str('rubro', 80), 'origen' => $_POST['origen'] ?? 'otro', 'interes' => $this->str('interes', 200),
              'responsable_id' => (int)($_POST['responsable_id'] ?? 0) ?: null, 'notas' => $this->str('notas', 2000)];
        $err = [];
        if (!$d['empresa']) $err['empresa'] = 'Indica la empresa o prospecto.';
        if (!isset(LEAD_ORIGENES[$d['origen']])) $err['origen'] = 'Origen no válido.';
        if ($d['email'] && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Correo no válido.';
        if (!$d['email'] && !$d['telefono']) $err['telefono'] = 'Indica al menos un teléfono o correo de contacto.';
        if ($err) { $row = $d + ['id' => $id, 'estado' => 'nuevo']; $this->render('leads/form', $this->formData($row, $err), $id ? 'Editar lead' : 'Nuevo lead'); return; }
        $nAct = $id ? (int)$this->val('SELECT COUNT(*) FROM crm_actividades WHERE lead_id=? AND auto=0', [$id]) : 0;
        if ($id) {
            $cur = $this->one('SELECT estado FROM crm_leads WHERE id=?', [$id]);
            $d['score'] = lead_score($d + ['estado' => $cur['estado']], $nAct);
            $this->run('UPDATE crm_leads SET empresa=:empresa, contacto=:contacto, cargo=:cargo, email=:email, telefono=:telefono, rubro=:rubro, origen=:origen, interes=:interes, responsable_id=:responsable_id, notas=:notas, score=:score WHERE id=:id', $d + ['id' => $id]);
            $this->flash('success', 'Lead actualizado.'); $this->redirect("leads/ver/$id");
        }
        $d['score'] = lead_score($d); $d['codigo'] = $this->codigo('crm_leads', 'LD');
        $this->run('INSERT INTO crm_leads(codigo,empresa,contacto,cargo,email,telefono,rubro,origen,interes,responsable_id,notas,score) VALUES(:codigo,:empresa,:contacto,:cargo,:email,:telefono,:rubro,:origen,:interes,:responsable_id,:notas,:score)', $d);
        $nid = (int)$this->db()->lastInsertId();
        $this->evento('Lead registrado (' . LEAD_ORIGENES[$d['origen']] . ')', null, $nid);
        $this->audit('Registró lead ' . $d['codigo'], $d['empresa'], 'bi-person-plus-fill', '#0ea5e9');
        $this->flash('success', "Lead {$d['codigo']} registrado. Agenda el primer contacto."); $this->redirect("leads/ver/$nid");
    }

    public function ver($id = 0): void
    {
        $this->guard(); $id = (int)$id;
        $l = $this->one("SELECT " . self::SEL . " FROM crm_leads l LEFT JOIN usuarios u ON u.id=l.responsable_id WHERE l.id=?", [$id]);
        if (!$l) { $this->flash('danger', 'El lead no existe.'); $this->redirect('leads'); }
        $acts = $this->all("SELECT a.*, CONCAT(u.nombres,' ',u.apellidos) AS responsable FROM crm_actividades a LEFT JOIN usuarios u ON u.id=a.responsable_id WHERE a.lead_id=? ORDER BY (a.completada=0) DESC, COALESCE(a.fecha, a.created_at) DESC", [$id]);
        $this->render('leads/ver', ['l' => $l, 'acts' => $acts, 'clientes' => $this->clientes(), 'vendedores' => $this->vendedores(),
            'cliente' => $l['cliente_id'] ? $this->one('SELECT id, razon_social FROM clientes WHERE id=?', [$l['cliente_id']]) : null,
            'opp' => $this->one('SELECT id, codigo FROM crm_oportunidades WHERE lead_id=? ORDER BY id DESC LIMIT 1', [$id])], $l['empresa']);
    }

    /** Cambio rápido de estado: contactado / calificado / nuevo */
    public function estado($id = 0): void
    {
        $this->guard(); $this->requirePost("leads/ver/$id"); $id = (int)$id; $e = $_POST['estado'] ?? '';
        $l = $this->one('SELECT * FROM crm_leads WHERE id=?', [$id]);
        if (!$l || !in_array($e, ['nuevo', 'contactado', 'calificado'], true) || in_array($l['estado'], ['convertido'], true)) { $this->flash('danger', 'No se pudo cambiar el estado.'); $this->redirect("leads/ver/$id"); }
        $nAct = (int)$this->val('SELECT COUNT(*) FROM crm_actividades WHERE lead_id=? AND auto=0', [$id]);
        $this->run('UPDATE crm_leads SET estado=?, score=?, motivo_descarte=NULL WHERE id=?', [$e, lead_score(array_merge($l, ['estado' => $e]), $nAct), $id]);
        $this->evento('Estado del lead: ' . LEAD_ESTADOS[$l['estado']] . ' → ' . LEAD_ESTADOS[$e], null, $id);
        $this->flash('success', 'Estado actualizado a «' . LEAD_ESTADOS[$e] . '».'); $this->back("leads/ver/$id");
    }

    public function descartar($id = 0): void
    {
        $this->guard(); $this->requirePost("leads/ver/$id"); $id = (int)$id; $m = $this->str('motivo', 200);
        if (!$m) { $this->flash('danger', 'Indica el motivo del descarte.'); $this->redirect("leads/ver/$id"); }
        $this->run("UPDATE crm_leads SET estado='descartado', motivo_descarte=? WHERE id=? AND estado<>'convertido'", [$m, $id]);
        $this->evento('Lead descartado: ' . $m, null, $id); $this->flash('success', 'Lead descartado.'); $this->redirect("leads/ver/$id");
    }

    public function reabrir($id = 0): void
    {
        $this->guard(); $this->requirePost("leads/ver/$id"); $id = (int)$id;
        $this->run("UPDATE crm_leads SET estado='nuevo', motivo_descarte=NULL WHERE id=? AND estado='descartado'", [$id]);
        $this->evento('Lead reabierto', null, $id); $this->flash('success', 'Lead reabierto.'); $this->redirect("leads/ver/$id");
    }

    /** Convierte el lead en cliente (o lo vincula a uno existente) y opcionalmente crea la oportunidad */
    public function convertir($id = 0): void
    {
        $this->guard(); $this->requirePost("leads/ver/$id"); $id = (int)$id;
        $l = $this->one('SELECT * FROM crm_leads WHERE id=?', [$id]);
        if (!$l || $l['estado'] === 'convertido') { $this->flash('danger', 'El lead no existe o ya fue convertido.'); $this->redirect('leads'); }
        $db = $this->db();
        try {
            $db->beginTransaction();
            $cid = (int)($_POST['cliente_id'] ?? 0);
            if ($cid) { if (!$this->val('SELECT COUNT(*) FROM clientes WHERE id=?', [$cid])) throw new RuntimeException('Cliente no válido.'); }
            else {
                $this->run('INSERT INTO clientes(razon_social,rubro,telefono,email,contacto,estado) VALUES(?,?,?,?,?,1)', [$l['empresa'], $l['rubro'], $l['telefono'], $l['email'], $l['contacto']]);
                $cid = (int)$db->lastInsertId();
            }
            $ctId = null;
            if ($l['contacto']) {
                $this->run('INSERT INTO crm_contactos(cliente_id,nombre,cargo,email,telefono,principal) VALUES(?,?,?,?,?,?)', [$cid, $l['contacto'], $l['cargo'], $l['email'], $l['telefono'], (int)!$this->val('SELECT COUNT(*) FROM crm_contactos WHERE cliente_id=? AND principal=1', [$cid])]);
                $ctId = (int)$db->lastInsertId();
            }
            $oid = null;
            if (!empty($_POST['crear_oportunidad'])) {
                $et = (int)$this->val("SELECT id FROM crm_etapas WHERE tipo='abierta' ORDER BY orden LIMIT 1");
                $prob = (int)$this->val('SELECT probabilidad FROM crm_etapas WHERE id=?', [$et]);
                $titulo = $this->str('titulo', 180) ?: ($l['interes'] ?: 'Oportunidad ' . $l['empresa']);
                $valor = max(0, (float)($_POST['valor'] ?? 0)); $cierre = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['cierre'] ?? '') ? $_POST['cierre'] : date('Y-m-d', strtotime('+30 days'));
                $this->run('INSERT INTO crm_oportunidades(codigo,titulo,cliente_id,contacto_id,lead_id,etapa_id,tipo,valor,probabilidad,fecha_cierre_estimada,responsable_id,origen,descripcion) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$this->codigo('crm_oportunidades', 'OP'), $titulo, $cid, $ctId, $id, $et, 'contrato', $valor, $prob, $cierre, $l['responsable_id'] ?: Auth::user()['id'], $l['origen'], $l['interes']]);
                $oid = (int)$db->lastInsertId();
            }
            $this->run("UPDATE crm_leads SET estado='convertido', cliente_id=?, score=100 WHERE id=?", [$cid, $id]);
            $this->run('UPDATE crm_actividades SET cliente_id=?, oportunidad_id=COALESCE(oportunidad_id,?) WHERE lead_id=?', [$cid, $oid, $id]);
            $this->evento('Lead convertido en cliente' . ($oid ? ' con oportunidad' : ''), $cid, $id, $oid);
            $db->commit();
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); $this->flash('danger', 'No se pudo convertir: ' . $e->getMessage()); $this->redirect("leads/ver/$id"); }
        $this->audit('Convirtió lead ' . $l['codigo'], $l['empresa'], 'bi-arrow-repeat', '#10b981');
        $this->flash('success', '¡Lead convertido! ' . ($oid ? 'Ya está en el pipeline.' : 'Ya es cliente.'));
        $this->redirect($oid ? "oportunidades/ver/$oid" : "clientes/ver/$cid");
    }

    public function eliminar($id = 0): void
    {
        $this->requireRole(['Administrador', 'Supervisor']); $this->requirePost('leads'); $id = (int)$id;
        $l = $this->one('SELECT codigo, empresa, estado FROM crm_leads WHERE id=?', [$id]);
        if (!$l) $this->redirect('leads');
        if ($l['estado'] === 'convertido') { $this->flash('danger', 'No se puede eliminar un lead convertido (queda como historial).'); $this->redirect("leads/ver/$id"); }
        $this->run('DELETE FROM crm_actividades WHERE lead_id=?', [$id]); $this->run('DELETE FROM crm_leads WHERE id=?', [$id]);
        $this->audit('Eliminó lead ' . $l['codigo'], $l['empresa'], 'bi-trash3-fill', '#ef4444'); $this->flash('success', 'Lead eliminado.'); $this->redirect('leads');
    }

    public function exportar(): void
    {
        $this->guard(); [$W, $p] = $this->filtros();
        $rows = $this->all("SELECT l.codigo, l.empresa, l.contacto, l.cargo, l.email, l.telefono, l.rubro, l.origen, l.interes, l.estado, l.score, CONCAT(u.nombres,' ',u.apellidos) resp, l.created_at FROM crm_leads l LEFT JOIN usuarios u ON u.id=l.responsable_id$W ORDER BY l.created_at DESC", $p);
        $this->csv('leads', ['Código', 'Empresa', 'Contacto', 'Cargo', 'Correo', 'Teléfono', 'Rubro', 'Origen', 'Interés', 'Estado', 'Score', 'Responsable', 'Registrado'], $rows);
    }
}

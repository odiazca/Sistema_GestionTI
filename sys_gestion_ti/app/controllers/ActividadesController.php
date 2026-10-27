<?php
require_once ROOT_PATH . '/core/CrmBase.php';

class ActividadesController extends CrmBase
{
    private const SEL = "a.*, c.razon_social AS cliente, l.empresa AS lead_nombre, l.codigo AS lead_codigo, o.codigo AS op_codigo, o.titulo AS op_titulo, CONCAT(u.nombres,' ',u.apellidos) AS responsable, u.avatar_color AS color";
    private const FROM = "crm_actividades a LEFT JOIN clientes c ON c.id=a.cliente_id LEFT JOIN crm_leads l ON l.id=a.lead_id LEFT JOIN crm_oportunidades o ON o.id=a.oportunidad_id LEFT JOIN usuarios u ON u.id=a.responsable_id";

    public function index(): void
    {
        $this->guard(); $vista = in_array($_GET['vista'] ?? '', ['hechas', 'todas'], true) ? $_GET['vista'] : 'pend';
        $resp = $_GET['resp'] ?? (($this->esGestor()) ? '' : 'mine');
        $w = ['a.auto = 0']; $p = [];
        if ($vista === 'pend') $w[] = 'a.completada = 0'; elseif ($vista === 'hechas') $w[] = 'a.completada = 1';
        if (isset(ACT_TIPOS[$_GET['tipo'] ?? ''])) { $w[] = 'a.tipo = :t'; $p[':t'] = $_GET['tipo']; }
        if ($resp === 'mine') { $w[] = 'a.responsable_id = :r'; $p[':r'] = (int)Auth::user()['id']; } elseif (ctype_digit((string)$resp) && $resp !== '') { $w[] = 'a.responsable_id = :r'; $p[':r'] = (int)$resp; }
        if (($q = trim($_GET['q'] ?? '')) !== '') { $w[] = '(a.asunto LIKE :q1 OR c.razon_social LIKE :q2 OR l.empresa LIKE :q3)'; $p[':q1'] = $p[':q2'] = $p[':q3'] = "%$q%"; }
        $dia = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dia'] ?? '') ? $_GET['dia'] : null;
        if ($dia) { $w[] = 'DATE(a.fecha) = :d'; $p[':d'] = $dia; }
        $W = ' WHERE ' . implode(' AND ', $w);
        $rows = $this->all("SELECT " . self::SEL . " FROM " . self::FROM . "$W ORDER BY " . ($vista === 'hechas' ? 'a.completada_at DESC' : 'a.fecha IS NULL, a.fecha ASC') . " LIMIT 200", $p);
        $grupos = [];
        if ($vista === 'pend') {
            $hoy = date('Y-m-d'); $en7 = date('Y-m-d', strtotime('+7 days'));
            $defs = ['vencidas' => ['Vencidas', '#ef4444', 'bi-exclamation-triangle-fill'], 'hoy' => ['Para hoy', '#4f46e5', 'bi-calendar-event-fill'], 'semana' => ['Próximos 7 días', '#0ea5e9', 'bi-calendar-week-fill'], 'despues' => ['Más adelante', '#64748b', 'bi-calendar3'], 'sinfecha' => ['Sin fecha', '#94a3b8', 'bi-infinity']];
            foreach ($defs as $k => $d) $grupos[$k] = ['t' => $d, 'rows' => []];
            foreach ($rows as $r) {
                $d = $r['fecha'] ? substr($r['fecha'], 0, 10) : null;
                $k = !$d ? 'sinfecha' : ($d < $hoy ? 'vencidas' : ($d === $hoy ? 'hoy' : ($d <= $en7 ? 'semana' : 'despues')));
                $grupos[$k]['rows'][] = $r;
            }
        }
        // franja de 7 días
        $semana = [];
        for ($i = 0; $i < 7; $i++) {
            $f = date('Y-m-d', strtotime("+$i days")); $pp = [':f' => $f];
            $sql = 'SELECT COUNT(*) FROM crm_actividades WHERE auto=0 AND completada=0 AND DATE(fecha)=:f'; if ($resp === 'mine') { $sql .= ' AND responsable_id=:r'; $pp[':r'] = (int)Auth::user()['id']; } elseif (ctype_digit((string)$resp) && $resp !== '') { $sql .= ' AND responsable_id=:r'; $pp[':r'] = (int)$resp; }
            $semana[] = ['f' => $f, 'n' => (int)$this->val($sql, $pp)];
        }
        $uid = (int)Auth::user()['id'];
        $kp = $this->one("SELECT SUM(completada=0 AND fecha < CURDATE()) vencidas, SUM(completada=0 AND DATE(fecha)=CURDATE()) hoy, SUM(completada=0) pendientes, SUM(completada=1 AND completada_at >= CURDATE() - INTERVAL 7 DAY) hechas7 FROM crm_actividades WHERE auto=0 AND (responsable_id=? OR ?='')", [$uid, $resp === 'mine' ? (string)$uid : '']);
        $this->render('actividades/index', compact('grupos', 'rows', 'vista', 'resp', 'dia', 'semana', 'kp') + ['vendedores' => $this->vendedores()], 'Actividades y seguimientos');
    }

    public function guardar(): void
    {
        $this->guard(); $this->requirePost('actividades');
        $tipo = $_POST['tipo'] ?? 'tarea'; $asunto = $this->str('asunto', 180);
        $f = trim((string)($_POST['fecha'] ?? '')); $h = trim((string)($_POST['hora'] ?? ''));
        $fecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) ? $f . ' ' . (preg_match('/^\d{2}:\d{2}$/', $h) ? $h : '09:00') . ':00' : null;
        $cli = (int)($_POST['cliente_id'] ?? 0) ?: null; $lead = (int)($_POST['lead_id'] ?? 0) ?: null; $op = (int)($_POST['oportunidad_id'] ?? 0) ?: null;
        if (!isset(ACT_TIPOS[$tipo]) || $tipo === 'nota' && false) $tipo = 'tarea';
        if (!$asunto) { $this->flash('danger', 'Escribe el asunto de la actividad.'); $this->back('actividades'); }
        if ($op) { $r = $this->one('SELECT cliente_id FROM crm_oportunidades WHERE id=?', [$op]); if (!$r) $op = null; else $cli = (int)$r['cliente_id']; }
        if ($lead && !$this->val('SELECT COUNT(*) FROM crm_leads WHERE id=?', [$lead])) $lead = null;
        if ($cli && !$this->val('SELECT COUNT(*) FROM clientes WHERE id=?', [$cli])) $cli = null;
        $resp = (int)($_POST['responsable_id'] ?? 0) ?: (int)Auth::user()['id'];
        $hecha = !empty($_POST['ya_realizada']);
        $this->run('INSERT INTO crm_actividades(tipo,asunto,detalle,fecha,completada,completada_at,resultado,cliente_id,lead_id,oportunidad_id,responsable_id,creado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',
            [$tipo, $asunto, $this->str('detalle', 2000), $fecha ?: ($hecha ? date('Y-m-d H:i:s') : null), (int)$hecha, $hecha ? date('Y-m-d H:i:s') : null, $hecha ? $this->str('detalle', 2000) : null, $cli, $lead, $op, $resp, Auth::user()['id']]);
        if ($hecha && $lead) $this->tocarLead($lead, $tipo);
        $this->flash('success', $hecha ? 'Actividad registrada en el historial.' : 'Actividad agendada.'); $this->back('actividades');
    }

    private function tocarLead(int $lead, string $tipo): void
    {
        $l = $this->one('SELECT * FROM crm_leads WHERE id=?', [$lead]); if (!$l) return;
        $n = (int)$this->val('SELECT COUNT(*) FROM crm_actividades WHERE lead_id=? AND auto=0 AND completada=1', [$lead]);
        $est = ($l['estado'] === 'nuevo' && in_array($tipo, ['llamada', 'correo', 'reunion', 'visita'], true)) ? 'contactado' : $l['estado'];
        $this->run('UPDATE crm_leads SET estado=?, score=? WHERE id=?', [$est, lead_score(array_merge($l, ['estado' => $est]), $n), $lead]);
    }

    public function completar($id = 0): void
    {
        $this->guard(); $this->requirePost('actividades'); $id = (int)$id;
        $a = $this->one('SELECT * FROM crm_actividades WHERE id=? AND completada=0', [$id]);
        if (!$a) { $this->flash('danger', 'La actividad no existe o ya fue completada.'); $this->back('actividades'); }
        $this->run('UPDATE crm_actividades SET completada=1, completada_at=NOW(), resultado=? WHERE id=?', [$this->str('resultado', 2000), $id]);
        if ($a['lead_id']) $this->tocarLead((int)$a['lead_id'], $a['tipo']);
        $msg = 'Actividad completada.';
        if (!empty($_POST['seguimiento']) && $this->str('sig_asunto', 180)) {
            $f = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['sig_fecha'] ?? '') ? $_POST['sig_fecha'] : date('Y-m-d', strtotime('+3 days'));
            $t = isset(ACT_TIPOS[$_POST['sig_tipo'] ?? '']) ? $_POST['sig_tipo'] : 'llamada';
            $this->run('INSERT INTO crm_actividades(tipo,asunto,fecha,cliente_id,lead_id,oportunidad_id,responsable_id,creado_por) VALUES(?,?,?,?,?,?,?,?)', [$t, $this->str('sig_asunto', 180), "$f 09:00:00", $a['cliente_id'], $a['lead_id'], $a['oportunidad_id'], $a['responsable_id'] ?: Auth::user()['id'], Auth::user()['id']]);
            $msg .= ' Seguimiento agendado para el ' . fdate($f) . '.';
        }
        $this->flash('success', $msg); $this->back('actividades');
    }

    public function reprogramar($id = 0): void
    {
        $this->guard(); $this->requirePost('actividades'); $id = (int)$id;
        $a = $this->one('SELECT fecha FROM crm_actividades WHERE id=? AND completada=0', [$id]); if (!$a) $this->back('actividades');
        $dias = (int)($_POST['dias'] ?? 0);
        if ($dias > 0 && $dias <= 60) $nueva = date('Y-m-d H:i:s', strtotime('+' . $dias . ' days', max(time(), strtotime($a['fecha'] ?: 'now'))));
        elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['fecha'] ?? '')) $nueva = $_POST['fecha'] . ' ' . ($a['fecha'] ? substr($a['fecha'], 11) : '09:00:00');
        else { $this->flash('danger', 'Fecha no válida.'); $this->back('actividades'); }
        $this->run('UPDATE crm_actividades SET fecha=? WHERE id=?', [$nueva, $id]); $this->flash('success', 'Actividad reprogramada para el ' . fdate($nueva, 'd/m/Y H:i') . '.'); $this->back('actividades');
    }

    public function eliminar($id = 0): void
    {
        $this->guard(); $this->requirePost('actividades'); $this->run('DELETE FROM crm_actividades WHERE id=? AND auto=0', [(int)$id]);
        $this->flash('success', 'Actividad eliminada.'); $this->back('actividades');
    }
}

<?php
require_once ROOT_PATH . '/core/CrmBase.php';

/** Panel comercial: indicadores, embudo, forecast, renovaciones y clientes en riesgo */
class CrmController extends CrmBase
{
    public function index(): void
    {
        $this->guard();
        $k = $this->one("SELECT
            COALESCE(SUM(CASE WHEN estado='abierta' THEN valor END),0) pipeline,
            COALESCE(SUM(CASE WHEN estado='abierta' THEN valor*probabilidad/100 END),0) forecast,
            COALESCE(SUM(estado='abierta'),0) n_abiertas,
            COALESCE(SUM(CASE WHEN estado='ganada' AND fecha_cierre_real >= DATE_FORMAT(NOW(),'%Y-%m-01') THEN valor END),0) ganado_mes,
            COALESCE(SUM(estado='ganada' AND fecha_cierre_real >= DATE_FORMAT(NOW(),'%Y-%m-01')),0) n_ganadas_mes,
            COALESCE(SUM(estado='ganada' AND fecha_cierre_real >= CURDATE() - INTERVAL 90 DAY),0) g90,
            COALESCE(SUM(estado='perdida' AND fecha_cierre_real >= CURDATE() - INTERVAL 90 DAY),0) p90,
            COALESCE(SUM(estado='abierta' AND fecha_cierre_estimada < CURDATE()),0) atrasadas
            FROM crm_oportunidades");
        $k['conversion'] = ($k['g90'] + $k['p90']) ? round($k['g90'] * 100 / ($k['g90'] + $k['p90'])) : 0;
        $k['ticket_prom'] = $k['g90'] ? round((float)$this->val("SELECT AVG(valor) FROM crm_oportunidades WHERE estado='ganada' AND fecha_cierre_real >= CURDATE() - INTERVAL 90 DAY")) : 0;
        $k['leads_mes'] = (int)$this->val("SELECT COUNT(*) FROM crm_leads WHERE created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");
        $k['leads_sin_atender'] = (int)$this->val("SELECT COUNT(*) FROM crm_leads l WHERE l.estado='nuevo' AND l.created_at < NOW() - INTERVAL 2 DAY");
        $uid = (int)Auth::user()['id'];
        $k['act_vencidas'] = (int)$this->val('SELECT COUNT(*) FROM crm_actividades WHERE auto=0 AND completada=0 AND fecha < NOW() - INTERVAL 1 HOUR AND responsable_id=?', [$uid]);
        $k['act_hoy'] = (int)$this->val('SELECT COUNT(*) FROM crm_actividades WHERE auto=0 AND completada=0 AND DATE(fecha)=CURDATE() AND responsable_id=?', [$uid]);

        $embudo = $this->all("SELECT e.id, e.nombre, e.color, e.probabilidad, COUNT(o.id) n, COALESCE(SUM(o.valor),0) valor FROM crm_etapas e LEFT JOIN crm_oportunidades o ON o.etapa_id=e.id AND o.estado='abierta' WHERE e.tipo='abierta' GROUP BY e.id ORDER BY e.orden");

        // forecast por mes de cierre estimado (atrasadas se acumulan en el mes actual)
        $meses = []; for ($i = 0; $i < 6; $i++) $meses[date('Y-m', strtotime("first day of +$i month"))] = ['mes' => $i, 'valor' => 0.0, 'pond' => 0.0];
        foreach ($this->all("SELECT fecha_cierre_estimada f, valor, probabilidad FROM crm_oportunidades WHERE estado='abierta'") as $o) {
            $m = substr($o['f'], 0, 7); if ($m < date('Y-m')) $m = date('Y-m');
            if (isset($meses[$m])) { $meses[$m]['valor'] += $o['valor']; $meses[$m]['pond'] += $o['valor'] * $o['probabilidad'] / 100; }
        }
        $mn = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $forecast = []; foreach ($meses as $m => $v) $forecast[] = ['label' => $mn[(int)substr($m, 5)] . ' ' . substr($m, 2, 2), 'valor' => round($v['valor']), 'pond' => round($v['pond'])];

        $origen = $this->all("SELECT COALESCE(origen,'otro') origen, COUNT(*) total, SUM(estado='ganada') ganadas, SUM(estado='perdida') perdidas, COALESCE(SUM(CASE WHEN estado='ganada' THEN valor END),0) ganado FROM crm_oportunidades GROUP BY origen ORDER BY ganado DESC, total DESC");
        $ranking = $this->all("SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) nombre, u.avatar_color color,
            COALESCE(SUM(CASE WHEN o.estado='ganada' AND o.fecha_cierre_real >= CURDATE() - INTERVAL 90 DAY THEN o.valor END),0) ganado,
            COALESCE(SUM(o.estado='ganada' AND o.fecha_cierre_real >= CURDATE() - INTERVAL 90 DAY),0) n_g, COALESCE(SUM(o.estado='perdida' AND o.fecha_cierre_real >= CURDATE() - INTERVAL 90 DAY),0) n_p,
            COALESCE(SUM(CASE WHEN o.estado='abierta' THEN o.valor END),0) pipeline, COALESCE(SUM(o.estado='abierta'),0) n_a
            FROM usuarios u JOIN roles r ON r.id=u.rol_id LEFT JOIN crm_oportunidades o ON o.responsable_id=u.id
            WHERE r.nombre IN ('Administrador','Supervisor','Comercial') AND u.estado=1 GROUP BY u.id HAVING n_a > 0 OR n_g > 0 OR n_p > 0 ORDER BY ganado DESC, pipeline DESC");
        $motivos = $this->all("SELECT motivo_perdida motivo, COUNT(*) n, SUM(valor) valor FROM crm_oportunidades WHERE estado='perdida' AND motivo_perdida IS NOT NULL GROUP BY motivo_perdida ORDER BY n DESC LIMIT 5");

        $acts = $this->all("SELECT a.*, c.razon_social cliente, l.empresa lead_nombre, o.titulo op, CONCAT(u.nombres,' ',u.apellidos) responsable FROM crm_actividades a LEFT JOIN clientes c ON c.id=a.cliente_id LEFT JOIN crm_leads l ON l.id=a.lead_id LEFT JOIN crm_oportunidades o ON o.id=a.oportunidad_id LEFT JOIN usuarios u ON u.id=a.responsable_id
            WHERE a.auto=0 AND a.completada=0 AND a.fecha IS NOT NULL AND a.fecha < NOW() + INTERVAL 7 DAY " . ($this->esGestor() ? '' : 'AND a.responsable_id=' . $uid) . " ORDER BY a.fecha LIMIT 7");
        $cierres = $this->all("SELECT o.id, o.codigo, o.titulo, o.valor, o.probabilidad, o.fecha_cierre_estimada, c.razon_social cliente, e.nombre etapa, e.color FROM crm_oportunidades o JOIN clientes c ON c.id=o.cliente_id JOIN crm_etapas e ON e.id=o.etapa_id WHERE o.estado='abierta' AND o.fecha_cierre_estimada <= CURDATE() + INTERVAL 21 DAY ORDER BY o.fecha_cierre_estimada LIMIT 6");
        $renov = $this->all("SELECT k.id, k.codigo, k.plan, k.monto_mensual, k.fecha_fin, DATEDIFF(k.fecha_fin, CURDATE()) dias, c.id cid, c.razon_social cliente,
            (SELECT COUNT(*) FROM crm_oportunidades o WHERE o.cliente_id=k.cliente_id AND o.tipo='renovacion' AND o.estado='abierta') en_curso
            FROM contratos k JOIN clientes c ON c.id=k.cliente_id WHERE k.estado IN ('por_vencer','vencido') AND k.fecha_fin >= CURDATE() - INTERVAL 60 DAY ORDER BY k.fecha_fin LIMIT 6");
        $riesgo = [];
        foreach ($this->all("SELECT c.id, c.razon_social,
            (SELECT COUNT(*) FROM contratos k WHERE k.cliente_id=c.id AND k.estado IN ('por_vencer','vencido')) k_riesgo,
            (SELECT COUNT(*) FROM tickets t WHERE t.cliente_id=c.id AND t.created_at >= NOW() - INTERVAL 90 DAY AND t.fecha_limite IS NOT NULL AND ((t.estado IN ('resuelto','cerrado') AND t.resuelto_at > t.fecha_limite) OR (t.estado NOT IN ('resuelto','cerrado') AND NOW() > t.fecha_limite))) fuera_sla,
            (SELECT COUNT(*) FROM tickets t WHERE t.cliente_id=c.id AND t.estado IN ('abierto','en_proceso','pendiente')) abiertos,
            (SELECT MAX(a.fecha) FROM crm_actividades a WHERE a.cliente_id=c.id AND a.completada=1 AND a.auto=0) ultimo
            FROM clientes c WHERE c.estado=1") as $c) {
            $s = 0; $why = [];
            if ($c['k_riesgo']) { $s += 40; $why[] = 'Contrato por vencer/vencido'; }
            if ($c['fuera_sla'] > 0) { $s += min(30, 10 * $c['fuera_sla']); $why[] = $c['fuera_sla'] . ' ticket(s) fuera de SLA (90 d)'; }
            if ($c['abiertos'] >= 3) { $s += 15; $why[] = $c['abiertos'] . ' tickets abiertos'; }
            if (!$c['ultimo'] || strtotime($c['ultimo']) < strtotime('-60 days')) { $s += 15; $why[] = 'Sin contacto comercial > 60 d'; }
            if ($s >= 40) $riesgo[] = ['id' => $c['id'], 'cliente' => $c['razon_social'], 'score' => min(100, $s), 'why' => $why];
        }
        usort($riesgo, fn($a, $b) => $b['score'] <=> $a['score']); $riesgo = array_slice($riesgo, 0, 6);

        $this->render('crm/index', compact('k', 'embudo', 'forecast', 'origen', 'ranking', 'motivos', 'acts', 'cierres', 'renov', 'riesgo'), 'CRM · Panel comercial');
    }

    /** Crea una oportunidad de renovación a partir de un contrato por vencer */
    public function renovar($id = 0): void
    {
        $this->guard(); $this->requirePost('crm'); $k = $this->one('SELECT k.*, c.razon_social FROM contratos k JOIN clientes c ON c.id=k.cliente_id WHERE k.id=?', [(int)$id]);
        if (!$k) { $this->flash('danger', 'Contrato no encontrado.'); $this->redirect('crm'); }
        $ya = $this->one("SELECT id FROM crm_oportunidades WHERE cliente_id=? AND tipo='renovacion' AND estado='abierta' LIMIT 1", [$k['cliente_id']]);
        if ($ya) { $this->flash('info', 'Ya existe una renovación en curso para este cliente.'); $this->redirect('oportunidades/ver/' . $ya['id']); }
        $et = $this->one("SELECT id, probabilidad FROM crm_etapas WHERE nombre='Propuesta' LIMIT 1") ?: $this->one("SELECT id, probabilidad FROM crm_etapas WHERE tipo='abierta' ORDER BY orden LIMIT 1");
        $ct = $this->val('SELECT id FROM crm_contactos WHERE cliente_id=? ORDER BY principal DESC LIMIT 1', [$k['cliente_id']]) ?: null;
        $cierre = max(date('Y-m-d'), date('Y-m-d', strtotime($k['fecha_fin'] . ' -10 days')));
        $cod = $this->codigo('crm_oportunidades', 'OP');
        $this->run('INSERT INTO crm_oportunidades(codigo,titulo,cliente_id,contacto_id,etapa_id,tipo,valor,probabilidad,fecha_cierre_estimada,responsable_id,origen,descripcion) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',
            [$cod, 'Renovación contrato ' . $k['plan'] . ' · ' . $k['codigo'], $k['cliente_id'], $ct, $et['id'], 'renovacion', $k['monto_mensual'] * 12, $et['probabilidad'], $cierre, Auth::user()['id'], 'referido', 'Renovación del contrato ' . $k['codigo'] . ' (vence el ' . fdate($k['fecha_fin']) . ').']);
        $oid = (int)$this->db()->lastInsertId();
        $this->evento('Oportunidad de renovación creada desde el contrato ' . $k['codigo'], (int)$k['cliente_id'], null, $oid);
        $this->run("INSERT INTO crm_actividades(tipo,asunto,fecha,cliente_id,oportunidad_id,responsable_id,creado_por) VALUES('llamada',?,?,?,?,?,?)", ['Contactar para renovación de ' . $k['codigo'], date('Y-m-d 10:00:00', strtotime('+1 day')), $k['cliente_id'], $oid, Auth::user()['id'], Auth::user()['id']]);
        $this->flash('success', "Renovación $cod creada y llamada de seguimiento agendada."); $this->redirect("oportunidades/ver/$oid");
    }
}

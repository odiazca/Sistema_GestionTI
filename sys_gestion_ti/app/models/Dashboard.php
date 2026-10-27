<?php
class Dashboard
{
    private PDO $db;
    public function __construct() { $this->db = Database::conn(); }

    private function val(string $sql, array $p = []) { $s = $this->db->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
    private function all(string $sql, array $p = []): array { $s = $this->db->prepare($sql); $s->execute($p); return $s->fetchAll(); }

    public function badges(): array
    {
        return [
            'tickets_abiertos' => (int)$this->val("SELECT COUNT(*) FROM tickets WHERE estado IN ('abierto','en_proceso','pendiente')"),
            'stock_bajo'       => (int)$this->val('SELECT COUNT(*) FROM repuestos WHERE stock <= stock_minimo'),
            'act_pend'         => $this->actPend(),
            'fac_vencidas'     => $this->facVencidas(),
        ];
    }

    private function facVencidas(): int
    {
        try { return (int)$this->val("SELECT COUNT(*) FROM fac_comprobantes WHERE estado='emitida' AND fecha_vencimiento < CURDATE()"); } catch (Throwable $e) { return 0; }
    }

    /** Actividades CRM vencidas o de hoy del usuario (0 si el CRM no está instalado) */
    private function actPend(): int
    {
        try { return (int)$this->val('SELECT COUNT(*) FROM crm_actividades WHERE auto=0 AND completada=0 AND fecha < CURDATE() + INTERVAL 1 DAY AND responsable_id=?', [(int)(Auth::user()['id'] ?? 0)]); }
        catch (Throwable $e) { return 0; }
    }

    /** Notificaciones reales para la campana: [icono, color, titulo, detalle, enlace] */
    public function notificaciones(string $rol): array
    {
        $n = [];
        $add = function (string $ic, string $c, string $t, string $d, string $l) use (&$n) { $n[] = ['ic' => $ic, 'c' => $c, 't' => $t, 'd' => $d, 'l' => $l]; };
        $soporte = in_array($rol, ['Administrador', 'Supervisor', 'Técnico', 'Recepción'], true);
        try {
            if ($soporte) {
                foreach ($this->all("SELECT id, codigo, asunto FROM tickets WHERE prioridad='critica' AND tecnico_id IS NULL AND estado IN ('abierto','en_proceso','pendiente') ORDER BY created_at DESC LIMIT 2") as $t) $add('bi-exclamation-triangle-fill', 'danger', 'Ticket crítico sin asignar', $t['codigo'] . ' · ' . mb_strimwidth($t['asunto'], 0, 38, '…'), 'tickets/ver/' . $t['id']);
                foreach ($this->all("SELECT id, codigo, fecha_limite FROM tickets WHERE estado IN ('abierto','en_proceso','pendiente') AND fecha_limite BETWEEN NOW() AND NOW() + INTERVAL 2 HOUR ORDER BY fecha_limite LIMIT 2") as $t) $add('bi-clock-history', 'warning', 'SLA próximo a vencer', $t['codigo'] . ' vence ' . date('H:i', strtotime($t['fecha_limite'])), 'tickets/ver/' . $t['id']);
                $v = (int)$this->val("SELECT COUNT(*) FROM tickets WHERE estado IN ('abierto','en_proceso','pendiente') AND fecha_limite < NOW()");
                if ($v) $add('bi-alarm-fill', 'danger', $v . ' ticket(s) fuera de SLA', 'Requieren atención inmediata', 'tickets?vencidos=1');
            }
            if (in_array($rol, ['Administrador', 'Supervisor', 'Comercial'], true)) {
                foreach ($this->all("SELECT codigo, plan, fecha_fin, cliente_id FROM contratos WHERE estado='por_vencer' ORDER BY fecha_fin LIMIT 2") as $k) $add('bi-file-earmark-text', 'primary', 'Contrato por vencer', $k['codigo'] . ' · ' . max(0, (int)ceil((strtotime($k['fecha_fin']) - time()) / 86400)) . ' días', 'clientes/ver/' . $k['cliente_id']);
                try {
                    $uid = (int)(Auth::user()['id'] ?? 0);
                    $a = (int)$this->val('SELECT COUNT(*) FROM crm_actividades WHERE auto=0 AND completada=0 AND fecha < NOW() - INTERVAL 1 HOUR AND responsable_id=?', [$uid]);
                    if ($a) $add('bi-calendar-x-fill', 'danger', $a . ' actividad(es) CRM vencida(s)', 'Reprograma o completa tus seguimientos', 'actividades');
                    $h = (int)$this->val('SELECT COUNT(*) FROM crm_actividades WHERE auto=0 AND completada=0 AND DATE(fecha)=CURDATE() AND fecha >= NOW() - INTERVAL 1 HOUR AND responsable_id=?', [$uid]);
                    if ($h) $add('bi-calendar-event-fill', 'primary', $h . ' actividad(es) para hoy', 'Revisa tu agenda comercial', 'actividades');
                    foreach ($this->all("SELECT id, titulo, fecha_cierre_estimada FROM crm_oportunidades WHERE estado='abierta' AND fecha_cierre_estimada <= CURDATE() + INTERVAL 7 DAY ORDER BY fecha_cierre_estimada LIMIT 2") as $o) $add('bi-bullseye', 'success', 'Oportunidad por cerrar', mb_strimwidth($o['titulo'], 0, 34, '…') . ' · ' . date('d/m', strtotime($o['fecha_cierre_estimada'])), 'oportunidades/ver/' . $o['id']);
                    $l = (int)$this->val("SELECT COUNT(*) FROM crm_leads WHERE estado='nuevo' AND created_at < NOW() - INTERVAL 2 DAY");
                    if ($l) $add('bi-person-plus-fill', 'warning', $l . ' lead(s) sin atender', 'Más de 2 días sin contacto', 'leads?estado=nuevo');
                } catch (Throwable $e) {}
            }
            if (in_array($rol, ['Administrador', 'Supervisor'], true)) {
                try {
                    $fv = $this->one("SELECT COUNT(*) n, COALESCE(SUM(total-pagado),0) s FROM fac_comprobantes WHERE estado='emitida' AND fecha_vencimiento < CURDATE()");
                    if ($fv && $fv['n']) $add('bi-cash-coin', 'danger', $fv['n'] . ' comprobante(s) vencido(s)', 'Saldo vencido ' . money($fv['s']), 'facturacion?estado=vencida');
                    $fp = (int)$this->val("SELECT COUNT(*) FROM fac_comprobantes WHERE estado='emitida' AND fecha_vencimiento BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY");
                    if ($fp) $add('bi-hourglass-split', 'warning', $fp . ' comprobante(s) vencen en 3 días', 'Recuerda a tus clientes', 'facturacion/cobranza');
                } catch (Throwable $e) {}
            }
            if ($soporte) { $sb = (int)$this->val('SELECT COUNT(*) FROM repuestos WHERE stock <= stock_minimo'); if ($sb) $add('bi-box-seam', 'warning', $sb . ' repuesto(s) con stock bajo', 'Revisa el almacén', 'repuestos'); }
        } catch (Throwable $e) {}
        return $n;
    }

    public function kpis(): array
    {
        $abiertos  = (int)$this->val("SELECT COUNT(*) FROM tickets WHERE estado IN ('abierto','en_proceso','pendiente')");
        $hoy       = (int)$this->val('SELECT COUNT(*) FROM tickets WHERE DATE(created_at)=CURDATE()');
        $resHoy    = (int)$this->val("SELECT COUNT(*) FROM tickets WHERE DATE(resuelto_at)=CURDATE()");
        $vencidos  = (int)$this->val("SELECT COUNT(*) FROM tickets WHERE estado IN ('abierto','en_proceso','pendiente') AND fecha_limite < NOW()");
        $mes       = (int)$this->val("SELECT COUNT(*) FROM tickets WHERE created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");
        $mesAnt    = (int)$this->val("SELECT COUNT(*) FROM tickets WHERE created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH,'%Y-%m-01') AND created_at < DATE_FORMAT(NOW(),'%Y-%m-01')");
        $resTot    = (int)$this->val("SELECT COUNT(*) FROM tickets WHERE resuelto_at IS NOT NULL");
        $resOk     = (int)$this->val("SELECT COUNT(*) FROM tickets WHERE resuelto_at IS NOT NULL AND resuelto_at <= fecha_limite");
        $sla       = $resTot ? round($resOk * 100 / $resTot, 1) : 100;
        $tmr       = (float)$this->val("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resuelto_at)) FROM tickets WHERE resuelto_at IS NOT NULL AND created_at >= NOW() - INTERVAL 30 DAY");
        // proyección del mes en curso vs mes anterior (por día transcurrido)
        $dia = max(1, (int)date('j'));
        $proj = $mesAnt ? round((($mes / $dia) * (int)date('t') - $mesAnt) * 100 / $mesAnt) : 0;
        try {
            $ordenes = (int)$this->val("SELECT COUNT(*) FROM ordenes_servicio WHERE estado IN ('pendiente','en_curso')");
        } catch (Throwable $e) { $ordenes = 0; }
        $visitas = (int)$this->val("SELECT COUNT(*) FROM visitas WHERE estado IN ('programada','en_curso') AND fecha_programada >= CURDATE() AND fecha_programada < CURDATE() + INTERVAL 7 DAY");
        return [
            'ordenes' => $ordenes, 'visitas_semana' => $visitas,
            'abiertos' => $abiertos, 'hoy' => $hoy, 'resueltos_hoy' => $resHoy, 'vencidos' => $vencidos,
            'mes' => $mes, 'tendencia' => $proj, 'sla' => $sla, 'tmr_horas' => round($tmr / 60, 1),
            'clientes' => (int)$this->val('SELECT COUNT(*) FROM clientes WHERE estado=1'),
            'activos'  => (int)$this->val('SELECT COUNT(*) FROM activos'),
            'contratos' => (int)$this->val("SELECT COUNT(*) FROM contratos WHERE estado <> 'vencido'"),
            'ingresos' => (float)$this->val("SELECT COALESCE(SUM(monto_mensual),0) FROM contratos WHERE estado <> 'vencido'"),
        ];
    }

    public function serieMensual(): array
    {
        $meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $labels = []; $keys = [];
        for ($i = 5; $i >= 0; $i--) {
            $t = strtotime("first day of -$i month");
            $keys[] = date('Y-m', $t); $labels[] = $meses[(int)date('n', $t)];
        }
        $cre = array_column($this->all("SELECT DATE_FORMAT(created_at,'%Y-%m') k, COUNT(*) c FROM tickets WHERE created_at >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH,'%Y-%m-01') GROUP BY k"), 'c', 'k');
        $res = array_column($this->all("SELECT DATE_FORMAT(resuelto_at,'%Y-%m') k, COUNT(*) c FROM tickets WHERE resuelto_at >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH,'%Y-%m-01') GROUP BY k"), 'c', 'k');
        return [
            'labels' => $labels,
            'creados' => array_map(fn($k) => (int)($cre[$k] ?? 0), $keys),
            'resueltos' => array_map(fn($k) => (int)($res[$k] ?? 0), $keys),
        ];
    }

    public function porEstado(): array
    {
        $rows = array_column($this->all('SELECT estado, COUNT(*) c FROM tickets GROUP BY estado'), 'c', 'estado');
        $orden = ['abierto' => 'Abierto', 'en_proceso' => 'En proceso', 'pendiente' => 'Pendiente', 'resuelto' => 'Resuelto', 'cerrado' => 'Cerrado'];
        $out = ['labels' => [], 'data' => []];
        foreach ($orden as $k => $l) { $out['labels'][] = $l; $out['data'][] = (int)($rows[$k] ?? 0); }
        return $out;
    }

    public function porCategoria(): array
    {
        $r = $this->all('SELECT c.nombre, c.color, COUNT(t.id) total FROM categorias c LEFT JOIN tickets t ON t.categoria_id=c.id GROUP BY c.id ORDER BY total DESC');
        return ['labels' => array_column($r, 'nombre'), 'data' => array_map('intval', array_column($r, 'total')), 'colors' => array_column($r, 'color')];
    }

    public function porPrioridad(): array
    {
        $rows = array_column($this->all("SELECT prioridad, COUNT(*) c FROM tickets WHERE estado IN ('abierto','en_proceso','pendiente') GROUP BY prioridad"), 'c', 'prioridad');
        $out = [];
        foreach (['critica' => 'Crítica', 'alta' => 'Alta', 'media' => 'Media', 'baja' => 'Baja'] as $k => $l) $out[] = ['key' => $k, 'label' => $l, 'total' => (int)($rows[$k] ?? 0)];
        return $out;
    }

    public function cargaTecnicos(): array
    {
        return $this->all("SELECT u.id, CONCAT(u.nombres,' ',u.apellidos) nombre, u.cargo, u.avatar_color color,
            SUM(t.estado IN ('abierto','en_proceso','pendiente')) activos,
            SUM(DATE(t.resuelto_at) = CURDATE()) hoy,
            SUM(t.resuelto_at >= NOW() - INTERVAL 30 DAY) mes
            FROM usuarios u JOIN tickets t ON t.tecnico_id = u.id
            WHERE u.estado = 1 GROUP BY u.id ORDER BY activos DESC");
    }

    public function recientes(int $n = 8): array
    {
        return $this->all("SELECT t.codigo, t.asunto, t.prioridad, t.estado, t.created_at, t.fecha_limite, c.razon_social cliente,
            CONCAT(u.nombres,' ',u.apellidos) tecnico, u.avatar_color color, cat.nombre categoria
            FROM tickets t JOIN clientes c ON c.id=t.cliente_id JOIN categorias cat ON cat.id=t.categoria_id
            LEFT JOIN usuarios u ON u.id=t.tecnico_id
            WHERE t.estado IN ('abierto','en_proceso','pendiente')
            ORDER BY FIELD(t.prioridad,'critica','alta','media','baja'), t.created_at DESC LIMIT " . (int)$n);
    }

    public function actividad(int $n = 7): array
    {
        return $this->all("SELECT a.*, CONCAT(u.nombres,' ',u.apellidos) usuario FROM actividad a LEFT JOIN usuarios u ON u.id=a.usuario_id ORDER BY a.created_at DESC LIMIT " . (int)$n);
    }

    public function alertas(): array
    {
        return [
            'contratos' => $this->all("SELECT c.codigo, cl.razon_social, c.fecha_fin, DATEDIFF(c.fecha_fin, CURDATE()) dias FROM contratos c JOIN clientes cl ON cl.id=c.cliente_id WHERE c.fecha_fin <= CURDATE() + INTERVAL 60 DAY AND c.estado<>'vencido' ORDER BY c.fecha_fin LIMIT 4"),
            'stock'     => $this->all('SELECT nombre, stock, stock_minimo FROM repuestos WHERE stock <= stock_minimo ORDER BY stock ASC LIMIT 4'),
            'visitas'   => $this->all("SELECT v.tipo, v.fecha_programada, cl.razon_social, CONCAT(u.nombres,' ',u.apellidos) tecnico FROM visitas v JOIN clientes cl ON cl.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.tecnico_id WHERE v.estado='programada' AND v.fecha_programada >= NOW() ORDER BY v.fecha_programada LIMIT 4"),
        ];
    }

    public function canales(): array
    {
        $r = $this->all("SELECT canal, COUNT(*) c FROM tickets WHERE created_at >= NOW() - INTERVAL 30 DAY GROUP BY canal ORDER BY c DESC");
        return ['labels' => array_map('ucfirst', array_column($r, 'canal')), 'data' => array_map('intval', array_column($r, 'c'))];
    }

    /** Actividad por día de la semana actual para el sparkline */
    public function sparkline(): array
    {
        $r = array_column($this->all("SELECT DATE(created_at) d, COUNT(*) c FROM tickets WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY d"), 'c', 'd');
        $out = [];
        for ($i = 6; $i >= 0; $i--) $out[] = (int)($r[date('Y-m-d', strtotime("-$i day"))] ?? 0);
        return $out;
    }
}

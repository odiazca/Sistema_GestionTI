<?php
/** Calendario general: junta visitas, órdenes, actividades CRM, vencimientos de contratos, cotizaciones y cobros */
class CalendarioController extends Controller
{
    private function rol(): string { return Auth::user()['rol'] ?? ''; }

    public function index(): void
    {
        $this->requireRole(['Administrador', 'Supervisor', 'Técnico', 'Recepción', 'Comercial']);
        $this->render('calendario/index', ['capas' => $this->capas()], 'Calendario');
    }

    /** Capas visibles según el rol: clave => [etiqueta, color, icono] */
    private function capas(): array
    {
        $r = $this->rol(); $todas = [
            'visita'   => ['Visitas técnicas', '#4f46e5', 'bi-geo-alt-fill', ['Administrador', 'Supervisor', 'Técnico', 'Recepción']],
            'orden'    => ['Órdenes de servicio', '#0ea5e9', 'bi-clipboard2-check-fill', ['Administrador', 'Supervisor', 'Técnico', 'Recepción']],
            'crm'      => ['Actividades CRM', '#8b5cf6', 'bi-telephone-fill', ['Administrador', 'Supervisor', 'Comercial']],
            'contrato' => ['Vencen contratos', '#f59e0b', 'bi-file-earmark-text-fill', ['Administrador', 'Supervisor', 'Recepción', 'Comercial']],
            'cotiz'    => ['Vencen cotizaciones', '#ec4899', 'bi-receipt-cutoff', ['Administrador', 'Supervisor', 'Comercial']],
            'cobro'    => ['Vencen cobros', '#ef4444', 'bi-cash-coin', ['Administrador', 'Supervisor']],
        ]; $o = [];
        foreach ($todas as $k => $c) if (in_array($r, $c[3], true)) $o[$k] = array_slice($c, 0, 3);
        return $o;
    }

    public function eventos(): void
    {
        $this->requireRole(['Administrador', 'Supervisor', 'Técnico', 'Recepción', 'Comercial']);
        $ok = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v);
        $d = $ok($_GET['desde'] ?? null) ? $_GET['desde'] : date('Y-m-01'); $h = $ok($_GET['hasta'] ?? null) ? $_GET['hasta'] : date('Y-m-t');
        $db = Database::conn(); $uid = (int)Auth::user()['id']; $tec = $this->rol() === 'Técnico'; $com = $this->rol() === 'Comercial'; $capas = $this->capas(); $ev = [];
        $q = function (string $sql, array $p) use ($db) { $s = $db->prepare($sql); $s->execute($p); return $s->fetchAll(); };
        $add = function (string $tipo, $id, string $fecha, string $titulo, string $sub, string $url, bool $hecho = false) use (&$ev, $capas) {
            $ev[] = ['tipo' => $tipo, 'id' => $id, 'fecha' => substr($fecha, 0, 10), 'hora' => strlen($fecha) > 10 && substr($fecha, 11, 5) !== '00:00' ? substr($fecha, 11, 5) : '', 'titulo' => $titulo, 'sub' => $sub, 'url' => url($url), 'color' => $capas[$tipo][1], 'hecho' => $hecho];
        };
        if (isset($capas['visita'])) foreach ($q("SELECT v.id, v.fecha_programada f, v.tipo, v.estado, c.razon_social cli, CONCAT(u.nombres,' ',u.apellidos) tec FROM visitas v JOIN clientes c ON c.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.tecnico_id WHERE DATE(v.fecha_programada) BETWEEN ? AND ? AND v.estado<>'cancelada'" . ($tec ? ' AND v.tecnico_id=' . $uid : ''), [$d, $h]) as $r)
            $add('visita', $r['id'], $r['f'], 'Visita ' . $r['tipo'] . ' · ' . $r['cli'], $r['tec'] ?: 'Sin técnico', 'visitas', $r['estado'] === 'completada');
        if (isset($capas['orden'])) foreach ($q("SELECT o.id, o.codigo, o.fecha_programada f, o.estado, c.razon_social cli FROM ordenes_servicio o JOIN clientes c ON c.id=o.cliente_id WHERE o.fecha_programada IS NOT NULL AND DATE(o.fecha_programada) BETWEEN ? AND ? AND o.estado<>'cancelada'" . ($tec ? ' AND o.tecnico_id=' . $uid : ''), [$d, $h]) as $r)
            $add('orden', $r['id'], $r['f'], $r['codigo'] . ' · ' . $r['cli'], 'Orden de servicio', 'ordenes/ver/' . $r['id'], $r['estado'] === 'completada');
        if (isset($capas['crm'])) foreach ($q("SELECT a.id, a.fecha f, a.tipo, a.asunto, a.completada, c.razon_social cli FROM crm_actividades a LEFT JOIN clientes c ON c.id=a.cliente_id WHERE DATE(a.fecha) BETWEEN ? AND ?" . ($com ? ' AND a.responsable_id=' . $uid : ''), [$d, $h]) as $r)
            $add('crm', $r['id'], $r['f'], ucfirst($r['tipo']) . ': ' . $r['asunto'], $r['cli'] ?: 'CRM', 'actividades', (bool)$r['completada']);
        if (isset($capas['contrato'])) foreach ($q("SELECT t.id, t.codigo, t.fecha_fin f, c.razon_social cli, t.cliente_id FROM contratos t JOIN clientes c ON c.id=t.cliente_id WHERE t.fecha_fin BETWEEN ? AND ?", [$d, $h]) as $r)
            $add('contrato', $r['id'], $r['f'], 'Vence ' . $r['codigo'] . ' · ' . $r['cli'], 'Renovar contrato', 'clientes/ver/' . $r['cliente_id']);
        if (isset($capas['cotiz'])) foreach ($q("SELECT q.id, q.codigo, q.valida_hasta f, c.razon_social cli FROM crm_cotizaciones q JOIN clientes c ON c.id=q.cliente_id WHERE q.estado IN ('enviada','borrador') AND q.valida_hasta BETWEEN ? AND ?", [$d, $h]) as $r)
            $add('cotiz', $r['id'], $r['f'], 'Vence ' . $r['codigo'] . ' · ' . $r['cli'], 'Cotización', 'cotizaciones/ver/' . $r['id']);
        if (isset($capas['cobro'])) foreach ($q("SELECT f.id, f.codigo, f.fecha_vencimiento f, f.total-f.pagado saldo, c.razon_social cli FROM fac_comprobantes f JOIN clientes c ON c.id=f.cliente_id WHERE f.estado='emitida' AND f.fecha_vencimiento BETWEEN ? AND ?", [$d, $h]) as $r)
            $add('cobro', $r['id'], $r['f'], 'Cobro ' . $r['codigo'] . ' · ' . $r['cli'], 'Saldo ' . money($r['saldo']), 'facturacion/ver/' . $r['id']);
        usort($ev, fn($a, $b) => [$a['fecha'], $a['hora']] <=> [$b['fecha'], $b['hora']]);
        $this->json($ev);
    }
}

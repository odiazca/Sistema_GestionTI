<?php
class ReportesController extends Controller
{
    private const DEFS = [
        'tickets'    => ['Tickets por período', 'bi-ticket-perforated-fill', '#4f46e5', 'Volumen de tickets creados y resueltos, con detalle y tiempos de resolución.', true],
        'sla'        => ['Cumplimiento de SLA', 'bi-shield-check', '#10b981', 'Porcentaje de tickets resueltos dentro del plazo, por cliente y prioridad.', true],
        'tecnicos'   => ['Productividad del equipo', 'bi-people-fill', '#0ea5e9', 'Tickets, órdenes de servicio y horas por técnico.', true],
        'clientes'   => ['Actividad por cliente', 'bi-buildings-fill', '#f59e0b', 'Tickets, órdenes y facturación de servicios por cliente.', true],
        'ingresos'   => ['Ingresos por servicios', 'bi-cash-coin', '#8b5cf6', 'Mensualidades de contratos y órdenes de servicio facturables por mes.', true],
        'inventario' => ['Inventario valorizado', 'bi-box-seam-fill', '#ef4444', 'Stock, valorización y alertas de reposición del almacén.', false],
    ];
    private const PAL = ['#4f46e5', '#10b981', '#f59e0b', '#0ea5e9', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'];

    public function index(): void
    {
        $this->requireRole(['Administrador', 'Supervisor']);
        $this->render('reportes/index', ['defs' => self::DEFS], 'Reportes');
    }

    private function rango(): array
    {
        $ok = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v);
        $h = $ok($_GET['hasta'] ?? null) ? $_GET['hasta'] : date('Y-m-d');
        $d = $ok($_GET['desde'] ?? null) ? $_GET['desde'] : date('Y-m-d', strtotime('-29 days'));
        if ($d > $h) [$d, $h] = [$h, $d];
        return [$d, $h];
    }

    private function build(string $id): ?array
    {
        if (!isset(self::DEFS[$id])) return null;
        [$d, $h] = $this->rango();
        $r = $this->{'r_' . $id}($d . ' 00:00:00', $h . ' 23:59:59', $d, $h);
        $r['id'] = $id; $r['def'] = self::DEFS[$id]; $r['desde'] = $d; $r['hasta'] = $h;
        return $r;
    }

    public function ver($id = ''): void
    {
        $this->requireRole(['Administrador', 'Supervisor']);
        $r = $this->build((string)$id);
        if (!$r) $this->redirect('reportes');
        $this->render('reportes/ver', ['r' => $r], $r['def'][0]);
    }

    public function exportar($id = ''): void
    {
        $this->requireRole(['Administrador', 'Supervisor']);
        $r = $this->build((string)$id);
        if (!$r) $this->redirect('reportes');
        $name = 'reporte_' . $id . ($r['def'][4] ? '_' . $r['desde'] . '_' . $r['hasta'] : '') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        $o = fopen('php://output', 'w');
        fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, [APP_NAME . ' · ' . $r['def'][0]], ';');
        if ($r['def'][4]) fputcsv($o, ['Período', $r['desde'], $r['hasta']], ';');
        fputcsv($o, [], ';');
        fputcsv($o, $r['cols'], ';');
        foreach ($r['rows'] as $row) fputcsv($o, $row, ';');
        fclose($o);
        exit;
    }

    // ------------------------------------------------------------------ helpers de consulta
    private function q(string $sql, array $p = []): array { $s = Database::conn()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    private function v(string $sql, array $p = []) { $s = Database::conn()->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
    private function dias(string $d, string $h): array
    {
        $o = []; for ($t = strtotime($d); $t <= strtotime($h); $t += 86400) $o[] = date('Y-m-d', $t); return $o;
    }

    // ------------------------------------------------------------------ reportes
    private function r_tickets(string $a, string $b, string $d, string $h): array
    {
        $p = [':a' => $a, ':b' => $b];
        $total = (int)$this->v('SELECT COUNT(*) FROM tickets WHERE created_at BETWEEN :a AND :b', $p);
        $res = (int)$this->v('SELECT COUNT(*) FROM tickets WHERE resuelto_at BETWEEN :a AND :b', $p);
        $ok = (int)$this->v('SELECT COUNT(*) FROM tickets WHERE resuelto_at BETWEEN :a AND :b AND resuelto_at <= fecha_limite', $p);
        $tmr = (float)$this->v('SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resuelto_at)) FROM tickets WHERE resuelto_at BETWEEN :a AND :b', $p);
        $cre = array_column($this->q('SELECT DATE(created_at) k, COUNT(*) c FROM tickets WHERE created_at BETWEEN :a AND :b GROUP BY k', $p), 'c', 'k');
        $rsl = array_column($this->q('SELECT DATE(resuelto_at) k, COUNT(*) c FROM tickets WHERE resuelto_at BETWEEN :a AND :b GROUP BY k', $p), 'c', 'k');
        $days = $this->dias($d, $h);
        $rows = [];
        foreach ($this->q("SELECT t.codigo, t.created_at, c.razon_social, cat.nombre cat, t.prioridad, t.estado, CONCAT(u.nombres,' ',u.apellidos) tec, t.resuelto_at, t.fecha_limite
                           FROM tickets t JOIN clientes c ON c.id=t.cliente_id JOIN categorias cat ON cat.id=t.categoria_id LEFT JOIN usuarios u ON u.id=t.tecnico_id
                           WHERE t.created_at BETWEEN :a AND :b ORDER BY t.created_at DESC LIMIT 1000", $p) as $t) {
            $hrs = $t['resuelto_at'] ? round((strtotime($t['resuelto_at']) - strtotime($t['created_at'])) / 3600, 1) : '';
            $sla = $t['resuelto_at'] ? ($t['resuelto_at'] <= $t['fecha_limite'] ? 'Cumplido' : 'Fuera de SLA') : (strtotime($t['fecha_limite']) < time() && !in_array($t['estado'], ['resuelto', 'cerrado']) ? 'Vencido' : 'En plazo');
            $rows[] = [$t['codigo'], date('d/m/Y H:i', strtotime($t['created_at'])), $t['razon_social'], $t['cat'], PRIORIDADES[$t['prioridad']], ESTADOS_TK[$t['estado']], $t['tec'] ?? 'Sin asignar', $hrs, $sla];
        }
        return [
            'kpis' => [['Tickets creados', $total, 'bi-ticket-perforated-fill', '#4f46e5'], ['Tickets resueltos', $res, 'bi-check-circle-fill', '#10b981'],
                       ['Cumplimiento SLA', ($res ? round($ok * 100 / $res, 1) : 100) . '%', 'bi-shield-check', '#0ea5e9'], ['Tiempo medio de resolución', round($tmr / 60, 1) . ' h', 'bi-clock-history', '#f59e0b']],
            'chart' => ['type' => 'line', 'title' => 'Tickets creados vs resueltos por día', 'labels' => array_map(fn($x) => date('d/m', strtotime($x)), $days),
                        'datasets' => [['label' => 'Creados', 'data' => array_map(fn($x) => (int)($cre[$x] ?? 0), $days), 'color' => self::PAL[0]], ['label' => 'Resueltos', 'data' => array_map(fn($x) => (int)($rsl[$x] ?? 0), $days), 'color' => self::PAL[1]]]],
            'cols' => ['Ticket', 'Creado', 'Cliente', 'Categoría', 'Prioridad', 'Estado', 'Técnico', 'Horas resolución', 'SLA'], 'rows' => $rows,
        ];
    }

    private function r_sla(string $a, string $b): array
    {
        $p = [':a' => $a, ':b' => $b];
        $rows = []; $tot = 0; $okT = 0;
        foreach ($this->q('SELECT c.razon_social, COUNT(*) n, SUM(t.resuelto_at <= t.fecha_limite) ok FROM tickets t JOIN clientes c ON c.id=t.cliente_id WHERE t.resuelto_at BETWEEN :a AND :b GROUP BY c.id ORDER BY n DESC', $p) as $r) {
            $rows[] = [$r['razon_social'], (int)$r['n'], (int)$r['ok'], (int)$r['n'] - (int)$r['ok'], round($r['ok'] * 100 / $r['n'], 1) . '%']; $tot += $r['n']; $okT += $r['ok'];
        }
        $pr = array_column($this->q('SELECT prioridad, COUNT(*) n, SUM(resuelto_at <= fecha_limite) ok FROM tickets WHERE resuelto_at BETWEEN :a AND :b GROUP BY prioridad', $p), null, 'prioridad');
        $lab = []; $dat = [];
        foreach (PRIORIDADES as $k => $l) { $lab[] = $l; $dat[] = isset($pr[$k]) ? round($pr[$k]['ok'] * 100 / $pr[$k]['n'], 1) : 0; }
        return [
            'kpis' => [['Tickets resueltos', $tot, 'bi-check-circle-fill', '#4f46e5'], ['Dentro de SLA', $okT, 'bi-shield-check', '#10b981'], ['Fuera de SLA', $tot - $okT, 'bi-alarm-fill', '#ef4444'], ['Cumplimiento global', ($tot ? round($okT * 100 / $tot, 1) : 100) . '%', 'bi-graph-up-arrow', '#0ea5e9']],
            'chart' => ['type' => 'bar', 'title' => '% de cumplimiento por prioridad', 'labels' => $lab, 'datasets' => [['label' => '% cumplimiento', 'data' => $dat, 'color' => self::PAL[1]]], 'max' => 100],
            'cols' => ['Cliente', 'Resueltos', 'Dentro de SLA', 'Fuera de SLA', '% cumplimiento'], 'rows' => $rows,
        ];
    }

    private function r_tecnicos(string $a, string $b): array
    {
        $p = [':a' => $a, ':b' => $b, ':c' => $a, ':d' => $b, ':e' => $a, ':f' => $b, ':g' => $a, ':h' => $b, ':i' => $a, ':j' => $b];
        $rows = []; $lab = []; $dat = []; $tr = 0; $hs = 0;
        foreach ($this->q("SELECT CONCAT(u.nombres,' ',u.apellidos) n,
              (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id AND t.created_at BETWEEN :a AND :b) asignados,
              (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id AND t.resuelto_at BETWEEN :c AND :d) resueltos,
              (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id AND t.resuelto_at BETWEEN :e AND :f AND t.resuelto_at <= t.fecha_limite) ok,
              (SELECT AVG(TIMESTAMPDIFF(MINUTE,t.created_at,t.resuelto_at)) FROM tickets t WHERE t.tecnico_id=u.id AND t.resuelto_at BETWEEN :g AND :h) tmr,
              (SELECT COUNT(*) FROM ordenes_servicio o WHERE o.tecnico_id=u.id AND o.estado='completada' AND o.fecha_fin BETWEEN :i AND :j) ordenes,
              (SELECT COALESCE(SUM(o.horas),0) FROM ordenes_servicio o WHERE o.tecnico_id=u.id AND o.estado='completada' AND o.fecha_fin BETWEEN :a2 AND :b2) horas
            FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.estado=1 AND r.nombre IN ('Técnico','Supervisor') ORDER BY resueltos DESC", $p + [':a2' => $a, ':b2' => $b]) as $r) {
            $rows[] = [$r['n'], (int)$r['asignados'], (int)$r['resueltos'], $r['resueltos'] ? round($r['ok'] * 100 / $r['resueltos'], 1) . '%' : '—', $r['tmr'] ? round($r['tmr'] / 60, 1) : '—', (int)$r['ordenes'], (float)$r['horas']];
            $lab[] = $r['n']; $dat[] = (int)$r['resueltos']; $tr += $r['resueltos']; $hs += $r['horas'];
        }
        return [
            'kpis' => [['Técnicos', count($rows), 'bi-people-fill', '#4f46e5'], ['Tickets resueltos', $tr, 'bi-check-circle-fill', '#10b981'], ['Promedio por técnico', count($rows) ? round($tr / count($rows), 1) : 0, 'bi-bar-chart-fill', '#0ea5e9'], ['Horas en órdenes', $hs, 'bi-clock-history', '#f59e0b']],
            'chart' => ['type' => 'bar', 'title' => 'Tickets resueltos por técnico', 'labels' => $lab, 'datasets' => [['label' => 'Resueltos', 'data' => $dat, 'color' => self::PAL[3]]]],
            'cols' => ['Técnico', 'Tickets asignados', 'Resueltos', '% SLA', 'T. medio (h)', 'Órdenes completadas', 'Horas en órdenes'], 'rows' => $rows,
        ];
    }

    private function r_clientes(string $a, string $b): array
    {
        $p = [':a' => $a, ':b' => $b, ':c' => $a, ':d' => $b, ':e' => $a, ':f' => $b, ':g' => $a, ':h' => $b];
        $rows = []; $lab = []; $dat = []; $fact = 0; $tk = 0;
        foreach ($this->q("SELECT c.razon_social n,
              (SELECT COUNT(*) FROM tickets t WHERE t.cliente_id=c.id AND t.created_at BETWEEN :a AND :b) tickets,
              (SELECT COUNT(*) FROM tickets t WHERE t.cliente_id=c.id AND t.resuelto_at BETWEEN :c AND :d) resueltos,
              (SELECT COUNT(*) FROM ordenes_servicio o WHERE o.cliente_id=c.id AND o.estado='completada' AND o.fecha_fin BETWEEN :e AND :f) ordenes,
              (SELECT COALESCE(SUM(o.horas*o.tarifa_hora + COALESCE((SELECT SUM(x.cantidad*x.precio_unit) FROM ordenes_repuestos x WHERE x.orden_id=o.id),0)),0)
                 FROM ordenes_servicio o WHERE o.cliente_id=c.id AND o.estado='completada' AND o.fecha_fin BETWEEN :g AND :h) facturado,
              (SELECT COALESCE(SUM(k.monto_mensual),0) FROM contratos k WHERE k.cliente_id=c.id AND k.estado<>'vencido') mensualidad
            FROM clientes c WHERE c.estado=1 ORDER BY tickets DESC", $p) as $r) {
            $rows[] = [$r['n'], (int)$r['tickets'], (int)$r['resueltos'], (int)$r['ordenes'], number_format($r['facturado'], 2, '.', ''), number_format($r['mensualidad'], 2, '.', '')];
            $fact += $r['facturado']; $tk += $r['tickets'];
        }
        foreach (array_slice($rows, 0, 6) as $r) { $lab[] = $r[0]; $dat[] = $r[1]; }
        return [
            'kpis' => [['Clientes activos', count($rows), 'bi-buildings-fill', '#4f46e5'], ['Tickets en el período', $tk, 'bi-ticket-perforated-fill', '#0ea5e9'], ['Facturado en órdenes', money($fact), 'bi-receipt', '#10b981'], ['Mensualidades vigentes', money(array_sum(array_column($rows, 5))), 'bi-cash-stack', '#8b5cf6']],
            'chart' => ['type' => 'doughnut', 'title' => 'Tickets por cliente (top 6)', 'labels' => $lab, 'datasets' => [['label' => 'Tickets', 'data' => $dat, 'color' => self::PAL]]],
            'cols' => ['Cliente', 'Tickets creados', 'Tickets resueltos', 'Órdenes completadas', 'Facturado órdenes (S/)', 'Mensualidad (S/)'], 'rows' => $rows,
        ];
    }

    private function r_ingresos(string $a, string $b, string $d, string $h): array
    {
        $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $rows = []; $lab = []; $ct = []; $os = []; $tc = 0; $to = 0;
        $t = strtotime(date('Y-m-01', strtotime($d))); $fin = strtotime(date('Y-m-01', strtotime($h)));
        for ($n = 0; $t <= $fin && $n < 24; $t = strtotime('+1 month', $t), $n++) {
            $i = date('Y-m-01', $t); $f = date('Y-m-t', $t);
            $r = $this->q("SELECT COALESCE(SUM(o.horas*o.tarifa_hora),0) mano, COUNT(*) n FROM ordenes_servicio o WHERE o.estado='completada' AND o.fecha_fin BETWEEN ? AND ?", [$i . ' 00:00:00', $f . ' 23:59:59'])[0];
            $rep = (float)$this->v("SELECT COALESCE(SUM(x.cantidad*x.precio_unit),0) FROM ordenes_repuestos x JOIN ordenes_servicio o ON o.id=x.orden_id WHERE o.estado='completada' AND o.fecha_fin BETWEEN ? AND ?", [$i . ' 00:00:00', $f . ' 23:59:59']);
            $con = (float)$this->v('SELECT COALESCE(SUM(monto_mensual),0) FROM contratos WHERE fecha_inicio <= ? AND fecha_fin >= ?', [$f, $i]);
            $rows[] = [$meses[(int)date('n', $t)] . ' ' . date('Y', $t), $con, (int)$r['n'], (float)$r['mano'], $rep, $r['mano'] + $rep, $con + $r['mano'] + $rep];
            $lab[] = $meses[(int)date('n', $t)] . ' ' . date('y', $t); $ct[] = $con; $os[] = round($r['mano'] + $rep, 2); $tc += $con; $to += $r['mano'] + $rep;
        }
        return [
            'kpis' => [['Mensualidades de contratos', money($tc), 'bi-file-earmark-text-fill', '#4f46e5'], ['Órdenes de servicio', money($to), 'bi-clipboard2-check-fill', '#10b981'], ['Total del período', money($tc + $to), 'bi-cash-stack', '#8b5cf6'], ['Meses analizados', count($rows), 'bi-calendar3', '#f59e0b']],
            'chart' => ['type' => 'bar', 'stacked' => true, 'title' => 'Ingresos por mes (S/)', 'labels' => $lab, 'datasets' => [['label' => 'Contratos', 'data' => $ct, 'color' => self::PAL[0]], ['label' => 'Órdenes de servicio', 'data' => $os, 'color' => self::PAL[1]]]],
            'cols' => ['Mes', 'Mensualidades contratos (S/)', 'Órdenes completadas', 'Mano de obra (S/)', 'Repuestos (S/)', 'Total órdenes (S/)', 'Total ingresos (S/)'], 'rows' => $rows,
        ];
    }

    private function r_inventario(): array
    {
        $rows = []; $porCat = []; $valor = 0; $bajo = 0; $ago = 0;
        foreach ($this->q('SELECT * FROM repuestos ORDER BY (stock - stock_minimo) ASC, nombre') as $r) {
            $v = $r['stock'] * $r['precio']; $valor += $v;
            $est = $r['stock'] == 0 ? 'Agotado' : ($r['stock'] <= $r['stock_minimo'] ? 'Stock bajo' : 'Disponible');
            if ($est === 'Agotado') $ago++; elseif ($est === 'Stock bajo') $bajo++;
            $rows[] = [$r['codigo'], $r['nombre'], $r['categoria'] ?? '—', (int)$r['stock'], (int)$r['stock_minimo'], number_format($r['precio'], 2, '.', ''), number_format($v, 2, '.', ''), $est];
            $porCat[$r['categoria'] ?? 'Otros'] = ($porCat[$r['categoria'] ?? 'Otros'] ?? 0) + $v;
        }
        arsort($porCat);
        return [
            'kpis' => [['Ítems', count($rows), 'bi-box-seam-fill', '#4f46e5'], ['Valor del inventario', money($valor), 'bi-currency-dollar', '#10b981'], ['Stock bajo', $bajo, 'bi-exclamation-triangle-fill', '#f59e0b'], ['Agotados', $ago, 'bi-x-octagon-fill', '#ef4444']],
            'chart' => ['type' => 'bar', 'horizontal' => true, 'title' => 'Valor del inventario por categoría (S/)', 'labels' => array_keys($porCat), 'datasets' => [['label' => 'Valor (S/)', 'data' => array_map(fn($x) => round($x, 2), array_values($porCat)), 'color' => self::PAL[2]]]],
            'cols' => ['Código', 'Repuesto', 'Categoría', 'Stock', 'Mínimo', 'Precio (S/)', 'Valorizado (S/)', 'Estado'], 'rows' => $rows,
        ];
    }
}

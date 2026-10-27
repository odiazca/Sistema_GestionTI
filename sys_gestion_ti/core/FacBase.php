<?php
/** Constantes y helpers del módulo Facturación */
const FAC_ROLES = ['Administrador', 'Supervisor'];
const FAC_TIPOS = ['factura' => 'Factura', 'boleta' => 'Boleta de venta', 'recibo' => 'Recibo'];
const FAC_SERIES = ['factura' => 'F001', 'boleta' => 'B001', 'recibo' => 'R001'];
const FAC_CONCEPTOS = ['mensualidad' => 'Mensualidad de contrato', 'servicio' => 'Servicio técnico', 'equipos' => 'Equipos / insumos', 'otro' => 'Otro'];
const FAC_METODOS = ['transferencia' => 'Transferencia', 'deposito' => 'Depósito', 'yape' => 'Yape', 'plin' => 'Plin', 'efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'cheque' => 'Cheque'];
/** Estado efectivo calculado en SQL (pendiente, parcial, vencida, pagada, anulada) */
const FAC_EST_SQL = "CASE WHEN f.estado='anulada' THEN 'anulada' WHEN f.estado='pagada' THEN 'pagada' WHEN f.fecha_vencimiento < CURDATE() THEN 'vencida' WHEN f.pagado > 0 THEN 'parcial' ELSE 'pendiente' END";
const FAC_ESTADOS = ['pendiente' => 'Pendiente', 'parcial' => 'Pago parcial', 'vencida' => 'Vencida', 'pagada' => 'Pagada', 'anulada' => 'Anulada'];

function fac_pill(string $e): string { return pill(FAC_ESTADOS[$e] ?? $e, ['pendiente' => 'soft-info', 'parcial' => 'soft-warning', 'vencida' => 'danger', 'pagada' => 'success', 'anulada' => 'muted'][$e] ?? 'muted'); }

abstract class FacBase extends Controller
{
    protected function guard(): void { $this->requireRole(FAC_ROLES); }
    protected function db(): PDO { return Database::conn(); }
    protected function all(string $sql, array $p = []): array { $s = $this->db()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    protected function one(string $sql, array $p = []): ?array { $s = $this->db()->prepare($sql); $s->execute($p); return $s->fetch() ?: null; }
    protected function val(string $sql, array $p = []) { $s = $this->db()->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
    protected function run(string $sql, array $p = []): void { $this->db()->prepare($sql)->execute($p); }
    protected function str(string $k, int $max = 255): ?string { $v = trim((string)($_POST[$k] ?? '')); return $v === '' ? null : mb_substr($v, 0, $max); }
    protected function clientes(): array { return $this->all('SELECT id, razon_social, ruc FROM clientes WHERE estado=1 ORDER BY razon_social'); }
    protected function audit(string $accion, string $detalle, string $icono = 'bi-receipt', string $color = '#10b981'): void
    {
        try { $this->run('INSERT INTO actividad(usuario_id,accion,detalle,icono,color) VALUES(?,?,?,?,?)', [Auth::user()['id'], $accion, mb_substr($detalle, 0, 190), $icono, $color]); } catch (Throwable $e) {}
    }
    /** Siguiente número correlativo de la serie */
    protected function siguiente(string $serie): int { return (int)$this->val('SELECT COALESCE(MAX(numero),0)+1 FROM fac_comprobantes WHERE serie=?', [$serie]); }
    /** Recalcula lo pagado y el estado de un comprobante a partir de sus pagos */
    protected function recalcular(int $id): void
    {
        $this->run("UPDATE fac_comprobantes f SET f.pagado=(SELECT COALESCE(SUM(monto),0) FROM fac_pagos WHERE comprobante_id=f.id), f.estado=IF(f.estado='anulada','anulada',IF((SELECT COALESCE(SUM(monto),0) FROM fac_pagos WHERE comprobante_id=f.id) >= f.total - 0.005,'pagada','emitida')) WHERE f.id=?", [$id]);
    }
    protected function totales(array $items, float $igvPct): array
    {
        $sub = round(array_sum(array_map(fn($x) => $x['cantidad'] * $x['precio_unit'], $items)), 2); $igv = round($sub * $igvPct / 100, 2);
        return [$sub, $igv, round($sub + $igv, 2)];
    }
    /** Inserta cabecera + ítems en una transacción y devuelve el id */
    protected function emitir(array $c, array $items): int
    {
        $db = $this->db(); $own = !$db->inTransaction(); if ($own) $db->beginTransaction();
        try {
            $serie = FAC_SERIES[$c['tipo']]; $n = $this->siguiente($serie); [$sub, $igv, $tot] = $this->totales($items, $c['igv_pct']);
            $this->run('INSERT INTO fac_comprobantes(tipo,serie,numero,codigo,cliente_id,concepto,contrato_id,orden_id,cotizacion_id,periodo,fecha_emision,fecha_vencimiento,igv_pct,subtotal,igv,total,notas,creado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$c['tipo'], $serie, $n, sprintf('%s-%06d', $serie, $n), $c['cliente_id'], $c['concepto'], $c['contrato_id'] ?? null, $c['orden_id'] ?? null, $c['cotizacion_id'] ?? null, $c['periodo'] ?? null, $c['fecha_emision'], $c['fecha_vencimiento'], $c['igv_pct'], $sub, $igv, $tot, $c['notas'] ?? null, Auth::user()['id']]);
            $id = (int)$db->lastInsertId();
            foreach ($items as $x) $this->run('INSERT INTO fac_items(comprobante_id,descripcion,cantidad,precio_unit) VALUES(?,?,?,?)', [$id, $x['descripcion'], $x['cantidad'], $x['precio_unit']]);
            if ($own) $db->commit();
            return $id;
        } catch (Throwable $e) { if ($own && $db->inTransaction()) $db->rollBack(); throw $e; }
    }
    protected function csv(string $nombre, array $cols, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $nombre . '_' . date('Ymd') . '.csv"');
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
        $safe = fn($v) => is_string($v) && $v !== '' && strpos("=+-@\t\r", $v[0]) !== false ? "'" . $v : $v;
        fputcsv($o, $cols, ';'); foreach ($rows as $r) fputcsv($o, array_map($safe, array_values($r)), ';'); exit;
    }
}

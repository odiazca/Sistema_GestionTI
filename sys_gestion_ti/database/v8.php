<?php
/**
 * Módulo Facturación: comprobantes, ítems y pagos (cuentas por cobrar).
 * Idempotente. Se usa desde install.php y migrar.php.
 */
function instalar_facturacion(PDO $pdo): array
{
    $log = []; $t = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec("CREATE TABLE IF NOT EXISTS fac_comprobantes (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      tipo ENUM('factura','boleta','recibo') NOT NULL DEFAULT 'factura',
      serie VARCHAR(6) NOT NULL,
      numero INT UNSIGNED NOT NULL,
      codigo VARCHAR(20) NOT NULL UNIQUE,
      cliente_id INT UNSIGNED NOT NULL,
      concepto ENUM('mensualidad','servicio','equipos','otro') NOT NULL DEFAULT 'servicio',
      contrato_id INT UNSIGNED NULL,
      orden_id INT UNSIGNED NULL,
      cotizacion_id INT UNSIGNED NULL,
      periodo CHAR(7) NULL,
      fecha_emision DATE NOT NULL,
      fecha_vencimiento DATE NOT NULL,
      igv_pct DECIMAL(5,2) NOT NULL DEFAULT 18,
      subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
      igv DECIMAL(12,2) NOT NULL DEFAULT 0,
      total DECIMAL(12,2) NOT NULL DEFAULT 0,
      pagado DECIMAL(12,2) NOT NULL DEFAULT 0,
      estado ENUM('emitida','pagada','anulada') NOT NULL DEFAULT 'emitida',
      motivo_anulacion VARCHAR(200) NULL,
      notas VARCHAR(500) NULL,
      creado_por INT UNSIGNED NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_serie_num (serie, numero),
      INDEX idx_fac_cli (cliente_id), INDEX idx_fac_est (estado, fecha_vencimiento),
      FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ) $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS fac_items (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      comprobante_id INT UNSIGNED NOT NULL,
      descripcion VARCHAR(255) NOT NULL,
      cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
      precio_unit DECIMAL(12,2) NOT NULL DEFAULT 0,
      FOREIGN KEY (comprobante_id) REFERENCES fac_comprobantes(id) ON DELETE CASCADE
    ) $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS fac_pagos (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      comprobante_id INT UNSIGNED NOT NULL,
      fecha DATE NOT NULL,
      monto DECIMAL(12,2) NOT NULL,
      metodo ENUM('efectivo','transferencia','yape','plin','tarjeta','deposito','cheque') NOT NULL DEFAULT 'transferencia',
      referencia VARCHAR(80) NULL,
      notas VARCHAR(200) NULL,
      registrado_por INT UNSIGNED NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_pag_fecha (fecha),
      FOREIGN KEY (comprobante_id) REFERENCES fac_comprobantes(id) ON DELETE CASCADE
    ) $t");
    $log[] = 'Tablas de facturación listas.';

    if ((int)$pdo->query('SELECT COUNT(*) FROM fac_comprobantes')->fetchColumn() > 0) return $log;

    // ---------- Datos de demostración ----------
    $uid = (int)$pdo->query("SELECT id FROM usuarios WHERE usuario='admin' LIMIT 1")->fetchColumn() ?: null;
    $serieDe = ['factura' => 'F001', 'boleta' => 'B001', 'recibo' => 'R001'];
    $cab = $pdo->prepare('INSERT INTO fac_comprobantes(tipo,serie,numero,codigo,cliente_id,concepto,contrato_id,orden_id,cotizacion_id,periodo,fecha_emision,fecha_vencimiento,igv_pct,subtotal,igv,total,pagado,estado,creado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,18,?,?,?,?,?,?)');
    $it = $pdo->prepare('INSERT INTO fac_items(comprobante_id,descripcion,cantidad,precio_unit) VALUES(?,?,?,?)');
    $pg = $pdo->prepare('INSERT INTO fac_pagos(comprobante_id,fecha,monto,metodo,referencia,registrado_por) VALUES(?,?,?,?,?,?)');
    $num = ['F001' => 0, 'B001' => 0, 'R001' => 0]; $metodos = ['transferencia', 'transferencia', 'yape', 'deposito', 'efectivo', 'plin'];
    $emitir = function (int $cli, string $concepto, array $items, string $emision, int $dias, string $tipo, ?int $ct, ?int $os, ?int $cot, ?string $periodo, float $pagadoPct) use (&$num, $serieDe, $cab, $it, $pg, $pdo, $uid, $metodos) {
        $s = $serieDe[$tipo]; $num[$s]++; $sub = 0; foreach ($items as $x) $sub += $x[1] * $x[2];
        $sub = round($sub, 2); $igv = round($sub * 0.18, 2); $tot = round($sub + $igv, 2); $pag = round($tot * $pagadoPct, 2);
        $est = $pag >= $tot ? 'pagada' : 'emitida'; $venc = date('Y-m-d', strtotime("$emision +$dias days"));
        $cab->execute([$tipo, $s, $num[$s], sprintf('%s-%06d', $s, $num[$s]), $cli, $concepto, $ct, $os, $cot, $periodo, $emision, $venc, $sub, $igv, $tot, $pag, $est, $uid]);
        $id = (int)$pdo->lastInsertId(); foreach ($items as $x) $it->execute([$id, $x[0], $x[1], $x[2]]);
        if ($pag > 0) {
            $f = min(date('Y-m-d'), date('Y-m-d', strtotime("$emision +" . max(1, $dias - 4) . ' days')));
            $pg->execute([$id, $f, $pag, $metodos[$id % 6], 'OP-' . (4000 + $id), $uid]);
        }
    };
    // Mensualidades de los últimos 3 meses por contrato (no vencido)
    $cts = $pdo->query("SELECT k.id, k.cliente_id, k.plan, k.monto_mensual, k.estado, k.fecha_inicio, c.ruc FROM contratos k JOIN clientes c ON c.id=k.cliente_id WHERE k.monto_mensual > 0 ORDER BY k.id")->fetchAll();
    foreach ($cts as $i => $k) {
        for ($m = 3; $m >= 1; $m--) {
            $ini = date('Y-m-01', strtotime("-$m month")); $per = substr($ini, 0, 7);
            if ($k['fecha_inicio'] > date('Y-m-t', strtotime($ini))) continue;
            $pct = 1.0; if ($m === 1 && $i % 3 === 0) $pct = 0.0; if ($m === 1 && $i % 3 === 1) $pct = 0.5; if ($m === 2 && $i % 4 === 0) $pct = 0.0;
            $emitir((int)$k['cliente_id'], 'mensualidad', [['Soporte TI plan ' . $k['plan'] . ' · ' . $per, 1, (float)$k['monto_mensual']]], date('Y-m-05', strtotime($ini)), 15, $k['ruc'] ? 'factura' : 'boleta', (int)$k['id'], null, null, $per, $pct);
        }
    }
    // Órdenes de servicio completadas (mano de obra + repuestos)
    foreach ($pdo->query("SELECT o.id, o.codigo, o.cliente_id, o.horas, o.tarifa_hora, o.tipo, o.fecha_fin, c.ruc FROM ordenes_servicio o JOIN clientes c ON c.id=o.cliente_id WHERE o.estado='completada' AND o.horas > 0 ORDER BY o.id LIMIT 6")->fetchAll() as $i => $o) {
        $items = [['Mano de obra ' . $o['tipo'] . ' · ' . $o['codigo'], (float)$o['horas'], (float)$o['tarifa_hora']]];
        foreach ($pdo->query("SELECT r.nombre, x.cantidad, x.precio_unit FROM ordenes_repuestos x JOIN repuestos r ON r.id=x.repuesto_id WHERE x.orden_id=" . (int)$o['id'])->fetchAll() as $r) $items[] = [$r['nombre'], (float)$r['cantidad'], (float)$r['precio_unit']];
        $f = $o['fecha_fin'] ? substr($o['fecha_fin'], 0, 10) : date('Y-m-d', strtotime('-20 days')); if ($f > date('Y-m-d')) $f = date('Y-m-d');
        $emitir((int)$o['cliente_id'], 'servicio', $items, $f, 7, $o['ruc'] ? 'factura' : 'boleta', null, (int)$o['id'], null, null, $i % 2 ? 1.0 : 0.0);
    }
    // Cotizaciones aprobadas
    foreach ($pdo->query("SELECT q.id, q.cliente_id, q.codigo, q.fecha, c.ruc FROM crm_cotizaciones q JOIN clientes c ON c.id=q.cliente_id WHERE q.estado='aprobada'")->fetchAll() as $q) {
        $items = array_map(fn($x) => [$x['descripcion'], (float)$x['cantidad'], (float)$x['precio_unit']], $pdo->query("SELECT descripcion,cantidad,precio_unit FROM crm_cotizacion_items WHERE cotizacion_id=" . (int)$q['id'])->fetchAll());
        if ($items) $emitir((int)$q['cliente_id'], 'equipos', $items, min(date('Y-m-d'), date('Y-m-d', strtotime($q['fecha'] . ' +2 days'))), 30, $q['ruc'] ? 'factura' : 'boleta', null, null, (int)$q['id'], null, 0.0);
    }
    $log[] = 'Facturación: comprobantes y pagos de demostración creados.';
    return $log;
}

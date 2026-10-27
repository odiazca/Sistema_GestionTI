<?php
require_once ROOT_PATH . '/core/FacBase.php';

class FacturacionController extends FacBase
{
    private const FROM = 'fac_comprobantes f JOIN clientes c ON c.id=f.cliente_id';
    private const COND = "• Pago a la fecha de vencimiento indicada.\n• Transferencias a nombre de la empresa; envíe el voucher para registrar su pago.\n• Pasada la fecha de vencimiento se podrá suspender la atención fuera de contrato.";

    private function filtros(): array
    {
        $w = []; $p = [];
        if (($q = trim($_GET['q'] ?? '')) !== '') { $w[] = '(f.codigo LIKE :q1 OR c.razon_social LIKE :q2 OR c.ruc LIKE :q3)'; $p[':q1'] = $p[':q2'] = $p[':q3'] = "%$q%"; }
        if (isset(FAC_ESTADOS[$_GET['estado'] ?? ''])) { $w[] = '(' . FAC_EST_SQL . ') = :e'; $p[':e'] = $_GET['estado']; }
        if (isset(FAC_CONCEPTOS[$_GET['concepto'] ?? ''])) { $w[] = 'f.concepto = :co'; $p[':co'] = $_GET['concepto']; }
        if (ctype_digit((string)($_GET['cliente'] ?? '')) && $_GET['cliente'] !== '') { $w[] = 'f.cliente_id = :cl'; $p[':cl'] = (int)$_GET['cliente']; }
        if (preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '')) { $w[] = "DATE_FORMAT(f.fecha_emision,'%Y-%m') = :m"; $p[':m'] = $_GET['mes']; }
        return [$w, $p];
    }

    public function index(): void
    {
        $this->guard(); [$w, $p] = $this->filtros(); $W = $w ? ' WHERE ' . implode(' AND ', $w) : '';
        $total = (int)$this->val('SELECT COUNT(*) FROM ' . self::FROM . $W, $p); $pg = $this->paginate($total, 12, (int)($_GET['page'] ?? 1));
        $rows = $this->all('SELECT f.*, c.razon_social AS cliente, (' . FAC_EST_SQL . ') AS est, (f.total - f.pagado) AS saldo FROM ' . self::FROM . "$W ORDER BY f.fecha_emision DESC, f.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
        $st = $this->one("SELECT
            COALESCE(SUM(CASE WHEN f.estado='emitida' THEN f.total - f.pagado END),0) por_cobrar,
            COALESCE(SUM(CASE WHEN f.estado='emitida' AND f.fecha_vencimiento < CURDATE() THEN f.total - f.pagado END),0) vencido,
            COALESCE(SUM(f.estado='emitida' AND f.fecha_vencimiento < CURDATE()),0) n_vencidos,
            COALESCE(SUM(CASE WHEN f.estado<>'anulada' AND f.fecha_emision >= DATE_FORMAT(NOW(),'%Y-%m-01') THEN f.total END),0) facturado_mes
            FROM fac_comprobantes f");
        $st['cobrado_mes'] = (float)$this->val("SELECT COALESCE(SUM(p.monto),0) FROM fac_pagos p JOIN fac_comprobantes f ON f.id=p.comprobante_id WHERE f.estado<>'anulada' AND p.fecha >= DATE_FORMAT(NOW(),'%Y-%m-01')");
        $this->render('facturacion/index', compact('rows', 'pg', 'st') + ['clientes' => $this->clientes()], 'Facturación');
    }

    /* ---------- Crear / editar ---------- */
    private function formData(array $row, array $items, array $errors = []): array
    {
        return ['row' => $row, 'items' => $items, 'errors' => $errors, 'id' => (int)($row['id'] ?? 0), 'clientes' => $this->clientes()];
    }

    public function crear(): void
    {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar(0); return; }
        $pre = ['tipo' => 'factura', 'concepto' => 'servicio', 'fecha_emision' => date('Y-m-d'), 'fecha_vencimiento' => date('Y-m-d', strtotime('+15 days')), 'igv_pct' => cfg('igv', 18), 'notas' => self::COND];
        $items = [['descripcion' => '', 'cantidad' => 1, 'precio_unit' => '']]; $cli = (int)($_GET['cliente'] ?? 0);
        if ($cli) $pre['cliente_id'] = $cli;
        if (($os = (int)($_GET['orden'] ?? 0)) && ($o = $this->one("SELECT * FROM ordenes_servicio WHERE id=?", [$os]))) {
            $pre['cliente_id'] = $o['cliente_id']; $pre['orden_id'] = $os; $pre['concepto'] = 'servicio';
            $items = [['descripcion' => 'Mano de obra ' . $o['tipo'] . ' · ' . $o['codigo'], 'cantidad' => (float)$o['horas'], 'precio_unit' => (float)$o['tarifa_hora']]];
            foreach ($this->all('SELECT r.nombre, x.cantidad, x.precio_unit FROM ordenes_repuestos x JOIN repuestos r ON r.id=x.repuesto_id WHERE x.orden_id=?', [$os]) as $r) $items[] = ['descripcion' => $r['nombre'], 'cantidad' => (float)$r['cantidad'], 'precio_unit' => (float)$r['precio_unit']];
        } elseif (($cq = (int)($_GET['cotizacion'] ?? 0)) && ($q = $this->one('SELECT * FROM crm_cotizaciones WHERE id=?', [$cq]))) {
            $pre['cliente_id'] = $q['cliente_id']; $pre['cotizacion_id'] = $cq; $pre['concepto'] = 'equipos'; $pre['igv_pct'] = $q['igv_pct'];
            $items = $this->all('SELECT descripcion, cantidad, precio_unit FROM crm_cotizacion_items WHERE cotizacion_id=? ORDER BY id', [$cq]);
            if ((float)$q['descuento_pct'] > 0) { $pre['notas'] = 'Incluye descuento de ' . (float)$q['descuento_pct'] . "% de la cotización {$q['codigo']}.\n" . self::COND; foreach ($items as &$x) $x['precio_unit'] = round($x['precio_unit'] * (1 - $q['descuento_pct'] / 100), 2); unset($x); }
        }
        if (!empty($pre['cliente_id'])) { $r = $this->val('SELECT ruc FROM clientes WHERE id=?', [$pre['cliente_id']]); $pre['tipo'] = $r ? 'factura' : 'boleta'; }
        $this->render('facturacion/form', $this->formData($pre, $items), 'Nuevo comprobante');
    }

    public function editar($id = 0): void
    {
        $this->guard(); $row = $this->one('SELECT * FROM fac_comprobantes WHERE id=?', [(int)$id]);
        if (!$row) { $this->flash('danger', 'El comprobante no existe.'); $this->redirect('facturacion'); }
        if ($row['estado'] !== 'emitida' || (float)$row['pagado'] > 0) { $this->flash('warning', 'Solo se editan comprobantes emitidos sin pagos. Anula y emite uno nuevo si hay un error.'); $this->redirect("facturacion/ver/$id"); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->guardar((int)$id); return; }
        $this->render('facturacion/form', $this->formData($row, $this->all('SELECT * FROM fac_items WHERE comprobante_id=? ORDER BY id', [(int)$id])), 'Editar ' . $row['codigo']);
    }

    private function guardar(int $id): void
    {
        $this->requirePost($id ? "facturacion/editar/$id" : 'facturacion/crear');
        $cli = (int)($_POST['cliente_id'] ?? 0); $tipo = $_POST['tipo'] ?? ''; $con = $_POST['concepto'] ?? '';
        $em = $_POST['fecha_emision'] ?? ''; $ve = $_POST['fecha_vencimiento'] ?? ''; $igvp = max(0, min(100, (float)($_POST['igv_pct'] ?? 18)));
        $items = []; foreach (($_POST['item_desc'] ?? []) as $i => $d) {
            $d = trim((string)$d); $c = (float)str_replace(',', '', (string)($_POST['item_cant'][$i] ?? 0)); $pr = (float)str_replace(',', '', (string)($_POST['item_precio'][$i] ?? 0));
            if ($d !== '' && $c > 0 && $pr >= 0) $items[] = ['descripcion' => mb_substr($d, 0, 255), 'cantidad' => $c, 'precio_unit' => $pr];
        }
        $err = [];
        $c = $this->one('SELECT id, ruc FROM clientes WHERE id=?', [$cli]);
        if (!$c) $err['cliente_id'] = 'Selecciona el cliente.';
        if (!isset(FAC_TIPOS[$tipo])) $err['tipo'] = 'Tipo no válido.'; elseif ($tipo === 'factura' && $c && !$c['ruc']) $err['cliente_id'] = 'Para emitir factura el cliente debe tener RUC. Usa boleta o completa su ficha.';
        if (!isset(FAC_CONCEPTOS[$con])) $err['concepto'] = 'Concepto no válido.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $em)) $err['fecha_emision'] = 'Fecha no válida.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ve) || $ve < $em) $err['fecha_vencimiento'] = 'El vencimiento no puede ser anterior a la emisión.';
        if (!$items) $err['items'] = 'Agrega al menos un ítem con descripción, cantidad y precio.';
        $link = ['contrato_id' => (int)($_POST['contrato_id'] ?? 0) ?: null, 'orden_id' => (int)($_POST['orden_id'] ?? 0) ?: null, 'cotizacion_id' => (int)($_POST['cotizacion_id'] ?? 0) ?: null, 'periodo' => preg_match('/^\d{4}-\d{2}$/', $_POST['periodo'] ?? '') ? $_POST['periodo'] : null];
        $row = ['id' => $id, 'cliente_id' => $cli, 'tipo' => $tipo, 'concepto' => $con, 'fecha_emision' => $em, 'fecha_vencimiento' => $ve, 'igv_pct' => $igvp, 'notas' => $this->str('notas', 500)] + $link;
        if ($err) { $this->render('facturacion/form', $this->formData($row, $items ?: [['descripcion' => '', 'cantidad' => 1, 'precio_unit' => '']], $err), $id ? 'Editar comprobante' : 'Nuevo comprobante'); return; }
        if ($id) {
            [$sub, $igv, $tot] = $this->totales($items, $igvp); $old = $this->one('SELECT tipo, serie, numero FROM fac_comprobantes WHERE id=?', [$id]); $db = $this->db();
            try {
                $db->beginTransaction();
                $serie = FAC_SERIES[$tipo]; $n = $old['tipo'] === $tipo ? (int)$old['numero'] : $this->siguiente($serie);
                $this->run('UPDATE fac_comprobantes SET tipo=?, serie=?, numero=?, codigo=?, cliente_id=?, concepto=?, fecha_emision=?, fecha_vencimiento=?, igv_pct=?, subtotal=?, igv=?, total=?, notas=? WHERE id=?', [$tipo, $serie, $n, sprintf('%s-%06d', $serie, $n), $cli, $con, $em, $ve, $igvp, $sub, $igv, $tot, $row['notas'], $id]);
                $this->run('DELETE FROM fac_items WHERE comprobante_id=?', [$id]);
                foreach ($items as $x) $this->run('INSERT INTO fac_items(comprobante_id,descripcion,cantidad,precio_unit) VALUES(?,?,?,?)', [$id, $x['descripcion'], $x['cantidad'], $x['precio_unit']]);
                $db->commit();
            } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); $this->flash('danger', 'No se pudo guardar: ' . $e->getMessage()); $this->redirect("facturacion/editar/$id"); }
            $this->flash('success', 'Comprobante actualizado.'); $this->redirect("facturacion/ver/$id");
        }
        try { $nid = $this->emitir($row, $items); } catch (Throwable $e) { $this->flash('danger', 'No se pudo emitir: ' . $e->getMessage()); $this->redirect('facturacion/crear'); }
        $cod = $this->val('SELECT codigo FROM fac_comprobantes WHERE id=?', [$nid]);
        $this->audit("Emitió $cod", $c['id'] . ' · ' . money(array_sum(array_map(fn($x) => $x['cantidad'] * $x['precio_unit'], $items)) * (1 + $igvp / 100)));
        $this->flash('success', "Comprobante $cod emitido."); $this->redirect("facturacion/ver/$nid");
    }

    private function cargar(int $id): array
    {
        $f = $this->one('SELECT f.*, (' . FAC_EST_SQL . ') AS est, (f.total - f.pagado) AS saldo, c.razon_social AS cliente, c.ruc, c.direccion, c.email AS cli_email, c.telefono AS cli_tel, k.codigo AS contrato, o.codigo AS orden, q.codigo AS cotizacion, CONCAT(u.nombres," ",u.apellidos) AS autor
            FROM fac_comprobantes f JOIN clientes c ON c.id=f.cliente_id LEFT JOIN contratos k ON k.id=f.contrato_id LEFT JOIN ordenes_servicio o ON o.id=f.orden_id LEFT JOIN crm_cotizaciones q ON q.id=f.cotizacion_id LEFT JOIN usuarios u ON u.id=f.creado_por WHERE f.id=?', [$id]);
        if (!$f) { $this->flash('danger', 'El comprobante no existe.'); $this->redirect('facturacion'); }
        return [$f, $this->all('SELECT * FROM fac_items WHERE comprobante_id=? ORDER BY id', [$id])];
    }

    public function ver($id = 0): void
    {
        $this->guard(); [$f, $items] = $this->cargar((int)$id);
        $pagos = $this->all('SELECT p.*, CONCAT(u.nombres," ",u.apellidos) AS quien FROM fac_pagos p LEFT JOIN usuarios u ON u.id=p.registrado_por WHERE p.comprobante_id=? ORDER BY p.fecha, p.id', [(int)$id]);
        $this->render('facturacion/ver', compact('f', 'items', 'pagos'), $f['codigo']);
    }

    public function imprimir($id = 0): void { $this->guard(); [$f, $items] = $this->cargar((int)$id); $this->renderPlain('facturacion/imprimir', compact('f', 'items')); }

    /* ---------- Pagos y anulación ---------- */
    public function pago($id = 0): void
    {
        $this->guard(); $this->requirePost("facturacion/ver/$id"); $id = (int)$id; [$f] = $this->cargar($id);
        if ($f['estado'] !== 'emitida') { $this->flash('danger', 'Este comprobante no admite pagos.'); $this->redirect("facturacion/ver/$id"); }
        $m = round((float)str_replace(',', '', (string)($_POST['monto'] ?? 0)), 2); $fe = $_POST['fecha'] ?? ''; $me = $_POST['metodo'] ?? '';
        $saldo = round($f['total'] - $f['pagado'], 2);
        if ($m <= 0 || $m > $saldo + 0.005) { $this->flash('danger', 'El monto debe ser mayor a 0 y no superar el saldo (' . money($saldo) . ').'); $this->redirect("facturacion/ver/$id"); }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fe) || $fe > date('Y-m-d')) { $this->flash('danger', 'La fecha de pago no es válida.'); $this->redirect("facturacion/ver/$id"); }
        if (!isset(FAC_METODOS[$me])) $me = 'transferencia';
        $this->run('INSERT INTO fac_pagos(comprobante_id,fecha,monto,metodo,referencia,notas,registrado_por) VALUES(?,?,?,?,?,?,?)', [$id, $fe, $m, $me, $this->str('referencia', 80), $this->str('notas', 200), Auth::user()['id']]);
        $this->recalcular($id); $this->audit('Registró pago ' . $f['codigo'], money($m) . ' · ' . FAC_METODOS[$me], 'bi-cash-coin');
        $this->flash('success', 'Pago de ' . money($m) . ' registrado.' . ($m >= $saldo - 0.005 ? ' El comprobante quedó cancelado.' : '')); $this->redirect("facturacion/ver/$id");
    }

    public function quitarpago($id = 0): void
    {
        $this->guard(); $this->requirePost('facturacion'); $p = $this->one('SELECT * FROM fac_pagos WHERE id=?', [(int)$id]);
        if (!$p) $this->redirect('facturacion');
        $this->run('DELETE FROM fac_pagos WHERE id=?', [$p['id']]); $this->recalcular((int)$p['comprobante_id']);
        $this->audit('Eliminó un pago', money($p['monto']), 'bi-cash-coin', '#ef4444'); $this->flash('success', 'Pago eliminado y saldo recalculado.'); $this->redirect('facturacion/ver/' . $p['comprobante_id']);
    }

    public function anular($id = 0): void
    {
        $this->guard(); $this->requirePost("facturacion/ver/$id"); $id = (int)$id; [$f] = $this->cargar($id); $m = $this->str('motivo', 200);
        if ($f['estado'] === 'anulada') $this->redirect("facturacion/ver/$id");
        if (!$m) { $this->flash('danger', 'Indica el motivo de la anulación.'); $this->redirect("facturacion/ver/$id"); }
        if ((float)$f['pagado'] > 0) { $this->flash('danger', 'El comprobante tiene pagos registrados. Elimina los pagos antes de anular.'); $this->redirect("facturacion/ver/$id"); }
        $this->run("UPDATE fac_comprobantes SET estado='anulada', motivo_anulacion=? WHERE id=?", [$m, $id]);
        $this->audit('Anuló ' . $f['codigo'], $m, 'bi-x-octagon-fill', '#ef4444'); $this->flash('warning', 'Comprobante anulado.'); $this->redirect("facturacion/ver/$id");
    }

    /* ---------- Facturación recurrente ---------- */
    public function recurrente(): void
    {
        $this->guard(); $per = preg_match('/^\d{4}-\d{2}$/', $_GET['periodo'] ?? '') ? $_GET['periodo'] : date('Y-m');
        $ini = "$per-01"; $fin = date('Y-m-t', strtotime($ini));
        $rows = $this->all("SELECT k.id, k.codigo, k.plan, k.monto_mensual, k.estado, k.fecha_inicio, k.fecha_fin, c.id cid, c.razon_social cliente, c.ruc,
            (SELECT f.codigo FROM fac_comprobantes f WHERE f.contrato_id=k.id AND f.periodo=:p1 AND f.estado<>'anulada' LIMIT 1) facturado
            FROM contratos k JOIN clientes c ON c.id=k.cliente_id WHERE k.monto_mensual > 0 AND k.fecha_inicio <= :fin AND k.fecha_fin >= :ini AND c.estado=1 ORDER BY c.razon_social", [':p1' => $per, ':fin' => $fin, ':ini' => $ini]);
        $this->render('facturacion/recurrente', ['rows' => $rows, 'per' => $per], 'Facturación recurrente');
    }

    public function generar(): void
    {
        $this->guard(); $this->requirePost('facturacion/recurrente'); $per = preg_match('/^\d{4}-\d{2}$/', $_POST['periodo'] ?? '') ? $_POST['periodo'] : date('Y-m');
        $ids = array_map('intval', (array)($_POST['ids'] ?? [])); $dias = max(0, min(90, (int)($_POST['dias'] ?? 15)));
        $em = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['emision'] ?? '') ? $_POST['emision'] : date('Y-m-d');
        if (!$ids) { $this->flash('warning', 'Selecciona al menos un contrato.'); $this->redirect("facturacion/recurrente?periodo=$per"); }
        $n = 0; $tot = 0;
        foreach ($ids as $kid) {
            $k = $this->one('SELECT k.*, c.ruc FROM contratos k JOIN clientes c ON c.id=k.cliente_id WHERE k.id=?', [$kid]); if (!$k || $k['monto_mensual'] <= 0) continue;
            if ((int)$this->val("SELECT COUNT(*) FROM fac_comprobantes WHERE contrato_id=? AND periodo=? AND estado<>'anulada'", [$kid, $per])) continue;
            $this->emitir(['tipo' => $k['ruc'] ? 'factura' : 'boleta', 'cliente_id' => $k['cliente_id'], 'concepto' => 'mensualidad', 'contrato_id' => $kid, 'periodo' => $per, 'fecha_emision' => $em, 'fecha_vencimiento' => date('Y-m-d', strtotime("$em +$dias days")), 'igv_pct' => cfg('igv', 18), 'notas' => self::COND],
                [['descripcion' => 'Soporte TI plan ' . $k['plan'] . ' · contrato ' . $k['codigo'] . ' · ' . $per, 'cantidad' => 1, 'precio_unit' => (float)$k['monto_mensual']]]);
            $n++; $tot += $k['monto_mensual'] * (1 + cfg('igv', 18) / 100);
        }
        $this->audit("Facturación recurrente $per", "$n comprobante(s) · " . money($tot));
        $this->flash($n ? 'success' : 'info', $n ? "Se emitieron $n comprobante(s) de mensualidad por " . money($tot) . '.' : 'Esos contratos ya estaban facturados en el período.'); $this->redirect('facturacion?concepto=mensualidad&mes=' . $per);
    }

    /* ---------- Cuentas por cobrar ---------- */
    public function cobranza(): void
    {
        $this->guard();
        $rows = $this->all("SELECT c.id, c.razon_social cliente, c.telefono, COUNT(*) docs, SUM(f.total - f.pagado) saldo,
            SUM(CASE WHEN f.fecha_vencimiento >= CURDATE() THEN f.total - f.pagado ELSE 0 END) vigente,
            SUM(CASE WHEN DATEDIFF(CURDATE(), f.fecha_vencimiento) BETWEEN 1 AND 30 THEN f.total - f.pagado ELSE 0 END) d30,
            SUM(CASE WHEN DATEDIFF(CURDATE(), f.fecha_vencimiento) BETWEEN 31 AND 60 THEN f.total - f.pagado ELSE 0 END) d60,
            SUM(CASE WHEN DATEDIFF(CURDATE(), f.fecha_vencimiento) BETWEEN 61 AND 90 THEN f.total - f.pagado ELSE 0 END) d90,
            SUM(CASE WHEN DATEDIFF(CURDATE(), f.fecha_vencimiento) > 90 THEN f.total - f.pagado ELSE 0 END) d90m,
            MAX(GREATEST(DATEDIFF(CURDATE(), f.fecha_vencimiento), 0)) max_dias
            FROM fac_comprobantes f JOIN clientes c ON c.id=f.cliente_id WHERE f.estado='emitida' GROUP BY c.id ORDER BY max_dias DESC, saldo DESC");
        $tot = ['saldo' => 0, 'vigente' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd90m' => 0]; foreach ($rows as $r) foreach ($tot as $k => $_) $tot[$k] += $r[$k];
        $this->render('facturacion/cobranza', compact('rows', 'tot'), 'Cuentas por cobrar');
    }

    /** Crea una tarea de cobranza en la agenda del CRM */
    public function agendar($cliente = 0): void
    {
        $this->guard(); $this->requirePost('facturacion/cobranza'); $cid = (int)$cliente;
        $c = $this->one("SELECT c.razon_social, SUM(f.total - f.pagado) saldo FROM clientes c JOIN fac_comprobantes f ON f.cliente_id=c.id AND f.estado='emitida' WHERE c.id=? GROUP BY c.id", [$cid]);
        if (!$c) $this->redirect('facturacion/cobranza');
        try {
            $this->run("INSERT INTO crm_actividades(tipo,asunto,fecha,cliente_id,responsable_id,creado_por) VALUES('llamada',?,?,?,?,?)", ['Gestionar cobranza ' . money($c['saldo']) . ' · ' . $c['razon_social'], date('Y-m-d 09:00:00', strtotime('+1 day')), $cid, Auth::user()['id'], Auth::user()['id']]);
            $this->flash('success', 'Llamada de cobranza agendada para mañana en Actividades.');
        } catch (Throwable $e) { $this->flash('warning', 'El módulo CRM no está instalado; no se pudo agendar.'); }
        $this->redirect('facturacion/cobranza');
    }

    /* ---------- Reporte de ingresos ---------- */
    public function ingresos(): void
    {
        $this->guard(); $mn = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $fact = array_column($this->all("SELECT DATE_FORMAT(fecha_emision,'%Y-%m') m, SUM(total) v FROM fac_comprobantes WHERE estado<>'anulada' AND fecha_emision >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') GROUP BY m"), 'v', 'm');
        $cob = array_column($this->all("SELECT DATE_FORMAT(p.fecha,'%Y-%m') m, SUM(p.monto) v FROM fac_pagos p JOIN fac_comprobantes f ON f.id=p.comprobante_id WHERE f.estado<>'anulada' AND p.fecha >= DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH,'%Y-%m-01') GROUP BY m"), 'v', 'm');
        $serie = []; for ($i = 11; $i >= 0; $i--) { $m = date('Y-m', strtotime("first day of -$i month")); $serie[] = ['l' => $mn[(int)substr($m, 5)] . ' ' . substr($m, 2, 2), 'f' => round((float)($fact[$m] ?? 0), 2), 'c' => round((float)($cob[$m] ?? 0), 2)]; }
        $metodos = $this->all("SELECT metodo l, SUM(monto) v FROM fac_pagos p JOIN fac_comprobantes f ON f.id=p.comprobante_id WHERE f.estado<>'anulada' AND p.fecha >= CURDATE() - INTERVAL 12 MONTH GROUP BY metodo ORDER BY v DESC");
        foreach ($metodos as &$m) { $m['l'] = FAC_METODOS[$m['l']] ?? $m['l']; $m['v'] = (float)$m['v']; } unset($m);
        $conceptos = $this->all("SELECT concepto l, SUM(total) v FROM fac_comprobantes WHERE estado<>'anulada' AND fecha_emision >= CURDATE() - INTERVAL 12 MONTH GROUP BY concepto ORDER BY v DESC");
        foreach ($conceptos as &$m) { $m['l'] = FAC_CONCEPTOS[$m['l']] ?? $m['l']; $m['v'] = (float)$m['v']; } unset($m);
        $top = $this->all("SELECT c.id, c.razon_social cliente, SUM(f.total) facturado, SUM(f.pagado) cobrado, SUM(f.total - f.pagado) pendiente FROM fac_comprobantes f JOIN clientes c ON c.id=f.cliente_id WHERE f.estado<>'anulada' AND f.fecha_emision >= CURDATE() - INTERVAL 12 MONTH GROUP BY c.id ORDER BY facturado DESC LIMIT 8");
        $k = $this->one("SELECT COALESCE(SUM(CASE WHEN estado<>'anulada' AND fecha_emision >= CURDATE() - INTERVAL 12 MONTH THEN total END),0) fact12, COALESCE(SUM(CASE WHEN estado='emitida' THEN total - pagado END),0) pendiente, COALESCE(SUM(CASE WHEN estado='emitida' AND fecha_vencimiento < CURDATE() THEN total - pagado END),0) vencido, COALESCE(SUM(CASE WHEN estado<>'anulada' AND concepto='mensualidad' AND fecha_emision >= DATE_FORMAT(NOW(),'%Y-%m-01') THEN total END),0) recurrente_mes FROM fac_comprobantes");
        $k['cobrado12'] = (float)$this->val("SELECT COALESCE(SUM(p.monto),0) FROM fac_pagos p JOIN fac_comprobantes f ON f.id=p.comprobante_id WHERE f.estado<>'anulada' AND p.fecha >= CURDATE() - INTERVAL 12 MONTH");
        $k['eficiencia'] = $k['fact12'] > 0 ? round($k['cobrado12'] * 100 / $k['fact12']) : 0;
        $this->render('facturacion/ingresos', compact('serie', 'metodos', 'conceptos', 'top', 'k'), 'Ingresos y cobranza');
    }

    public function exportar(): void
    {
        $this->guard(); [$w, $p] = $this->filtros(); $W = $w ? ' WHERE ' . implode(' AND ', $w) : '';
        $rows = $this->all('SELECT f.codigo, f.tipo, c.razon_social, c.ruc, f.concepto, f.fecha_emision, f.fecha_vencimiento, f.subtotal, f.igv, f.total, f.pagado, (f.total - f.pagado) saldo, (' . FAC_EST_SQL . ') est FROM ' . self::FROM . "$W ORDER BY f.fecha_emision DESC, f.id DESC", $p);
        $this->csv('comprobantes', ['Código', 'Tipo', 'Cliente', 'RUC', 'Concepto', 'Emisión', 'Vencimiento', 'Subtotal', 'IGV', 'Total', 'Pagado', 'Saldo', 'Estado'], $rows);
    }
}

<?php
$val = fn($k) => $row[$k] ?? '';
$err = fn($k) => isset($errors[$k]) ? '<div class="err"><i class="bi bi-exclamation-circle"></i> ' . e($errors[$k]) . '</div>' : '';
$inv = fn($k) => isset($errors[$k]) ? 'is-invalid' : '';
$rucs = []; foreach ($clientes as $c) $rucs[$c['id']] = $c['ruc'] ? 1 : 0;
?>
<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('facturacion') ?>">Facturación</a> / <?= $id ? 'Editar' : 'Nuevo' ?></div><h1><?= $id ? 'Editar ' . e($row['codigo'] ?? 'comprobante') : 'Nuevo comprobante' ?></h1></div></div>
<form method="post" class="form-ui" novalidate id="frmFac"><?= csrf_field() ?>
<?php foreach (['contrato_id', 'orden_id', 'cotizacion_id', 'periodo'] as $h): if ($val($h)): ?><input type="hidden" name="<?= $h ?>" value="<?= e($val($h)) ?>"><?php endif; endforeach; ?>
<div class="row g-3"><div class="col-xl-8">
  <div class="card-ui mb-3"><div class="form-section" style="margin-top:0">Cliente y documento</div><div class="row g-3">
    <div class="col-md-6"><label class="lbl">Cliente <span class="req">*</span></label><select class="form-select <?= $inv('cliente_id') ?>" name="cliente_id" id="fcCli" data-rucs='<?= json_encode($rucs) ?>'><option value="">— Seleccionar —</option><?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$val('cliente_id') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['razon_social']) ?></option><?php endforeach; ?></select><?= $err('cliente_id') ?></div>
    <div class="col-md-3"><label class="lbl">Tipo <span class="req">*</span></label><select class="form-select <?= $inv('tipo') ?>" name="tipo" id="fcTipo"><?php foreach (FAC_TIPOS as $k => $v): ?><option value="<?= $k ?>" <?= $val('tipo') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select><?= $err('tipo') ?></div>
    <div class="col-md-3"><label class="lbl">Concepto</label><select class="form-select" name="concepto"><?php foreach (FAC_CONCEPTOS as $k => $v): ?><option value="<?= $k ?>" <?= $val('concepto') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
  </div></div>
  <div class="card-ui mb-3"><div class="card-head"><div><h6>Detalle</h6></div><button type="button" class="btn btn-soft btn-sm" id="fcAdd"><i class="bi bi-plus-lg"></i> Ítem</button></div><?= $err('items') ?>
    <div class="table-responsive"><table class="table-ui cq-items"><thead><tr><th>Descripción</th><th style="width:90px">Cant.</th><th style="width:130px">P. unit. (<?= e(fmt_cfg()['simbolo']) ?>)</th><th style="width:120px" class="text-end">Importe</th><th style="width:40px"></th></tr></thead><tbody id="fcBody">
    <?php foreach ($items as $it): ?><tr><td><input class="form-control" name="item_desc[]" maxlength="255" value="<?= e($it['descripcion']) ?>"></td><td><input type="number" class="form-control cq-c" name="item_cant[]" min="0.01" step="0.01" value="<?= e($it['cantidad']) ?>"></td><td><input type="number" class="form-control cq-p" name="item_precio[]" min="0" step="0.01" value="<?= e($it['precio_unit']) ?>"></td><td class="text-end fw-semibold cq-i">0.00</td><td><button type="button" class="btn btn-ghost btn-sm text-danger cq-del"><i class="bi bi-x-lg"></i></button></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  <div class="card-ui"><label class="lbl">Condiciones / notas (aparecen en el comprobante)</label><textarea class="form-control" name="notas" rows="3" maxlength="500"><?= e($val('notas')) ?></textarea></div>
</div>
<div class="col-xl-4"><div class="card-ui" style="position:sticky;top:84px">
  <div class="form-section" style="margin-top:0">Fechas</div><div class="row g-2 mb-3">
    <div class="col-6"><label class="lbl">Emisión</label><input type="date" class="form-control <?= $inv('fecha_emision') ?>" name="fecha_emision" id="fcEm" value="<?= e($val('fecha_emision')) ?>"><?= $err('fecha_emision') ?></div>
    <div class="col-6"><label class="lbl">Vencimiento</label><input type="date" class="form-control <?= $inv('fecha_vencimiento') ?>" name="fecha_vencimiento" id="fcVe" value="<?= e($val('fecha_vencimiento')) ?>"><?= $err('fecha_vencimiento') ?></div>
    <div class="col-12 d-flex gap-1 flex-wrap"><?php foreach ([0 => 'Contado', 7 => '7 días', 15 => '15 días', 30 => '30 días'] as $d => $l): ?><button type="button" class="btn btn-ghost btn-sm" data-dias="<?= $d ?>"><?= $l ?></button><?php endforeach; ?></div>
    <div class="col-6"><label class="lbl"><?= e(impuesto_nombre()) ?> %</label><input type="number" min="0" max="100" step="0.01" class="form-control" name="igv_pct" id="fcIgv" value="<?= e($val('igv_pct')) ?>"></div></div>
  <div class="cq-totals"><div><span>Subtotal</span><b id="tSub"><?= e(num(0)) ?></b></div><div><span><?= e(impuesto_nombre()) ?></span><b id="tIgv"><?= e(num(0)) ?></b></div><div class="tot"><span>Total</span><b id="tTot"><?= e(money(0)) ?></b></div></div>
  <div class="d-grid gap-2 mt-3"><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i><?= $id ? 'Guardar cambios' : 'Emitir comprobante' ?></button><a class="btn btn-ghost" href="<?= url($id ? 'facturacion/ver/' . $id : 'facturacion') ?>">Cancelar</a></div>
</div></div></div></form>
<?php $scripts = '<script src="' . asset('js/fac.js') . '"></script>'; ?>

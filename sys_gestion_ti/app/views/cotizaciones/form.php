<?php
$val = fn($k) => $row[$k] ?? '';
$err = fn($k) => isset($errors[$k]) ? '<div class="err"><i class="bi bi-exclamation-circle"></i> ' . e($errors[$k]) . '</div>' : '';
$inv = fn($k) => isset($errors[$k]) ? 'is-invalid' : '';
?>
<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('cotizaciones') ?>">Cotizaciones</a> / <?= $id ? 'Editar' : 'Nueva' ?></div><h1><?= $id ? 'Editar ' . e($row['codigo']) : 'Nueva cotización' ?></h1></div></div>
<form method="post" class="form-ui" novalidate id="frmCot"><?= csrf_field() ?>
<div class="row g-3"><div class="col-xl-8">
  <div class="card-ui mb-3"><div class="form-section" style="margin-top:0">Cliente</div><div class="row g-3">
    <div class="col-md-6"><label class="lbl">Cliente <span class="req">*</span></label><select class="form-select <?= $inv('cliente_id') ?>" name="cliente_id" id="cqCli"><option value="">— Seleccionar —</option><?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$val('cliente_id') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['razon_social']) ?></option><?php endforeach; ?></select><?= $err('cliente_id') ?></div>
    <div class="col-md-6"><label class="lbl">Contacto</label><select class="form-select" name="contacto_id" id="cqCon" data-sel="<?= (int)$val('contacto_id') ?>"><option value="">— Sin contacto —</option><?php foreach ($contactos as $k): ?><option value="<?= $k['id'] ?>" data-cli="<?= $k['cliente_id'] ?>"><?= e($k['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><label class="lbl">Oportunidad vinculada</label><select class="form-select" name="oportunidad_id" id="cqOp" data-sel="<?= (int)$val('oportunidad_id') ?>"><option value="">— Ninguna —</option><?php foreach ($ops as $o): ?><option value="<?= $o['id'] ?>" data-cli="<?= $o['cliente_id'] ?>"><?= e($o['codigo'] . ' · ' . $o['titulo']) ?></option><?php endforeach; ?></select><div class="help">Al enviar o aprobar, la oportunidad avanza de etapa automáticamente.</div></div>
  </div></div>
  <div class="card-ui mb-3"><div class="card-head"><div><h6>Detalle</h6><small>Servicios, equipos o licencias</small></div><button type="button" class="btn btn-soft btn-sm" id="cqAdd"><i class="bi bi-plus-lg"></i> Ítem</button></div>
    <?= $err('items') ?>
    <div class="table-responsive"><table class="table-ui cq-items"><thead><tr><th>Descripción</th><th style="width:90px">Cant.</th><th style="width:130px">P. unit. (<?= e(fmt_cfg()['simbolo']) ?>)</th><th style="width:120px" class="text-end">Importe</th><th style="width:40px"></th></tr></thead><tbody id="cqBody">
    <?php foreach ($items as $it): ?><tr><td><input class="form-control" name="item_desc[]" maxlength="255" value="<?= e($it['descripcion']) ?>" placeholder="Ej: Soporte mensual Business"></td><td><input type="number" class="form-control cq-c" name="item_cant[]" min="0.01" step="0.01" value="<?= e($it['cantidad']) ?>"></td><td><input type="number" class="form-control cq-p" name="item_precio[]" min="0" step="0.01" value="<?= e($it['precio_unit']) ?>"></td><td class="text-end fw-semibold cq-i">0.00</td><td><button type="button" class="btn btn-ghost btn-sm text-danger cq-del"><i class="bi bi-x-lg"></i></button></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
  <div class="card-ui"><div class="form-section" style="margin-top:0">Condiciones y notas</div><div class="row g-3">
    <div class="col-12"><label class="lbl">Condiciones comerciales</label><textarea class="form-control" name="condiciones" rows="4"><?= e($val('condiciones')) ?></textarea></div>
    <div class="col-12"><label class="lbl">Notas internas</label><textarea class="form-control" name="notas" rows="2"><?= e($val('notas')) ?></textarea></div></div></div>
</div>
<div class="col-xl-4"><div class="card-ui" style="position:sticky;top:84px">
  <div class="form-section" style="margin-top:0">Emisión</div><div class="row g-2 mb-3">
    <div class="col-6"><label class="lbl">Fecha</label><input type="date" class="form-control <?= $inv('fecha') ?>" name="fecha" value="<?= e($val('fecha')) ?>"><?= $err('fecha') ?></div>
    <div class="col-6"><label class="lbl">Válida hasta</label><input type="date" class="form-control <?= $inv('valida_hasta') ?>" name="valida_hasta" value="<?= e($val('valida_hasta')) ?>"><?= $err('valida_hasta') ?></div>
    <div class="col-6"><label class="lbl">Descuento %</label><input type="number" min="0" max="100" step="0.01" class="form-control" name="descuento_pct" id="cqDesc" value="<?= e($val('descuento_pct') ?: 0) ?>"></div>
    <div class="col-6"><label class="lbl"><?= e(impuesto_nombre()) ?> %</label><input type="number" min="0" max="100" step="0.01" class="form-control" name="igv_pct" id="cqIgv" value="<?= e($val('igv_pct')) ?>"></div></div>
  <div class="cq-totals"><div><span>Subtotal</span><b id="tSub"><?= e(num(0)) ?></b></div><div><span>Descuento</span><b id="tDesc"><?= e(num(0)) ?></b></div><div><span><?= e(impuesto_nombre()) ?></span><b id="tIgv"><?= e(num(0)) ?></b></div><div class="tot"><span>Total</span><b id="tTot"><?= e(money(0)) ?></b></div></div>
  <div class="d-grid gap-2 mt-3"><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i><?= $id ? 'Guardar cambios' : 'Crear cotización' ?></button><a class="btn btn-ghost" href="<?= url($id ? 'cotizaciones/ver/' . $id : 'cotizaciones') ?>">Cancelar</a></div>
</div></div></div></form>

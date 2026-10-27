<?php $pend = array_filter($rows, fn($r) => !$r['facturado']); $tot = array_sum(array_map(fn($r) => $r['monto_mensual'] * (1 + cfg('igv', 18) / 100), $pend)); $mn = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre']; ?>
<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Facturación</div><h1>Mensualidades de contrato</h1><p>Emite de una sola vez las cuotas de soporte del mes. No duplica comprobantes ya emitidos.</p></div></div>
<?php include __DIR__ . '/_tabs.php'; ?>
<form method="post" action="<?= url('facturacion/generar') ?>" class="form-ui"><?= csrf_field() ?><input type="hidden" name="periodo" value="<?= e($per) ?>">
<div class="card-ui mb-3"><div class="d-flex flex-wrap gap-3 align-items-end">
  <div><label class="lbl">Período</label><input type="month" class="form-control" value="<?= e($per) ?>" onchange="location='<?= url('facturacion/recurrente') ?>?periodo='+this.value"></div>
  <div><label class="lbl">Fecha de emisión</label><input type="date" class="form-control" name="emision" value="<?= date('Y-m-d') ?>"></div>
  <div><label class="lbl">Días de crédito</label><input type="number" class="form-control" name="dias" min="0" max="90" value="15" style="width:110px"></div>
  <div class="ms-auto text-end"><div class="small text-muted">Por emitir (<?= count($pend) ?>) · con IGV</div><b class="fs-5"><?= money($tot) ?></b></div>
  <button class="btn btn-brand" <?= $pend ? '' : 'disabled' ?>><i class="bi bi-lightning-charge-fill me-1"></i>Emitir seleccionados</button></div></div>
<div class="card-ui"><div class="table-responsive"><table class="table-ui"><thead><tr><th style="width:36px"><input type="checkbox" class="form-check-input" id="chkAll" checked></th><th>Contrato</th><th>Cliente</th><th>Plan</th><th class="text-end">Cuota (sin IGV)</th><th>Comprobante</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?php if (!$r['facturado']): ?><input type="checkbox" class="form-check-input chk" name="ids[]" value="<?= $r['id'] ?>" checked><?php endif; ?></td>
  <td class="cell-main"><b><?= e($r['codigo']) ?></b><small>hasta <?= fdate($r['fecha_fin']) ?></small></td><td><?= e($r['cliente']) ?> <small class="text-muted"><?= $r['ruc'] ? '(factura)' : '(boleta)' ?></small></td><td><?= e($r['plan']) ?></td><td class="text-end fw-semibold"><?= money($r['monto_mensual']) ?></td>
  <td><?= $r['facturado'] ? pill('Emitido ' . $r['facturado'], 'success') : pill('Pendiente de emitir', 'soft-warning') ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="empty-row">No hay contratos con vigencia en este período.</td></tr><?php endif; ?></tbody></table></div></div></form>
<script>var a=document.getElementById('chkAll');if(a)a.addEventListener('change',function(){document.querySelectorAll('.chk').forEach(function(c){c.checked=a.checked})});</script>

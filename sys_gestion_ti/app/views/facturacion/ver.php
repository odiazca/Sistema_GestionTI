<?php $emitida = $f['estado'] === 'emitida'; ?>
<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('facturacion') ?>">Facturación</a> / <?= e($f['codigo']) ?></div>
  <h1><?= e($f['codigo']) ?> <?= fac_pill($f['est']) ?></h1><p><?= e(FAC_TIPOS[$f['tipo']]) ?> · <a href="<?= url('clientes/ver/' . $f['cliente_id']) ?>"><?= e($f['cliente']) ?></a><?= $f['contrato'] ? ' · contrato ' . e($f['contrato']) : '' ?><?= $f['orden'] ? ' · orden ' . e($f['orden']) : '' ?><?= $f['cotizacion'] ? ' · cotización ' . e($f['cotizacion']) : '' ?></p></div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($emitida): ?><button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#mdlPago"><i class="bi bi-cash-coin me-1"></i>Registrar pago</button><?php endif; ?>
    <a class="btn btn-ghost" target="_blank" href="<?= url('facturacion/imprimir/' . $f['id']) ?>"><i class="bi bi-printer me-1"></i>Imprimir / PDF</a>
    <?php if ($emitida && (float)$f['pagado'] == 0): ?><a class="btn btn-ghost" href="<?= url('facturacion/editar/' . $f['id']) ?>"><i class="bi bi-pencil me-1"></i>Editar</a><?php endif; ?>
    <?php if ($f['estado'] !== 'anulada' && (float)$f['pagado'] == 0): ?><button class="btn btn-danger-soft" data-bs-toggle="modal" data-bs-target="#mdlAnular"><i class="bi bi-x-octagon me-1"></i>Anular</button><?php endif; ?>
  </div></div>
<div class="row g-3"><div class="col-xl-8"><div class="card-ui mb-3">
  <div class="table-responsive"><table class="table-ui"><thead><tr><th>Descripción</th><th class="text-end">Cant.</th><th class="text-end">P. unit.</th><th class="text-end">Importe</th></tr></thead><tbody>
  <?php foreach ($items as $it): ?><tr><td><?= e($it['descripcion']) ?></td><td class="text-end"><?= rtrim(rtrim(number_format($it['cantidad'], 2), '0'), '.') ?></td><td class="text-end"><?= money($it['precio_unit']) ?></td><td class="text-end fw-semibold"><?= money($it['cantidad'] * $it['precio_unit']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="cq-totals ms-auto mt-3" style="max-width:300px"><div><span>Subtotal</span><b><?= money($f['subtotal']) ?></b></div><div><span>IGV (<?= (float)$f['igv_pct'] ?>%)</span><b><?= money($f['igv']) ?></b></div><div class="tot"><span>Total</span><b><?= money($f['total']) ?></b></div></div>
  <?php if ($f['notas']): ?><div class="mini-title mt-3">Condiciones</div><p class="small mb-0"><?= nl2br(e($f['notas'])) ?></p><?php endif; ?></div>
  <div class="card-ui"><div class="card-head"><div><h6>Pagos registrados</h6><small><?= count($pagos) ?> pago(s)</small></div></div>
  <?php foreach ($pagos as $p): ?><div class="alert-row"><div><b><?= money($p['monto']) ?> · <?= e(FAC_METODOS[$p['metodo']] ?? $p['metodo']) ?></b><small><?= fdate($p['fecha']) ?><?= $p['referencia'] ? ' · ref. ' . e($p['referencia']) : '' ?><?= $p['quien'] ? ' · ' . e($p['quien']) : '' ?></small></div>
    <form method="post" action="<?= url('facturacion/quitarpago/' . $p['id']) ?>" onsubmit="return confirm('¿Eliminar este pago?')"><?= csrf_field() ?><button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash3"></i></button></form></div>
  <?php endforeach; if (!$pagos): ?><p class="text-muted small mb-0">Sin pagos registrados.</p><?php endif; ?></div></div>
<div class="col-xl-4"><div class="card-ui mb-3"><div class="row g-2 text-center"><div class="col-6"><div class="stat-mini d-block"><b><?= money($f['pagado']) ?></b><span>Pagado</span></div></div><div class="col-6"><div class="stat-mini d-block"><b class="<?= $f['est'] === 'vencida' ? 'text-danger' : '' ?>"><?= $f['estado'] === 'anulada' ? '—' : money($f['saldo']) ?></b><span>Saldo</span></div></div></div>
  <div class="info-list mt-3"><div class="info-item"><span>Emisión</span><b><?= fdate($f['fecha_emision']) ?></b></div><div class="info-item"><span>Vencimiento</span><b class="<?= $f['est'] === 'vencida' ? 'text-danger' : '' ?>"><?= fdate($f['fecha_vencimiento']) ?></b></div>
    <?php if ($f['est'] === 'vencida'): ?><div class="info-item"><span>Días de atraso</span><b class="text-danger"><?= (int)((time() - strtotime($f['fecha_vencimiento'])) / 86400) ?></b></div><?php endif; ?>
    <div class="info-item"><span>Concepto</span><b><?= e(FAC_CONCEPTOS[$f['concepto']] ?? '') ?></b></div><?php if ($f['periodo']): ?><div class="info-item"><span>Período</span><b><?= e($f['periodo']) ?></b></div><?php endif; ?>
    <div class="info-item"><span>Emitido por</span><b><?= e($f['autor'] ?: '—') ?></b></div></div>
  <?php if ($f['estado'] === 'anulada'): ?><div class="alert alert-light border small mt-3 mb-0"><b>Anulado:</b> <?= e($f['motivo_anulacion']) ?></div><?php endif; ?></div></div></div>

<div class="modal fade" id="mdlPago" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 form-ui" style="border-radius:20px"><form method="post" action="<?= url('facturacion/pago/' . $f['id']) ?>"><?= csrf_field() ?>
  <div class="modal-header border-0 px-4 pt-4"><div><h5 class="modal-title fw-bold">Registrar pago</h5><div class="small text-muted"><?= e($f['codigo']) ?> · saldo <?= money($f['saldo']) ?></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body px-4 pt-0"><div class="row g-2">
    <div class="col-6"><label class="lbl">Monto (S/)</label><input type="number" step="0.01" min="0.01" max="<?= e($f['saldo']) ?>" class="form-control" name="monto" value="<?= e($f['saldo']) ?>" required></div>
    <div class="col-6"><label class="lbl">Fecha</label><input type="date" class="form-control" name="fecha" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required></div>
    <div class="col-6"><label class="lbl">Método</label><select class="form-select" name="metodo"><?php foreach (FAC_METODOS as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="col-6"><label class="lbl">N° operación</label><input class="form-control" name="referencia" maxlength="80"></div>
    <div class="col-12"><label class="lbl">Notas</label><input class="form-control" name="notas" maxlength="200"></div></div></div>
  <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Guardar pago</button></div></form></div></div></div>
<div class="modal fade" id="mdlAnular" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 form-ui" style="border-radius:20px"><form method="post" action="<?= url('facturacion/anular/' . $f['id']) ?>"><?= csrf_field() ?>
  <div class="modal-header border-0 px-4 pt-4"><h5 class="modal-title fw-bold">Anular comprobante</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body px-4 pt-0"><label class="lbl">Motivo <span class="req">*</span></label><input class="form-control" name="motivo" maxlength="200" required placeholder="Ej: error en el monto o en el cliente"><div class="help">Un comprobante anulado no se puede reactivar; deberás emitir uno nuevo.</div></div>
  <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger">Anular</button></div></form></div></div></div>

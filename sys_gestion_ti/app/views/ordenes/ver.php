<?php
$abierta = in_array($o['estado'], ['pendiente', 'en_curso']);
$subRep = 0; foreach ($items as $it) $subRep += $it['cantidad'] * $it['precio_unit'];
$mano = $o['horas'] * $o['tarifa_hora']; $total = $mano + $subRep;
?>
<div class="page-head">
  <div class="tk-head">
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('ordenes') ?>">Órdenes de servicio</a> / <?= e($o['codigo']) ?></div>
    <div class="d-flex align-items-center gap-2 flex-wrap mb-1"><span class="tk-code-lg"><?= e($o['codigo']) ?></span><?= pill($tipos[$o['tipo']], 'soft-primary') ?><?= pill($est[$o['estado']], $estCls[$o['estado']]) ?></div>
    <h1><?= e($o['cliente']) ?></h1>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($o['estado'] === 'pendiente'): ?>
      <form method="post" action="<?= url('ordenes/estado/' . $o['id']) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="iniciar"><button class="btn btn-brand"><i class="bi bi-play-fill me-1"></i>Iniciar trabajo</button></form>
    <?php elseif ($o['estado'] === 'completada'): ?>
      <?php if (in_array(Auth::user()['rol'], ['Administrador', 'Supervisor'], true)): $fo = null; try { $st_ = Database::conn()->prepare("SELECT id, codigo FROM fac_comprobantes WHERE orden_id=? AND estado<>'anulada' LIMIT 1"); $st_->execute([$o['id']]); $fo = $st_->fetch(); } catch (Throwable $e) {} ?>
        <?php if ($fo): ?><a class="btn btn-soft" href="<?= url('facturacion/ver/' . $fo['id']) ?>"><i class="bi bi-receipt me-1"></i><?= e($fo['codigo']) ?></a><?php else: ?><a class="btn btn-brand" href="<?= url('facturacion/crear?orden=' . $o['id']) ?>"><i class="bi bi-receipt me-1"></i>Facturar</a><?php endif; ?>
      <?php endif; ?>
      <form method="post" action="<?= url('ordenes/estado/' . $o['id']) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="reabrir"><button class="btn btn-ghost"><i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir</button></form>
    <?php endif; ?>
    <?php if ($abierta): ?>
      <form method="post" action="<?= url('ordenes/estado/' . $o['id']) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="cancelar"><button class="btn btn-ghost text-danger"><i class="bi bi-x-circle me-1"></i>Cancelar</button></form>
    <?php endif; ?>
    <a href="<?= url('ordenes/editar/' . $o['id']) ?>" class="btn btn-ghost"><i class="bi bi-pencil me-1"></i>Editar</a>
    <button type="button" class="btn btn-ghost" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card-ui mb-3">
      <div class="card-head"><div><h6>Trabajo a realizar</h6><small>Programada: <?= fdate($o['fecha_programada'], 'd/m/Y H:i') ?><?= $o['fecha_inicio'] ? ' · Inicio: ' . fdate($o['fecha_inicio'], 'd/m H:i') : '' ?><?= $o['fecha_fin'] ? ' · Fin: ' . fdate($o['fecha_fin'], 'd/m H:i') : '' ?></small></div></div>
      <div class="desc-box"><?= e($o['descripcion_trabajo']) ?></div>
    </div>

    <div class="card-ui mb-3">
      <div class="card-head"><div><h6>Informe técnico</h6><small>Diagnóstico, solución y tiempo invertido</small></div></div>
      <form method="post" action="<?= url('ordenes/' . ($abierta ? 'estado' : 'informe') . '/' . $o['id']) ?>" class="form-ui">
        <?= csrf_field() ?>
        <?php if ($abierta): ?><input type="hidden" name="accion" value="completar"><?php endif; ?>
        <div class="row g-3">
          <div class="col-12"><label class="lbl">Diagnóstico</label><textarea class="form-control" rows="3" name="diagnostico" placeholder="Causa raíz identificada…"><?= e($o['diagnostico']) ?></textarea></div>
          <div class="col-12"><label class="lbl">Solución aplicada <?= $abierta ? '<span class="req">*</span>' : '' ?></label><textarea class="form-control" rows="3" name="solucion" placeholder="Acciones realizadas y resultado…"><?= e($o['solucion']) ?></textarea></div>
          <div class="col-md-4"><label class="lbl">Horas trabajadas <?= $abierta ? '<span class="req">*</span>' : '' ?></label><input type="number" step="0.25" min="0" class="form-control" name="horas" value="<?= e($o['horas'] > 0 ? $o['horas'] : '') ?>" placeholder="0.0"></div>
          <div class="col-md-8 d-flex align-items-end gap-4 flex-wrap">
            <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="conformidad" id="conf" <?= $o['conformidad'] ? 'checked' : '' ?>><label class="form-check-label small fw-semibold" for="conf">Conformidad del cliente</label></div>
            <?php if ($abierta && $o['ticket'] && in_array($o['ticket_estado'], ['abierto', 'en_proceso', 'pendiente'])): ?>
            <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="resolver_ticket" id="rt" checked><label class="form-check-label small fw-semibold" for="rt">Resolver ticket <?= e($o['ticket']) ?></label></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="text-end mt-3">
          <button class="btn <?= $abierta ? 'btn-brand' : 'btn-soft' ?>" style="<?= $abierta ? 'background:linear-gradient(135deg,#059669,#10b981)' : '' ?>"><i class="bi bi-check2-circle me-1"></i><?= $abierta ? 'Completar orden' : 'Guardar informe' ?></button>
        </div>
      </form>
    </div>

    <div class="card-ui">
      <div class="card-head"><div><h6>Repuestos y materiales</h6><small>Se descuentan automáticamente del almacén</small></div></div>
      <div class="table-responsive"><table class="table-ui">
        <thead><tr><th>Repuesto</th><th class="text-end">Cant.</th><th class="text-end">P. unit.</th><th class="text-end">Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr><td class="cell-main"><b><?= e($it['nombre']) ?></b><small><?= e($it['codigo']) ?></small></td><td class="text-end"><?= (int)$it['cantidad'] ?></td>
            <td class="text-end"><?= e(money($it['precio_unit'])) ?></td><td class="text-end fw-bold"><?= e(money($it['cantidad'] * $it['precio_unit'])) ?></td>
            <td><form method="post" action="<?= url('ordenes/quitar/' . $o['id'] . '/' . $it['id']) ?>"><?= csrf_field() ?><button class="act-btn danger" title="Quitar y devolver al almacén"><i class="bi bi-x-lg"></i></button></form></td></tr>
        <?php endforeach; if (!$items): ?><tr><td colspan="5" class="empty-row" style="padding:1.2rem">No se han usado repuestos.</td></tr><?php endif; ?>
        </tbody></table></div>
      <?php if ($o['estado'] !== 'cancelada'): ?>
      <form method="post" action="<?= url('ordenes/repuesto/' . $o['id']) ?>" class="form-ui d-flex gap-2 flex-wrap mt-3 pt-3" style="border-top:1px solid var(--line)">
        <?= csrf_field() ?>
        <select class="form-select" name="repuesto_id" style="flex:1;min-width:220px" required><option value="">— Agregar repuesto —</option>
          <?php foreach ($repuestos as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['nombre'] . ' · stock ' . $r['stock'] . ' · ' . money($r['precio'])) ?></option><?php endforeach; ?></select>
        <input type="number" class="form-control" name="cantidad" min="1" value="1" style="width:90px" required>
        <button class="btn btn-soft"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card-ui mb-3">
      <div class="card-head"><div><h6>Resumen de costos</h6></div></div>
      <div class="info-list">
        <div class="info-item"><span>Mano de obra (<?= (float)$o['horas'] ?> h × <?= e(money($o['tarifa_hora'])) ?>)</span><b><?= e(money($mano)) ?></b></div>
        <div class="info-item"><span>Repuestos</span><b><?= e(money($subRep)) ?></b></div>
        <div class="info-item" style="border-top:1px dashed var(--line);padding-top:.8rem"><span class="fw-bold" style="color:var(--ink)">Total (sin IGV)</span><b style="font-size:1.35rem;color:var(--brand)"><?= e(money($total)) ?></b></div>
      </div>
      <?php if ($o['conformidad']): ?><div class="mt-3"><?= pill('Conformidad del cliente registrada', 'success') ?></div><?php endif; ?>
    </div>
    <div class="card-ui mb-3">
      <div class="card-head"><div><h6>Firma del cliente</h6></div></div>
      <?php if ($o['firma']): ?>
        <div class="firma-box"><img src="<?= e($o['firma']) ?>" alt="Firma"></div>
        <div class="small mt-2"><b><?= e($o['firma_nombre']) ?></b><br><span class="text-muted"><?= fdate($o['firma_fecha'], 'd/m/Y H:i') ?></span></div>
        <?php if (in_array(Auth::user()['rol'], ['Administrador', 'Supervisor'], true)): ?><form method="post" action="<?= url('ordenes/quitarfirma/' . $o['id']) ?>" class="mt-2" onsubmit="return confirm('¿Eliminar la firma registrada?')"><?= csrf_field() ?><button class="btn btn-sm btn-ghost text-danger"><i class="bi bi-trash3 me-1"></i>Eliminar firma</button></form><?php endif; ?>
      <?php elseif ($o['estado'] !== 'cancelada'): ?>
        <p class="small text-muted mb-2">Pide al cliente que firme con el dedo o el mouse al terminar el trabajo.</p>
        <button class="btn btn-brand w-100" data-bs-toggle="modal" data-bs-target="#mFirma"><i class="bi bi-pen me-1"></i>Capturar firma</button>
      <?php else: ?><p class="small text-muted mb-0">Orden cancelada.</p><?php endif; ?>
    </div>
    <div class="card-ui">
      <div class="card-head"><div><h6>Detalles</h6></div></div>
      <div class="info-list">
        <div class="info-item"><span>Cliente</span><b><a class="link-brand" href="<?= url('clientes/ver/' . $o['cliente_id']) ?>"><?= e($o['cliente']) ?></a></b></div>
        <div class="info-item"><span>Contacto</span><b><?= e($o['contacto'] ?: '—') ?><?= $o['cli_tel'] ? '<br><small class="text-muted fw-normal">' . e($o['cli_tel']) . '</small>' : '' ?></b></div>
        <div class="info-item"><span>Técnico</span><b><?= $o['tecnico'] ? e($o['tecnico']) : 'Sin asignar' ?></b></div>
        <div class="info-item"><span>Ticket</span><b><?= $o['ticket'] ? '<a class="link-brand" href="' . url('tickets/ver/' . $o['ticket_id']) . '">' . e($o['ticket']) . '</a>' : '—' ?></b></div>
        <div class="info-item"><span>Equipo</span><b><?= $o['act_codigo'] ? e($o['act_codigo'] . ' · ' . $o['act_tipo']) . '<br><small class="text-muted fw-normal">' . e(trim($o['act_marca'] . ' ' . $o['act_modelo'])) . '</small>' : '—' ?></b></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="mFirma" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="post" action="<?= url('ordenes/firmar/' . $o['id']) ?>" class="modal-content form-ui" id="fFirma"><?= csrf_field() ?>
  <input type="hidden" name="firma" id="firmaData">
  <div class="modal-header"><h5 class="modal-title">Firma de conformidad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label">Nombre de quien firma</label><input class="form-control mb-3" name="firma_nombre" required maxlength="100" value="<?= e($o['contacto'] ?? '') ?>">
    <div class="firma-pad"><canvas id="firmaCanvas"></canvas><span class="firma-hint">Firme aquí</span></div>
    <div class="d-flex justify-content-between mt-2"><small class="text-muted">Al firmar confirma que el servicio fue recibido a conformidad.</small><button type="button" class="btn btn-sm btn-ghost" id="firmaLimpiar"><i class="bi bi-eraser me-1"></i>Limpiar</button></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Guardar firma</button></div></form></div></div>
<?php $scripts = ($scripts ?? '') . '<script src="' . asset('js/firma.js') . '"></script>'; ?>

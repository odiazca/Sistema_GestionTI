<?php
[$sc, $st, $stLong] = sla_info($t);
$abierto = in_array($t['estado'], ['abierto', 'en_proceso', 'pendiente']);
$ini = strtotime($t['created_at']); $lim = strtotime($t['fecha_limite']);
$pct = $abierto ? min(100, max(0, round((time() - $ini) * 100 / max(1, $lim - $ini)))) : 100;
$yo = Auth::user();
?>
<div class="page-head">
  <div class="tk-head">
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('tickets') ?>">Tickets</a> / <?= e($t['codigo']) ?></div>
    <div class="d-flex align-items-center gap-2 flex-wrap mb-1"><span class="tk-code-lg"><?= e($t['codigo']) ?></span><?= prio_pill($t['prioridad']) ?><?= estado_pill($t['estado']) ?></div>
    <h1><?= e($t['asunto']) ?></h1>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($abierto): ?>
      <?php if ((int)$t['tecnico_id'] !== (int)$yo['id']): ?>
      <form method="post" action="<?= url('tickets/actualizar/' . $t['id']) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="tomar"><button class="btn btn-ghost"><i class="bi bi-person-check me-1"></i>Tomar ticket</button></form>
      <?php endif; ?>
      <form method="post" action="<?= url('tickets/actualizar/' . $t['id']) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="resolver"><button class="btn btn-brand" style="background:linear-gradient(135deg,#059669,#10b981)"><i class="bi bi-check2-circle me-1"></i>Marcar resuelto</button></form>
    <?php elseif ($t['estado'] === 'resuelto'): ?>
      <form method="post" action="<?= url('tickets/actualizar/' . $t['id']) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="cerrar"><button class="btn btn-ghost"><i class="bi bi-lock me-1"></i>Cerrar ticket</button></form>
      <form method="post" action="<?= url('tickets/actualizar/' . $t['id']) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="reabrir"><button class="btn btn-ghost"><i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir</button></form>
    <?php else: ?>
      <form method="post" action="<?= url('tickets/actualizar/' . $t['id']) ?>"><?= csrf_field() ?><input type="hidden" name="accion" value="reabrir"><button class="btn btn-ghost"><i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir</button></form>
    <?php endif; ?>
    <a href="<?= url('tickets/editar/' . $t['id']) ?>" class="btn btn-ghost"><i class="bi bi-pencil me-1"></i>Editar</a>
    <?php if (in_array($yo['rol'], ['Administrador', 'Supervisor'])): ?>
      <button type="button" class="btn btn-ghost text-danger" data-delete="<?= url('tickets/eliminar/' . $t['id']) ?>" data-name="<?= e($t['codigo']) ?>"><i class="bi bi-trash3"></i></button>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card-ui mb-3">
      <div class="card-head"><div><h6>Descripción</h6><small>Reportado por <?= e($t['contacto'] ?: $t['cliente']) ?> vía <?= e(CANALES[$t['canal']] ?? $t['canal']) ?> · <?= fdate($t['created_at'], 'd/m/Y H:i') ?></small></div></div>
      <div class="desc-box"><?= $t['descripcion'] ? e($t['descripcion']) : '<span class="text-muted">Sin descripción adicional.</span>' ?></div>
    </div>

    <div class="card-ui">
      <div class="card-head"><div><h6>Seguimiento</h6><small>Comentarios y registro de cambios</small></div></div>
      <?php foreach ($coments as $m):
        if ((int)$m['interno'] === 2): ?>
          <div class="sys-event"><i class="bi bi-lightning-charge-fill"></i><span><b><?= e($m['autor'] ?? 'Sistema') ?></b> <?= $m['comentario'] ?> · <?= e(hace($m['created_at'])) ?></span></div>
      <?php else: ?>
          <div class="comment <?= $m['interno'] ? 'internal' : '' ?>">
            <?= avatar_html($m['autor'] ?? 'Sistema', $m['color'] ?? '#64748b') ?>
            <div class="bubble">
              <div class="meta"><b><?= e($m['autor'] ?? 'Sistema') ?></b><span><?= e(hace($m['created_at'])) ?></span><?= $m['interno'] ? '<span class="pill pill-warning">Nota interna</span>' : '' ?></div>
              <div style="white-space:pre-line"><?= e($m['comentario']) ?></div>
            </div>
          </div>
      <?php endif; endforeach; if (!$coments) echo '<p class="text-muted small">Aún no hay actividad.</p>'; ?>

      <form method="post" action="<?= url('tickets/comentar/' . $t['id']) ?>" class="form-ui mt-3 pt-3" style="border-top:1px solid var(--line)">
        <?= csrf_field() ?>
        <div class="d-flex gap-2">
          <?= avatar_html($yo['nombre'], $yo['color']) ?>
          <div class="flex-grow-1">
            <textarea class="form-control" rows="3" name="comentario" placeholder="Escribe una respuesta o una nota…" required></textarea>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" name="interno" id="interno" value="1"><label class="form-check-label small" for="interno">Nota interna (no visible para el cliente)</label></div>
              <button class="btn btn-brand btn-sm px-3"><i class="bi bi-send me-1"></i>Enviar</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card-ui mb-3">
      <div class="sla-box <?= $sc ?>">
        <div class="top"><span><?= $abierto ? 'Tiempo restante de SLA' : 'Resultado de SLA' ?></span><i class="bi bi-alarm"></i></div>
        <div class="time"><?= e($st) ?></div>
        <div class="progress-ui my-2"><div class="bar" style="width:<?= $pct ?>%;background:<?= $sc === 'bad' ? '#ef4444' : ($sc === 'warn' ? '#f59e0b' : '#10b981') ?>"></div></div>
        <small class="text-muted"><?= e($stLong) ?> · límite <?= fdate($t['fecha_limite'], 'd/m H:i') ?></small>
      </div>

      <form method="post" action="<?= url('tickets/actualizar/' . $t['id']) ?>" class="form-ui">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="lbl">Estado</label>
          <select class="form-select" name="estado"><?php foreach (ESTADOS_TK as $k => $v): ?><option value="<?= $k ?>" <?= $t['estado'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="lbl">Prioridad</label>
          <select class="form-select" name="prioridad"><?php foreach (PRIORIDADES as $k => $v): ?><option value="<?= $k ?>" <?= $t['prioridad'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="lbl">Técnico asignado</label>
          <select class="form-select" name="tecnico_id"><option value="0">Sin asignar</option>
            <?php foreach ($tecnicos as $u): ?><option value="<?= $u['id'] ?>" <?= (int)$t['tecnico_id'] === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['nombre']) ?></option><?php endforeach; ?></select></div>
        <button class="btn btn-soft w-100" style="height:44px"><i class="bi bi-check2 me-1"></i>Aplicar cambios</button>
      </form>
    </div>

    <div class="card-ui">
      <div class="card-head"><div><h6>Detalles</h6></div></div>
      <div class="info-list">
        <div class="info-item"><span>Cliente</span><b><a href="<?= url('clientes/ver/' . $t['cliente_id']) ?>" class="link-brand"><?= e($t['cliente']) ?></a></b></div>
        <div class="info-item"><span>Contacto</span><b><?= e($t['contacto'] ?: '—') ?><?= $t['cli_tel'] ? '<br><small class="text-muted fw-normal">' . e($t['cli_tel']) . '</small>' : '' ?></b></div>
        <div class="info-item"><span>Categoría</span><b><i class="bi <?= e($t['cat_icono']) ?> me-1"></i><?= e($t['categoria']) ?></b></div>
        <div class="info-item"><span>Equipo</span><b><?= $t['act_codigo'] ? e($t['act_codigo'] . ' · ' . $t['act_tipo']) . '<br><small class="text-muted fw-normal">' . e(trim($t['act_marca'] . ' ' . $t['act_modelo'])) . '</small>' : '—' ?></b></div>
        <div class="info-item"><span>Canal</span><b><?= e(CANALES[$t['canal']] ?? '') ?></b></div>
        <div class="info-item"><span>Creado por</span><b><?= e($t['creador'] ?: '—') ?></b></div>
        <div class="info-item"><span>Creado</span><b><?= fdate($t['created_at'], 'd/m/Y H:i') ?></b></div>
        <?php if ($t['resuelto_at']): ?><div class="info-item"><span>Resuelto</span><b><?= fdate($t['resuelto_at'], 'd/m/Y H:i') ?></b></div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <form class="modal-content p-4 text-center" method="post"><?= csrf_field() ?>
      <div class="empty-icon" style="width:64px;height:64px;font-size:1.7rem;background:linear-gradient(135deg,#ef4444,#f97316)"><i class="bi bi-trash3"></i></div>
      <h5 class="fw-bold">¿Eliminar ticket?</h5>
      <p class="text-muted small">Se eliminará <b class="del-name"></b> con todo su historial.</p>
      <div class="d-flex gap-2"><button type="button" class="btn btn-ghost flex-fill" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger flex-fill" style="border-radius:12px;font-weight:600">Eliminar</button></div>
    </form>
  </div>
</div>

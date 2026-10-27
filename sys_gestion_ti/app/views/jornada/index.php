<?php $mapa = fn($d) => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($d); $hoy = date('Y-m-d'); $saludo = (int)date('G') < 12 ? 'Buenos días' : ((int)date('G') < 19 ? 'Buenas tardes' : 'Buenas noches'); ?>
<div class="page-head"><div><div class="crumbs">Mesa de Ayuda</div><h1>Mi jornada</h1><p><?= $saludo ?>, <?= e(explode(' ', Auth::user()['nombres'] ?? '')[0]) ?>. <?= date('d/m/Y') ?></p></div>
  <div class="d-flex gap-2"><button id="btnInstalar" class="btn btn-brand d-none"><i class="bi bi-phone me-1"></i>Instalar app</button>
  <?php if (Auth::user()['rol'] !== 'Técnico'): ?><a class="btn btn-ghost" href="<?= url('jornada' . ($todos ? '' : '?todos=1')) ?>"><?= $todos ? 'Solo las mías' : 'Ver todo el equipo' ?></a><?php endif; ?></div></div>

<h6 class="jr-h"><i class="bi bi-geo-alt-fill"></i>Visitas <span><?= count($visitas) ?></span></h6>
<div class="row g-3 mb-4">
<?php foreach ($visitas as $v): $dia = substr($v['fecha_programada'], 0, 10); ?>
  <div class="col-md-6 col-xl-4"><div class="card-ui jr-card <?= $v['estado'] === 'en_curso' ? 'live' : '' ?>">
    <div class="d-flex justify-content-between align-items-start"><div><b class="d-block"><?= e($v['cliente']) ?></b><small class="text-muted"><?= $dia === $hoy ? 'Hoy' : ($dia < $hoy ? 'Atrasada' : ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'][(int)date('w', strtotime($v['fecha_programada']))] . ' ' . fdate($v['fecha_programada'], 'd/m')) ?> · <?= fdate($v['fecha_programada'], 'H:i') ?> · <?= e(ucfirst($v['tipo'])) ?></small></div>
      <?= $v['tipo'] === 'emergencia' ? '<span class="pill pill-danger">Emergencia</span>' : ($v['estado'] === 'en_curso' ? '<span class="pill pill-info">En curso</span>' : '') ?></div>
    <?php if ($v['notas']): ?><p class="small mt-2 mb-1"><?= e($v['notas']) ?></p><?php endif; ?>
    <?php if ($v['ticket']): ?><small class="text-muted">Ticket <?= e($v['ticket']) ?></small><?php endif; ?>
    <div class="jr-actions">
      <?php if ($v['direccion']): ?><a class="btn btn-soft" target="_blank" rel="noopener" href="<?= e($mapa($v['direccion'])) ?>"><i class="bi bi-signpost-2"></i> Ir</a><?php endif; ?>
      <?php if ($v['telefono']): ?><a class="btn btn-soft" href="tel:<?= e(preg_replace('/[^\d+]/', '', $v['telefono'])) ?>"><i class="bi bi-telephone"></i> Llamar</a><?php endif; ?>
      <form method="post" action="<?= url('visitas/estado/' . $v['id']) ?>"><?= csrf_field() ?><input type="hidden" name="volver" value="jornada"><input type="hidden" name="estado" value="<?= $v['estado'] === 'programada' ? 'en_curso' : 'completada' ?>">
        <button class="btn btn-brand"><i class="bi <?= $v['estado'] === 'programada' ? 'bi-box-arrow-in-right' : 'bi-check2-circle' ?>"></i> <?= $v['estado'] === 'programada' ? 'Llegué' : 'Terminar' ?></button></form>
    </div>
  </div></div>
<?php endforeach; if (!$visitas): ?><div class="col-12"><div class="card-ui p-4 text-center text-muted"><i class="bi bi-emoji-smile fs-2 d-block mb-1"></i>No tienes visitas pendientes en los próximos días.</div></div><?php endif; ?>
</div>

<h6 class="jr-h"><i class="bi bi-clipboard2-check-fill"></i>Órdenes de servicio <span><?= count($ordenes) ?></span></h6>
<div class="row g-3 mb-4">
<?php foreach ($ordenes as $o): ?>
  <div class="col-md-6 col-xl-4"><div class="card-ui jr-card <?= $o['estado'] === 'en_curso' ? 'live' : '' ?>">
    <div class="d-flex justify-content-between"><div><b class="d-block"><?= e($o['codigo']) ?> · <?= e($o['cliente']) ?></b><small class="text-muted"><?= e(ucfirst($o['tipo'])) ?><?= $o['fecha_programada'] ? ' · ' . fdate($o['fecha_programada'], 'd/m H:i') : '' ?></small></div><?= $o['estado'] === 'en_curso' ? '<span class="pill pill-info">En curso</span>' : '<span class="pill pill-warning">Pendiente</span>' ?></div>
    <p class="small mt-2 mb-1 text-muted"><?= e(mb_strimwidth((string)$o['descripcion_trabajo'], 0, 110, '…')) ?></p>
    <div class="jr-actions">
      <?php if ($o['direccion']): ?><a class="btn btn-soft" target="_blank" rel="noopener" href="<?= e($mapa($o['direccion'])) ?>"><i class="bi bi-signpost-2"></i> Ir</a><?php endif; ?>
      <a class="btn btn-brand" href="<?= url('ordenes/ver/' . $o['id']) ?>"><i class="bi bi-pen"></i> Abrir / firmar</a>
    </div>
  </div></div>
<?php endforeach; if (!$ordenes): ?><div class="col-12"><div class="card-ui p-4 text-center text-muted">Sin órdenes pendientes.</div></div><?php endif; ?>
</div>

<h6 class="jr-h"><i class="bi bi-ticket-perforated-fill"></i>Tickets asignados <span><?= count($tickets) ?></span></h6>
<div class="card-ui">
<?php foreach ($tickets as $t): ?>
  <a class="jr-tk" href="<?= url('tickets/ver/' . $t['id']) ?>"><span class="tk-code"><?= e($t['codigo']) ?></span><span class="flex-grow-1"><b><?= e($t['asunto']) ?></b><small class="d-block text-muted"><?= e($t['cliente']) ?></small></span><?= prio_pill($t['prioridad']) ?></a>
<?php endforeach; if (!$tickets): ?><div class="p-4 text-center text-muted">No tienes tickets abiertos.</div><?php endif; ?>
</div>

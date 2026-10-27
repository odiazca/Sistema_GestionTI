<?php
$tc = VisitasController::TIPO_COLOR; $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$offset = ((int)date('N', $ini)) - 1; // lunes = 0
$prev = date('Y-m', strtotime('-1 month', $ini)); $next = date('Y-m', strtotime('+1 month', $ini));
$hoyD = date('Y-m') === $mes ? (int)date('j') : 0;
$qs = fn($m) => '?mes=' . $m . ($tec !== '' ? '&tecnico=' . urlencode($tec) : '');
$celdas = $offset + $dias; $filas = (int)ceil($celdas / 7);
?>
<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Mesa de Ayuda</div>
    <h1>Visitas técnicas</h1>
    <p>Agenda y calendario de visitas a clientes.</p>
  </div>
  <a href="<?= url('visitas/crear') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Agendar visita</a>
</div>

<div class="row g-3 mb-3">
  <?php foreach ([['Programadas', $stats['prog'], 'bi-calendar-event', '#4f46e5'], ['Para hoy', $stats['hoy'], 'bi-geo-alt-fill', '#0ea5e9'], ['Emergencias activas', $stats['emerg'], 'bi-exclamation-triangle-fill', '#ef4444'], ['Completadas (30 días)', $stats['comp'], 'bi-check-circle-fill', '#10b981']] as $s): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:<?= $s[3] ?>1f;color:<?= $s[3] ?>"><i class="bi <?= $s[2] ?>"></i></span><div><b><?= (int)$s[1] ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card-ui">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
          <a class="act-btn" href="<?= e($qs($prev)) ?>"><i class="bi bi-chevron-left"></i></a>
          <h5 class="m-0 fw-bold" style="min-width:170px;text-align:center"><?= $meses[(int)date('n', $ini)] . ' ' . date('Y', $ini) ?></h5>
          <a class="act-btn" href="<?= e($qs($next)) ?>"><i class="bi bi-chevron-right"></i></a>
          <a class="btn btn-soft btn-sm ms-1" href="<?= e($qs(date('Y-m'))) ?>">Hoy</a>
        </div>
        <form method="get" class="d-flex gap-2"><input type="hidden" name="mes" value="<?= e($mes) ?>">
          <select name="tecnico" class="select-ui" onchange="this.form.submit()"><option value="">Todos los técnicos</option>
            <?php foreach ($tecnicos as $t): ?><option value="<?= $t['id'] ?>" <?= $tec === (string)$t['id'] ? 'selected' : '' ?>><?= e($t['nombre']) ?></option><?php endforeach; ?></select></form>
      </div>
      <div class="cal">
        <?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d): ?><div class="cal-h"><?= $d ?></div><?php endforeach; ?>
        <?php for ($i = 0; $i < $filas * 7; $i++):
            $d = $i - $offset + 1; $valid = $d >= 1 && $d <= $dias; ?>
          <div class="cal-c <?= !$valid ? 'off' : '' ?> <?= $valid && $d === $hoyD ? 'today' : '' ?>">
            <?php if ($valid): ?>
              <span class="cal-n"><?= $d ?></span>
              <?php foreach (array_slice($porDia[$d] ?? [], 0, 3) as $v): ?>
                <a href="<?= url('visitas/editar/' . $v['id']) ?>" class="cal-ev <?= in_array($v['estado'], ['completada', 'cancelada']) ? 'done' : '' ?>" style="--c:<?= $tc[$v['tipo']] ?>" title="<?= e($v['cliente'] . ' · ' . ($v['tecnico'] ?? 'Sin técnico')) ?>">
                  <b><?= date('H:i', strtotime($v['fecha_programada'])) ?></b> <?= e(mb_strimwidth($v['cliente'], 0, 14, '…')) ?></a>
              <?php endforeach; if (count($porDia[$d] ?? []) > 3): ?><span class="cal-more">+<?= count($porDia[$d]) - 3 ?> más</span><?php endif; ?>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      </div>
      <div class="legend-inline mt-3 flex-wrap"><?php foreach (VisitasController::TIPOS as $k => $v): ?><span><i style="background:<?= $tc[$k] ?>"></i><?= $v ?></span><?php endforeach; ?></div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6>Próximas visitas</h6><small>Programadas y en curso</small></div></div>
      <?php foreach ($prox as $v): ?>
      <div class="visit-item" style="--c:<?= $tc[$v['tipo']] ?>">
        <div class="vdate"><b><?= date('d', strtotime($v['fecha_programada'])) ?></b><span><?= date('M', strtotime($v['fecha_programada'])) ?></span></div>
        <div class="flex-grow-1">
          <b><?= e($v['cliente']) ?></b>
          <small><i class="bi bi-clock"></i> <?= date('H:i', strtotime($v['fecha_programada'])) ?> · <?= e(VisitasController::TIPOS[$v['tipo']]) ?> · <?= e($v['tecnico'] ?? 'Sin técnico') ?></small>
          <?php if ($v['notas']): ?><small class="d-block"><?= e(mb_strimwidth($v['notas'], 0, 60, '…')) ?></small><?php endif; ?>
          <div class="d-flex gap-1 mt-2">
            <?php foreach ($v['estado'] === 'programada' ? [['en_curso', 'Iniciar', 'bi-play-fill']] : [] as [$e, $l, $ic]): ?>
              <form method="post" action="<?= url('visitas/estado/' . $v['id']) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="<?= $e ?>"><input type="hidden" name="mes" value="<?= e($mes) ?>"><button class="btn btn-soft btn-sm"><i class="bi <?= $ic ?>"></i> <?= $l ?></button></form>
            <?php endforeach; ?>
            <form method="post" action="<?= url('visitas/estado/' . $v['id']) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="completada"><input type="hidden" name="mes" value="<?= e($mes) ?>"><button class="btn btn-soft btn-sm" style="color:#059669"><i class="bi bi-check2"></i> Completar</button></form>
            <a href="<?= url('visitas/editar/' . $v['id']) ?>" class="btn btn-ghost btn-sm" style="border-radius:10px"><i class="bi bi-pencil"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach; if (!$prox): ?><p class="text-muted small">No hay visitas próximas.</p><?php endif; ?>
    </div>
  </div>
</div>

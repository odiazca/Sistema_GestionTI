<?php
$totRes = array_sum(array_column($tec, 'resueltos')); $totAct = array_sum(array_column($tec, 'activos'));
$maxRes = max(1, max(array_column($tec, 'resueltos') ?: [1]));
$rank = 0;
?>
<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Gestión</div>
    <h1>Equipo técnico</h1>
    <p>Productividad y carga de trabajo de los últimos 30 días.</p>
  </div>
  <a href="<?= url('usuarios/crear') ?>" class="btn btn-brand"><i class="bi bi-person-plus me-1"></i>Nuevo técnico</a>
</div>

<div class="row g-3 mb-3">
  <?php foreach ([['Técnicos activos', count($tec), 'bi-people-fill', '#4f46e5'], ['Tickets resueltos (30 d)', $totRes, 'bi-check-circle-fill', '#10b981'], ['Tickets en atención', $totAct, 'bi-hourglass-split', '#f59e0b'], ['Horas facturables (30 d)', array_sum(array_column($tec, 'horas')), 'bi-clock-history', '#0ea5e9']] as $s): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:<?= $s[3] ?>1f;color:<?= $s[3] ?>"><i class="bi <?= $s[2] ?>"></i></span><div><b><?= e($s[1]) ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <?php foreach ($tec as $t): $rank++;
      $sla = $t['resueltos'] ? round($t['en_sla'] * 100 / $t['resueltos']) : 100;
      $tmr = $t['tmr'] ? round($t['tmr'] / 60, 1) : 0; ?>
  <div class="col-md-6 col-xl-4">
    <div class="card-ui h-100">
      <div class="d-flex gap-3 align-items-center mb-3">
        <?= avatar_html($t['nombre'], $t['color'], 'avatar-lg') ?>
        <div class="flex-grow-1"><b class="d-block"><?= e($t['nombre']) ?></b><small class="text-muted"><?= e($t['cargo'] ?: $t['rol']) ?></small></div>
        <?php if ($rank <= 3 && $t['resueltos'] > 0): ?><span class="pill pill-<?= $rank === 1 ? 'warning' : 'muted' ?>"><i class="bi bi-trophy-fill"></i> #<?= $rank ?></span><?php endif; ?>
      </div>
      <div class="row g-2 text-center mb-3">
        <div class="col-4"><div class="stat-mini justify-content-center flex-column gap-0 py-2"><b><?= (int)$t['activos'] ?></b><span>Activos</span></div></div>
        <div class="col-4"><div class="stat-mini justify-content-center flex-column gap-0 py-2"><b><?= (int)$t['resueltos'] ?></b><span>Resueltos</span></div></div>
        <div class="col-4"><div class="stat-mini justify-content-center flex-column gap-0 py-2"><b><?= $tmr ?>h</b><span>T. medio</span></div></div>
      </div>
      <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Cumplimiento SLA</span><b style="color:<?= $sla >= 95 ? '#059669' : ($sla >= 85 ? '#d97706' : '#dc2626') ?>"><?= $sla ?>%</b></div>
      <div class="progress-ui mb-3"><div class="bar" style="width:<?= $sla ?>%;background:<?= $sla >= 95 ? '#10b981' : ($sla >= 85 ? '#f59e0b' : '#ef4444') ?>"></div></div>
      <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Productividad vs. equipo</span><b><?= (int)$t['resueltos'] ?> / <?= $maxRes ?></b></div>
      <div class="progress-ui mb-3"><div class="bar" style="width:<?= round($t['resueltos'] * 100 / $maxRes) ?>%;background:<?= e($t['color']) ?>"></div></div>
      <div class="d-flex flex-wrap gap-2">
        <?= pill($t['ordenes'] . ' órdenes abiertas', 'soft-info') ?><?= pill($t['visitas'] . ' visitas próximas', 'soft-primary') ?>
        <?= $t['vencidos'] ? pill($t['vencidos'] . ' fuera de SLA', 'danger') : '' ?>
      </div>
      <a href="<?= url('tickets?tecnico=' . $t['id']) ?>" class="btn btn-soft btn-sm w-100 mt-3">Ver tickets asignados <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
  </div>
  <?php endforeach; ?>
</div>

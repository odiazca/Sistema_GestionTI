<?php $q = $_GET; unset($q['url']); $mk = fn(array $ch) => url('actividades?' . http_build_query(array_filter(array_merge($q, $ch), fn($v) => $v !== null && $v !== ''))); $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']; ?>
<div class="page-head">
  <div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / CRM</div><h1>Actividades y seguimientos</h1><p>Tu agenda comercial: llamadas, reuniones, correos y tareas pendientes.</p></div>
  <button class="btn btn-brand" data-nueva-act='{}'><i class="bi bi-calendar-plus me-1"></i>Nueva actividad</button>
</div>
<div class="row g-3 mb-3">
  <?php foreach ([['Vencidas', (int)$kp['vencidas'], 'bi-exclamation-triangle-fill', '#ef4444'], ['Para hoy', (int)$kp['hoy'], 'bi-calendar-event-fill', '#4f46e5'], ['Pendientes', (int)$kp['pendientes'], 'bi-hourglass-split', '#0ea5e9'], ['Hechas (7 días)', (int)$kp['hechas7'], 'bi-check2-circle', '#10b981']] as $s): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:<?= $s[3] ?>1f;color:<?= $s[3] ?>"><i class="bi <?= $s[2] ?>"></i></span><div><b><?= $s[1] ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>
<div class="week-strip mb-3">
  <?php foreach ($semana as $d): $on = $dia === $d['f']; $ts = strtotime($d['f']); ?>
    <a href="<?= $mk(['dia' => $on ? null : $d['f'], 'vista' => 'pend']) ?>" class="wd <?= $on ? 'on' : '' ?> <?= $d['n'] ? 'has' : '' ?>"><small><?= $d['f'] === date('Y-m-d') ? 'Hoy' : $dias[(int)date('w', $ts)] ?></small><b><?= date('d', $ts) ?></b><span><?= $d['n'] ? $d['n'] . ' act.' : '—' ?></span></a>
  <?php endforeach; ?>
</div>
<form method="get" class="filter-bar form-ui card-ui mb-3" style="padding:.75rem 1rem">
  <div class="seg"><?php foreach (['pend' => 'Pendientes', 'hechas' => 'Realizadas', 'todas' => 'Todas'] as $k => $v): ?><a class="<?= $vista === $k ? 'on' : '' ?>" href="<?= $mk(['vista' => $k]) ?>"><?= $v ?></a><?php endforeach; ?></div>
  <input type="hidden" name="vista" value="<?= e($vista) ?>"><?php if ($dia): ?><input type="hidden" name="dia" value="<?= e($dia) ?>"><?php endif; ?>
  <div class="search-ui"><i class="bi bi-search"></i><input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar asunto, cliente o lead…"></div>
  <select name="tipo" class="select-ui" onchange="this.form.submit()"><option value="">Tipo: todos</option><?php foreach (ACT_TIPOS as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['tipo'] ?? '') === $k ? 'selected' : '' ?>><?= $v[0] ?></option><?php endforeach; ?></select>
  <select name="resp" class="select-ui" onchange="this.form.submit()"><option value="">Responsable: todos</option><option value="mine" <?= $resp === 'mine' ? 'selected' : '' ?>>Mis actividades</option><?php foreach ($vendedores as $v): ?><option value="<?= $v['id'] ?>" <?= $resp === (string)$v['id'] ? 'selected' : '' ?>><?= e($v['nombre']) ?></option><?php endforeach; ?></select>
  <?php if ($dia): ?><a class="btn btn-ghost" href="<?= $mk(['dia' => null]) ?>"><i class="bi bi-x"></i> <?= fdate($dia) ?></a><?php endif; ?>
</form>
<?php if ($vista === 'pend'): $hay = false; foreach ($grupos as $g): if (!$g['rows']) continue; $hay = true; ?>
  <div class="card-ui mb-3"><div class="card-head"><div><h6 style="color:<?= $g['t'][1] ?>"><i class="bi <?= $g['t'][2] ?> me-1"></i><?= $g['t'][0] ?> <span class="kb-n"><?= count($g['rows']) ?></span></h6></div></div>
    <?php foreach ($g['rows'] as $a) echo act_item($a); ?></div>
<?php endforeach; if (!$hay): ?><div class="card-ui text-center py-5 text-muted"><i class="bi bi-emoji-smile fs-1 d-block mb-2"></i>Todo al día. No hay actividades pendientes con este filtro.</div><?php endif; ?>
<?php else: ?>
  <div class="card-ui"><?php foreach ($rows as $a) echo act_item($a); if (!$rows): ?><p class="text-muted text-center py-4 mb-0">Sin actividades.</p><?php endif; ?></div>
<?php endif; ?>
<?php include APP_PATH . '/views/crm/_modales.php'; ?>

<?php
$cards = [
  ['c' => 'indigo', 'icon' => 'bi-bullseye', 'val' => money($k['pipeline']), 'tit' => 'Pipeline abierto', 'sub' => (int)$k['n_abiertas'] . ' oportunidades', 'link' => 'oportunidades', 'chip' => $k['atrasadas'] ? $k['atrasadas'] . ' atrasadas' : ''],
  ['c' => 'violet', 'icon' => 'bi-graph-up-arrow', 'val' => money($k['forecast']), 'tit' => 'Forecast ponderado', 'sub' => 'Valor × probabilidad', 'link' => 'oportunidades'],
  ['c' => 'green', 'icon' => 'bi-trophy-fill', 'val' => money($k['ganado_mes']), 'tit' => 'Ganado este mes', 'sub' => (int)$k['n_ganadas_mes'] . ' cierres', 'link' => 'oportunidades?vista=lista&estado=ganada'],
  ['c' => 'cyan', 'icon' => 'bi-percent', 'val' => (int)$k['conversion'] . '%', 'tit' => 'Conversión (90 d)', 'sub' => 'Ticket prom. ' . money($k['ticket_prom']), 'link' => 'oportunidades?vista=lista'],
  ['c' => 'blue', 'icon' => 'bi-person-plus-fill', 'val' => (int)$k['leads_mes'], 'tit' => 'Leads del mes', 'sub' => $k['leads_sin_atender'] . ' sin atender (+2 d)', 'link' => 'leads', 'chip' => $k['leads_sin_atender'] ? 'Atender' : ''],
  ['c' => 'red', 'icon' => 'bi-alarm-fill', 'val' => (int)$k['act_vencidas'], 'tit' => 'Mis actividades vencidas', 'sub' => $k['act_hoy'] . ' para hoy', 'link' => 'actividades'],
];
$etq = ['web' => 'Sitio web', 'referido' => 'Referido', 'llamada' => 'Llamada', 'evento' => 'Evento', 'redes' => 'Redes', 'correo' => 'Correo', 'otro' => 'Otro'];
?>
<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / CRM</div><h1>Panel comercial</h1><p>Embudo, forecast, renovaciones y clientes en riesgo en un solo lugar.</p></div>
  <div class="d-flex gap-2 flex-wrap"><a class="btn btn-ghost" href="<?= url('leads/crear') ?>"><i class="bi bi-person-plus me-1"></i>Nuevo lead</a><a class="btn btn-brand" href="<?= url('oportunidades/crear') ?>"><i class="bi bi-plus-lg me-1"></i>Nueva oportunidad</a></div></div>
<div class="row g-3 mt-0">
<?php foreach ($cards as $c): ?><div class="col-12 col-sm-6 col-xl-4"><a href="<?= url($c['link']) ?>" class="kpi-g kpi-g-<?= $c['c'] ?>"><i class="bi <?= $c['icon'] ?> kpi-g-bg"></i><div class="kpi-g-val"><?= e($c['val']) ?></div><div class="kpi-g-tit"><?= e($c['tit']) ?></div><div class="kpi-g-sub"><?= e($c['sub']) ?></div><div class="kpi-g-foot"><span class="kpi-g-go">Ver <i class="bi bi-arrow-right"></i></span><?php if (!empty($c['chip'])): ?><span class="kpi-g-chip"><?= e($c['chip']) ?></span><?php endif; ?></div></a></div><?php endforeach; ?>
</div>
<div class="row g-3 mt-1">
  <div class="col-xl-5"><div class="card-ui h-100"><div class="card-head"><div><h6>Embudo de ventas</h6><small>Oportunidades abiertas por etapa</small></div></div>
    <?php $mx = max(1, max(array_column($embudo, 'valor') ?: [1])); foreach ($embudo as $e): ?>
    <a href="<?= url('oportunidades') ?>" class="funnel-row"><div class="fn-l"><b><?= e($e['nombre']) ?></b><small><?= (int)$e['n'] ?> oport. · <?= (int)$e['probabilidad'] ?>%</small></div><div class="fn-bar"><span style="width:<?= max(4, round($e['valor'] / $mx * 100)) ?>%;background:<?= e($e['color']) ?>"></span></div><div class="fn-v"><?= money($e['valor']) ?></div></a>
    <?php endforeach; ?></div></div>
  <div class="col-xl-7"><div class="card-ui h-100"><div class="card-head"><div><h6>Forecast de cierres</h6><small>Próximos 6 meses · total vs ponderado</small></div></div><div style="height:250px"><canvas id="chForecast"></canvas></div></div></div>
</div>
<div class="row g-3 mt-1">
  <div class="col-xl-4"><div class="card-ui h-100"><div class="card-head"><div><h6>Origen de oportunidades</h6></div></div><div style="height:210px"><canvas id="chOrigen"></canvas></div></div></div>
  <div class="col-xl-8"><div class="card-ui h-100"><div class="card-head"><div><h6>Ranking comercial</h6><small>Últimos 90 días</small></div></div>
    <div class="table-responsive"><table class="table-ui"><thead><tr><th>Vendedor</th><th class="text-end">Ganado</th><th class="text-center">G / P</th><th class="text-end">Pipeline</th></tr></thead><tbody>
    <?php foreach ($ranking as $r): ?><tr><td><?= avatar_html($r['nombre'], $r['color'] ?: '#4f46e5', 'avatar-sm') ?> <b class="ms-1"><?= e($r['nombre']) ?></b></td><td class="text-end fw-semibold"><?= money($r['ganado']) ?></td><td class="text-center"><span class="text-success"><?= (int)$r['n_g'] ?></span> / <span class="text-danger"><?= (int)$r['n_p'] ?></span></td><td class="text-end"><?= money($r['pipeline']) ?> <small class="text-muted">(<?= (int)$r['n_a'] ?>)</small></td></tr>
    <?php endforeach; if (!$ranking): ?><tr><td colspan="4" class="empty-row">Sin datos todavía.</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>
<div class="row g-3 mt-1">
  <div class="col-xl-4"><div class="card-ui h-100"><div class="card-head"><div><h6><i class="bi bi-calendar-check text-primary me-1"></i>Agenda próxima</h6></div><a href="<?= url('actividades') ?>" class="btn btn-soft btn-sm">Ver todo</a></div>
    <?php foreach ($acts as $a): $v = strtotime($a['fecha']) < time() - 3600; ?><div class="alert-row"><div><b><?= e($a['asunto']) ?></b><small><?= e($a['cliente'] ?: $a['lead_nombre'] ?: '') ?> · <?= e(ACT_TIPOS[$a['tipo']][0] ?? '') ?></small></div><span class="pill <?= $v ? 'pill-danger' : 'pill-soft-primary' ?>"><?= fdate($a['fecha'], 'd/m H:i') ?></span></div>
    <?php endforeach; if (!$acts) echo '<p class="text-muted small">Sin actividades próximas.</p>'; ?></div></div>
  <div class="col-xl-4"><div class="card-ui h-100"><div class="card-head"><div><h6><i class="bi bi-bullseye text-success me-1"></i>Cierres próximos</h6><small>Próximos 30 días</small></div></div>
    <?php foreach ($cierres as $o): ?><a href="<?= url('oportunidades/ver/' . $o['id']) ?>" class="alert-row text-reset text-decoration-none"><div><b><?= e($o['titulo']) ?></b><small><?= e($o['cliente']) ?> · <?= e($o['etapa']) ?></small></div><div class="text-end"><b class="small"><?= money($o['valor']) ?></b><small class="d-block <?= $o['fecha_cierre_estimada'] < date('Y-m-d') ? 'text-danger' : 'text-muted' ?>"><?= fdate($o['fecha_cierre_estimada'], 'd M') ?></small></div></a>
    <?php endforeach; if (!$cierres) echo '<p class="text-muted small">Sin cierres previstos.</p>'; ?></div></div>
  <div class="col-xl-4"><div class="card-ui h-100"><div class="card-head"><div><h6><i class="bi bi-arrow-repeat text-warning me-1"></i>Renovaciones de contrato</h6></div></div>
    <?php foreach ($renov as $r): ?><div class="alert-row"><div><b><?= e($r['cliente']) ?></b><small><?= e($r['codigo']) ?> · <?= e($r['plan']) ?> · <?= $r['dias'] < 0 ? 'venció hace ' . abs($r['dias']) . ' d' : 'vence en ' . $r['dias'] . ' d' ?></small></div>
      <?php if ($r['en_curso']): ?><span class="pill pill-success">En curso</span><?php else: ?><form method="post" action="<?= url('crm/renovar/' . $r['id']) ?>"><?= csrf_field() ?><button class="btn btn-soft btn-sm">Renovar</button></form><?php endif; ?></div>
    <?php endforeach; if (!$renov) echo '<p class="text-muted small">Sin contratos por renovar.</p>'; ?></div></div>
</div>
<div class="row g-3 mt-1">
  <div class="col-xl-7"><div class="card-ui h-100"><div class="card-head"><div><h6><i class="bi bi-heart-pulse text-danger me-1"></i>Clientes en riesgo</h6><small>Contrato, SLA, tickets y falta de contacto comercial</small></div></div>
    <?php foreach ($riesgo as $r): ?><a href="<?= url('clientes/ver/' . $r['id']) ?>" class="alert-row text-reset text-decoration-none"><div><b><?= e($r['cliente']) ?></b><small><?= e(implode(' · ', $r['why'])) ?></small></div><span class="pill <?= $r['score'] >= 70 ? 'pill-danger' : 'pill-warning' ?>"><?= (int)$r['score'] ?>/100</span></a>
    <?php endforeach; if (!$riesgo) echo '<p class="text-muted small">Ningún cliente en riesgo. ¡Buen trabajo!</p>'; ?></div></div>
  <div class="col-xl-5"><div class="card-ui h-100"><div class="card-head"><div><h6>Motivos de pérdida</h6></div></div>
    <?php $tm = array_sum(array_column($motivos, 'n')) ?: 1; foreach ($motivos as $m): ?><div class="mb-2"><div class="d-flex justify-content-between small"><span><?= e($m['motivo']) ?></span><b><?= (int)$m['n'] ?></b></div><div class="progress" style="height:6px"><div class="progress-bar bg-danger" style="width:<?= round($m['n'] / $tm * 100) ?>%"></div></div></div>
    <?php endforeach; if (!$motivos) echo '<p class="text-muted small">Sin oportunidades perdidas.</p>'; ?></div></div>
</div>
<?php
$d = ['forecast' => $forecast, 'origen' => array_map(fn($o) => ['l' => $etq[$o['origen']] ?? ucfirst($o['origen']), 'n' => (int)$o['total']], $origen)];
$scripts = '<script>window.CRM_DATA = ' . json_encode($d, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>';
?>

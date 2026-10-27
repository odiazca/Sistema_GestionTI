<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Facturación</div><h1>Ingresos y cobranza</h1><p>Evolución de lo facturado y cobrado en los últimos 12 meses.</p></div></div>
<?php include __DIR__ . '/_tabs.php'; ?>
<div class="row g-3 mb-3">
  <?php foreach ([['Facturado (12 m)', money($k['fact12']), 'bi-receipt', '#4f46e5'], ['Cobrado (12 m)', money($k['cobrado12']), 'bi-cash-coin', '#10b981'], ['Eficiencia de cobro', $k['eficiencia'] . '%', 'bi-percent', '#0ea5e9'], ['Recurrente del mes', money($k['recurrente_mes']), 'bi-arrow-repeat', '#8b5cf6'], ['Pendiente', money($k['pendiente']), 'bi-hourglass-split', '#f59e0b'], ['Vencido', money($k['vencido']), 'bi-exclamation-triangle-fill', '#ef4444']] as $s): ?>
  <div class="col-6 col-xl-2"><div class="stat-mini"><span class="ic" style="background:<?= $s[3] ?>1f;color:<?= $s[3] ?>"><i class="bi <?= $s[2] ?>"></i></span><div><b style="font-size:1rem"><?= $s[1] ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-xl-8"><div class="card-ui h-100"><div class="card-head"><div><h6>Facturado vs cobrado</h6><small>Por mes · soles (incluye IGV)</small></div></div><div style="height:280px"><canvas id="chIng"></canvas></div></div></div>
  <div class="col-xl-4"><div class="card-ui h-100"><div class="card-head"><div><h6>Cobros por método</h6><small>Últimos 12 meses</small></div></div><div style="height:240px"><canvas id="chMet"></canvas></div></div></div>
  <div class="col-xl-5"><div class="card-ui h-100"><div class="card-head"><div><h6>Facturado por concepto</h6></div></div><div style="height:230px"><canvas id="chCon"></canvas></div></div></div>
  <div class="col-xl-7"><div class="card-ui h-100"><div class="card-head"><div><h6>Principales clientes</h6><small>12 meses</small></div></div>
    <div class="table-responsive"><table class="table-ui"><thead><tr><th>Cliente</th><th class="text-end">Facturado</th><th class="text-end">Cobrado</th><th class="text-end">Pendiente</th></tr></thead><tbody>
    <?php foreach ($top as $t): ?><tr><td><a href="<?= url('clientes/ver/' . $t['id']) ?>"><?= e($t['cliente']) ?></a></td><td class="text-end fw-semibold"><?= money($t['facturado']) ?></td><td class="text-end"><?= money($t['cobrado']) ?></td><td class="text-end <?= $t['pendiente'] > 0 ? 'text-danger' : '' ?>"><?= money($t['pendiente']) ?></td></tr><?php endforeach; if (!$top) echo '<tr><td colspan="4" class="empty-row">Sin datos.</td></tr>'; ?></tbody></table></div></div></div>
</div>
<?php $scripts = '<script>window.FAC_DATA = ' . json_encode(['serie' => $serie, 'metodos' => $metodos, 'conceptos' => $conceptos], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script><script src="' . asset('js/fac.js') . '"></script>'; ?>

<?php $def = $r['def']; $qs = http_build_query(['desde' => $r['desde'], 'hasta' => $r['hasta']]); ?>
<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('reportes') ?>">Reportes</a> / <?= e($def[0]) ?></div>
    <h1><?= e($def[0]) ?></h1>
    <p class="d-none d-print-block"><?= e(cfg('empresa_nombre', APP_NAME)) ?> · <?= $def[4] ? 'Del ' . fdate($r['desde']) . ' al ' . fdate($r['hasta']) : 'Al ' . date('d/m/Y') ?></p>
    <p class="d-print-none"><?= e($def[3]) ?></p>
  </div>
  <div class="d-flex gap-2 d-print-none">
    <a class="btn btn-ghost" href="<?= url('reportes/exportar/' . $r['id'] . '?' . $qs) ?>"><i class="bi bi-file-earmark-excel me-1"></i>Excel (CSV)</a>
    <button class="btn btn-brand" onclick="window.print()"><i class="bi bi-file-earmark-pdf me-1"></i>Imprimir / PDF</button>
  </div>
</div>

<?php if ($def[4]): ?>
<form method="get" class="card-ui mb-3 d-print-none">
  <div class="filter-bar m-0 form-ui">
    <label class="lbl m-0">Desde</label><input type="date" class="form-control" style="max-width:170px" name="desde" value="<?= e($r['desde']) ?>">
    <label class="lbl m-0">Hasta</label><input type="date" class="form-control" style="max-width:170px" name="hasta" value="<?= e($r['hasta']) ?>">
    <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Aplicar</button>
    <?php foreach ([['7 días', 6], ['30 días', 29], ['90 días', 89]] as [$l, $n]): ?>
      <a class="btn btn-ghost btn-sm" style="height:36px;display:inline-flex;align-items:center" href="?desde=<?= date('Y-m-d', strtotime("-$n days")) ?>&hasta=<?= date('Y-m-d') ?>"><?= $l ?></a>
    <?php endforeach; ?>
    <a class="btn btn-ghost btn-sm" style="height:36px;display:inline-flex;align-items:center" href="?desde=<?= date('Y-m-01') ?>&hasta=<?= date('Y-m-d') ?>">Este mes</a>
  </div>
</form>
<?php endif; ?>

<div class="row g-3 mb-3">
  <?php foreach ($r['kpis'] as $k): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:<?= $k[3] ?>1f;color:<?= $k[3] ?>"><i class="bi <?= $k[2] ?>"></i></span><div><b><?= e($k[1]) ?></b><span><?= e($k[0]) ?></span></div></div></div>
  <?php endforeach; ?>
</div>

<div class="card-ui mb-3">
  <div class="card-head"><div><h6><?= e($r['chart']['title']) ?></h6></div></div>
  <div class="chart-box" style="height:<?= $r['chart']['type'] === 'doughnut' ? 300 : 320 ?>px"><canvas id="chReporte"></canvas></div>
</div>

<div class="card-ui">
  <div class="card-head"><div><h6>Detalle</h6><small><?= count($r['rows']) ?> registros</small></div></div>
  <div class="table-responsive table-scroll">
    <table class="table-ui table-report">
      <thead><tr><?php foreach ($r['cols'] as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach ($r['rows'] as $row): ?><tr><?php foreach ($row as $i => $cell): ?><td class="<?= is_numeric(str_replace(['%'], '', (string)$cell)) && $i > 0 ? 'text-end' : '' ?>"><?= e($cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
      <?php if (!$r['rows']): ?><tr><td colspan="<?= count($r['cols']) ?>" class="empty-row"><i class="bi bi-inbox"></i>Sin datos en el período seleccionado.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $scripts = '<script>window.TD_REPORT = ' . json_encode($r['chart'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script><script src="' . asset('js/reporte.js') . '"></script>'; ?>

<div class="page-head">
  <div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Mesa de Ayuda</div><h1>Calendario</h1><p>Visitas, órdenes, actividades comerciales y vencimientos en una sola vista.</p></div>
  <div class="d-flex gap-2 align-items-center"><button class="btn btn-ghost" id="calPrev"><i class="bi bi-chevron-left"></i></button><b id="calTitulo" class="px-2" style="min-width:150px;text-align:center;text-transform:capitalize"></b><button class="btn btn-ghost" id="calNext"><i class="bi bi-chevron-right"></i></button><button class="btn btn-soft" id="calHoy">Hoy</button></div>
</div>
<div class="d-flex flex-wrap gap-2 mb-3" id="calCapas">
  <?php foreach ($capas as $k => [$l, $c, $ic]): ?><button type="button" class="cal-capa active" data-k="<?= $k ?>" style="--c:<?= $c ?>"><i class="bi <?= $ic ?>"></i><?= e($l) ?></button><?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-xl-9"><div class="card-ui p-2 p-md-3"><div class="cal-grid" id="calGrid"></div></div></div>
  <div class="col-xl-3"><div class="card-ui p-3"><h6 class="fw-bold mb-3" id="calDiaTitulo">Agenda del día</h6><div id="calDia" class="cal-dia"></div></div></div>
</div>
<?php $scripts = '<script>window.CAL_URL=' . json_encode(url('calendario/eventos')) . ';</script><script src="' . asset('js/calendario.js') . '"></script>'; ?>

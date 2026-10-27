<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Sistema</div><h1>Importar datos</h1><p>Carga masiva desde Excel o CSV con validación antes de guardar.</p></div></div>
<div class="row g-3">
  <?php foreach ($tipos as $k => [$l, $ic, $c, $campos]): ?>
  <div class="col-lg-4"><div class="card-ui p-4 h-100 d-flex flex-column">
    <div class="d-flex align-items-center gap-3 mb-3"><span class="ic-lg" style="background:<?= $c ?>1f;color:<?= $c ?>"><i class="bi <?= $ic ?>"></i></span><div><h5 class="fw-bold mb-0"><?= e($l) ?></h5><small class="text-muted"><?= count($campos) ?> columnas</small></div></div>
    <p class="small text-muted mb-2">Columnas: <?php $x = []; foreach ($campos as [$cl, $rq]) $x[] = $rq ? '<b>' . e($cl) . '*</b>' : e($cl); echo implode(' · ', $x); ?></p>
    <form method="post" action="<?= url('importar/subir') ?>" enctype="multipart/form-data" class="form-ui mt-auto"><?= csrf_field() ?><input type="hidden" name="tipo" value="<?= $k ?>">
      <input type="file" class="form-control mb-2" name="archivo" accept=".xlsx,.csv,.txt" required>
      <div class="d-flex gap-2"><button class="btn btn-brand flex-grow-1"><i class="bi bi-eye me-1"></i>Revisar archivo</button><a class="btn btn-ghost" href="<?= url('importar/plantilla/' . $k) ?>" title="Descargar plantilla"><i class="bi bi-download"></i></a></div>
    </form>
  </div></div>
  <?php endforeach; ?>
</div>
<div class="card-ui p-4 mt-3"><h6 class="fw-bold"><i class="bi bi-info-circle me-1 text-primary"></i>Cómo funciona</h6>
  <ol class="small text-muted mb-0"><li>Descarga la plantilla (CSV, ábrela con Excel) y completa tus datos; también puedes subir un <b>.xlsx</b> con los mismos encabezados.</li><li>Revisa la vista previa: las filas con errores o duplicados se omiten y se explica el motivo.</li><li>Confirma y se guardan solo las filas válidas, todo en una sola operación.</li></ol></div>

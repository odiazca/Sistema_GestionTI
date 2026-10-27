<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Gestión</div>
    <h1>Reportes</h1>
    <p>Informes gerenciales y operativos. Todos se pueden exportar a Excel (CSV) e imprimir en PDF.</p>
  </div>
</div>
<div class="row g-3">
  <?php foreach ($defs as $id => $d): ?>
  <div class="col-md-6 col-xl-4">
    <a href="<?= url('reportes/ver/' . $id) ?>" class="card-ui report-card d-block h-100" style="--c:<?= $d[2] ?>">
      <div class="rc-ic"><i class="bi <?= $d[1] ?>"></i></div>
      <h5><?= e($d[0]) ?></h5>
      <p><?= e($d[3]) ?></p>
      <span class="rc-go">Ver reporte <i class="bi bi-arrow-right"></i></span>
    </a>
  </div>
  <?php endforeach; ?>
</div>

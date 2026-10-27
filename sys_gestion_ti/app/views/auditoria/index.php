<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Sistema</div>
    <h1>Auditoría</h1>
    <p>Bitácora de acciones realizadas en el sistema.</p>
  </div>
  <a class="btn btn-ghost" href="<?= url('auditoria/exportar?' . http_build_query(array_diff_key($_GET, ['url' => 1, 'page' => 1]))) ?>"><i class="bi bi-file-earmark-excel me-1"></i>Exportar CSV</a>
</div>
<div class="row g-3 mb-3">
  <?php foreach ([['Eventos registrados', $total, 'bi-journal-text', '#4f46e5'], ['Eventos hoy', $hoy, 'bi-activity', '#10b981'], ['Inicios de sesión (7 d)', $accesos, 'bi-box-arrow-in-right', '#0ea5e9'], ['Usuarios activos (7 d)', $activos, 'bi-people-fill', '#f59e0b']] as $s): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:<?= $s[3] ?>1f;color:<?= $s[3] ?>"><i class="bi <?= $s[2] ?>"></i></span><div><b><?= (int)$s[1] ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card-ui">
  <form method="get" class="filter-bar form-ui">
    <div class="search-ui"><i class="bi bi-search"></i><input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar acción o detalle…"></div>
    <select name="usuario" class="select-ui" onchange="this.form.submit()"><option value="">Usuario: todos</option>
      <?php foreach ($usuarios as $u): ?><option value="<?= $u['id'] ?>" <?= ($_GET['usuario'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>><?= e($u['n']) ?></option><?php endforeach; ?></select>
    <input type="date" class="form-control" style="max-width:160px" name="desde" value="<?= e($_GET['desde'] ?? '') ?>" title="Desde">
    <input type="date" class="form-control" style="max-width:160px" name="hasta" value="<?= e($_GET['hasta'] ?? '') ?>" title="Hasta">
    <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    <?php if (array_filter(array_diff_key($_GET, ['url' => 1, 'page' => 1]))): ?><a href="<?= url('auditoria') ?>" class="btn btn-ghost">Limpiar</a><?php endif; ?>
  </form>
  <div class="timeline">
    <?php foreach ($rows as $a): ?>
    <div class="tl-item">
      <span class="tl-ic" style="background:<?= e($a['color'] ?: '#64748b') ?>1f;color:<?= e($a['color'] ?: '#64748b') ?>"><i class="bi <?= e($a['icono']) ?>"></i></span>
      <div class="flex-grow-1 d-flex justify-content-between gap-3 flex-wrap">
        <div><b><?= e($a['accion']) ?></b><small><?= e($a['detalle']) ?></small><em><?= e($a['usuario'] ?? 'Sistema') ?></em></div>
        <div class="text-end small text-muted"><?= fdate($a['created_at'], 'd/m/Y H:i') ?><br><?= e(hace($a['created_at'])) ?></div>
      </div>
    </div>
    <?php endforeach; if (!$rows): ?><div class="empty-row"><i class="bi bi-inbox"></i>No hay eventos con esos filtros.</div><?php endif; ?>
  </div>
  <?= pager($pg) ?>
</div>

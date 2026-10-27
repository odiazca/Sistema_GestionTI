<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Sistema</div>
    <h1>Configuración</h1>
    <p>Parámetros generales de la empresa y del sistema.</p>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-3">
    <div class="card-ui p-2">
      <?php foreach ($tabs as $k => [$lab, $ic]): ?>
        <a href="<?= url('configuracion?tab=' . $k) ?>" class="cfg-tab <?= $tab === $k ? 'active' : '' ?>"><i class="bi <?= $ic ?>"></i><?= e($lab) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-9">

  <?php if ($tab === 'categorias'): ?>
    <div class="card-ui mb-3 form-ui">
      <div class="card-head"><div><h6>Nueva categoría de tickets</h6></div></div>
      <form method="post" action="<?= url('configuracion/categoria') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-5"><label class="lbl">Nombre</label><input class="form-control" name="nombre" maxlength="60" required></div>
        <div class="col-md-4"><label class="lbl">Ícono</label><select class="form-select" name="icono"><?php foreach ($iconos as $k => $v): ?><option value="<?= $k ?>"><?= e($v) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-1"><label class="lbl">Color</label><input type="color" class="form-control form-control-color w-100" style="height:44px;padding:4px" name="color" value="#4f46e5"></div>
        <div class="col-md-2"><button class="btn btn-brand w-100" style="height:44px"><i class="bi bi-plus-lg"></i> Agregar</button></div>
      </form>
    </div>
    <div class="card-ui">
      <div class="card-head"><div><h6>Categorías (<?= count($cats) ?>)</h6></div></div>
      <?php foreach ($cats as $c): ?>
      <form method="post" action="<?= url('configuracion/categoria') ?>" class="cat-row form-ui">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <span class="tl-ic" style="background:<?= e($c['color']) ?>22;color:<?= e($c['color']) ?>"><i class="bi <?= e($c['icono']) ?>"></i></span>
        <input class="form-control" name="nombre" value="<?= e($c['nombre']) ?>" maxlength="60" style="flex:1;min-width:150px">
        <select class="form-select" name="icono" style="width:190px"><?php foreach ($iconos as $k => $v): ?><option value="<?= $k ?>" <?= $c['icono'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
        <input type="color" class="form-control form-control-color" style="width:52px;height:44px;padding:4px" name="color" value="<?= e($c['color']) ?>">
        <span class="text-muted small" style="width:80px"><?= (int)$c['usos'] ?> tickets</span>
        <button class="act-btn" title="Guardar"><i class="bi bi-check2"></i></button>
        <button type="submit" formaction="<?= url('configuracion/categoria_eliminar/' . $c['id']) ?>" class="act-btn danger" title="Eliminar"><i class="bi bi-trash3"></i></button>
      </form>
      <?php endforeach; ?>
    </div>

  <?php elseif ($tab === 'seguridad'): ?>
    <form method="post" action="<?= url('configuracion/password') ?>" class="card-ui form-ui" style="max-width:560px">
      <?= csrf_field() ?>
      <div class="card-head"><div><h6>Cambiar mi contraseña</h6><small>Usa al menos 6 caracteres</small></div></div>
      <div class="mb-3"><label class="lbl">Contraseña actual</label><input type="password" class="form-control" name="actual" autocomplete="current-password" required></div>
      <div class="mb-3"><label class="lbl">Nueva contraseña</label><input type="password" class="form-control" name="nueva" autocomplete="new-password" required></div>
      <div class="mb-3"><label class="lbl">Confirmar nueva contraseña</label><input type="password" class="form-control" name="confirmar" autocomplete="new-password" required></div>
      <button class="btn btn-brand"><i class="bi bi-key me-1"></i>Actualizar contraseña</button>
    </form>

  <?php else: ?>
    <a href="<?= url('respaldo') ?>" class="card-ui mb-3 d-flex align-items-center gap-3 p-3 text-decoration-none" style="color:inherit"><span class="bk-ic" style="--c:#4f46e5"><i class="bi bi-hdd-fill"></i></span><div class="flex-grow-1"><b>Respaldo y restauración</b><div class="text-muted small">Crea copias, restaura y reinicia el sistema para una empresa nueva.</div></div><i class="bi bi-chevron-right"></i></a>
    <div class="row g-3">
      <div class="col-md-6"><div class="card-ui h-100"><div class="card-head"><div><h6>Entorno</h6></div></div>
        <div class="info-list"><?php foreach ($info as $k => $v): ?><div class="info-item"><span><?= e($k) ?></span><b><?= e($v) ?></b></div><?php endforeach; ?></div></div></div>
      <div class="col-md-6"><div class="card-ui h-100"><div class="card-head"><div><h6>Registros</h6></div></div>
        <div class="info-list"><?php foreach ($conteos as $k => $v): ?><div class="info-item"><span><?= e(ucwords(str_replace('_', ' ', $k))) ?></span><b><?= e($v) ?></b></div><?php endforeach; ?></div></div></div>
    </div>
  <?php endif; ?>

  </div>
</div>

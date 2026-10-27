<?php
$c = fn($k, $d = '') => cfg($k, $d); $f = fmt_cfg(); $logo = logo_url(); $mon = EmpresaController::MONEDAS;
$sm = $c('sep_miles', ','); $color = $c('color_marca', 'indigo');
?>
<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Sistema</div><h1>Empresa</h1><p>Identidad, moneda y formatos que usa todo el sistema: pantallas, reportes, cotizaciones y facturas.</p></div></div>
<div class="seg mb-3"><?php foreach ($tabs as $k => [$l, $ic]): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= url('empresa?tab=' . $k) ?>"><i class="bi <?= $ic ?>"></i><?= e($l) ?></a><?php endforeach; ?></div>

<div class="row g-3">
<div class="col-xl-8">
<?php if ($tab === 'datos'): ?>
  <div class="card-ui bk-card mb-3">
    <div class="d-flex gap-3 align-items-center flex-wrap">
      <div class="emp-logo"><?= $logo ? '<img src="' . e($logo) . '" alt="Logo">' : '<i class="bi bi-image"></i>' ?></div>
      <div class="flex-grow-1"><h5 class="mb-1">Logo de la empresa</h5><p class="text-muted small mb-2">PNG, JPG o WebP de hasta 2 MB. Se ajusta automáticamente y aparece en el menú, el inicio de sesión, el portal y los documentos impresos. Mejor con fondo transparente.</p>
        <form method="post" action="<?= url('empresa/logo_subir') ?>" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center"><?= csrf_field() ?>
          <input type="file" class="form-control form-control-sm" name="logo" accept="image/png,image/jpeg,image/webp" required style="max-width:280px"><button class="btn btn-sm btn-brand"><i class="bi bi-upload me-1"></i>Subir</button></form>
        <?php if ($logo): ?><form method="post" action="<?= url('empresa/logo_quitar') ?>" class="mt-2" onsubmit="return confirm('¿Quitar el logo?')"><?= csrf_field() ?><button class="btn btn-sm btn-ghost text-danger"><i class="bi bi-trash3 me-1"></i>Quitar logo</button></form><?php endif; ?></div></div></div>
  <form method="post" action="<?= url('empresa/guardar') ?>" class="card-ui bk-card form-ui"><?= csrf_field() ?><input type="hidden" name="tab" value="datos">
    <h5 class="mb-3">Datos generales</h5>
    <div class="row g-3">
      <div class="col-md-6"><label class="lbl">Nombre comercial *</label><input class="form-control" name="empresa_nombre" required maxlength="100" value="<?= e($c('empresa_nombre', APP_NAME)) ?>" data-pv="nombre"></div>
      <div class="col-md-6"><label class="lbl">Eslogan (aparece bajo el nombre)</label><input class="form-control" name="empresa_eslogan" maxlength="60" value="<?= e($c('empresa_eslogan', 'Soporte TI')) ?>" data-pv="eslogan"></div>
      <div class="col-md-8"><label class="lbl">Razón social</label><input class="form-control" name="empresa_razon_social" maxlength="150" value="<?= e($c('empresa_razon_social')) ?>"></div>
      <div class="col-md-4"><label class="lbl">Identificación fiscal</label><div class="input-group"><select class="form-select" name="doc_etiqueta" style="max-width:110px"><?php foreach (EmpresaController::DOCS as $d): ?><option <?= $c('doc_etiqueta', 'RUC') === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?></select><input class="form-control" name="empresa_ruc" maxlength="20" value="<?= e($c('empresa_ruc')) ?>"></div></div>
      <div class="col-md-6"><label class="lbl">Giro / actividad</label><input class="form-control" name="empresa_giro" maxlength="100" value="<?= e($c('empresa_giro')) ?>" placeholder="Servicios de tecnología e infraestructura"></div>
      <div class="col-md-6"><label class="lbl">Sitio web</label><input class="form-control" name="empresa_web" maxlength="150" value="<?= e($c('empresa_web')) ?>" placeholder="www.miempresa.com"></div>
      <div class="col-md-12"><label class="lbl">Dirección</label><input class="form-control" name="empresa_direccion" maxlength="200" value="<?= e($c('empresa_direccion')) ?>"></div>
      <div class="col-md-4"><label class="lbl">Ciudad</label><input class="form-control" name="empresa_ciudad" maxlength="80" value="<?= e($c('empresa_ciudad')) ?>"></div>
      <div class="col-md-4"><label class="lbl">País</label><input class="form-control" name="empresa_pais" maxlength="60" value="<?= e($c('empresa_pais')) ?>"></div>
      <div class="col-md-4"><label class="lbl">Teléfono</label><input class="form-control" name="empresa_telefono" maxlength="30" value="<?= e($c('empresa_telefono')) ?>"></div>
      <div class="col-md-12"><label class="lbl">Correo de contacto</label><input type="email" class="form-control" name="empresa_email" maxlength="120" value="<?= e($c('empresa_email')) ?>"></div>
    </div>
    <button class="btn btn-brand mt-4"><i class="bi bi-check2-circle me-1"></i>Guardar datos</button></form>

<?php elseif ($tab === 'moneda'): ?>
  <form method="post" action="<?= url('empresa/guardar') ?>" class="card-ui bk-card form-ui" id="fMon"><?= csrf_field() ?><input type="hidden" name="tab" value="moneda">
    <h5 class="mb-1">Moneda y formato de números</h5><p class="text-muted small">Elige una moneda para cargar sus valores habituales y ajusta lo que necesites. La vista previa se actualiza al instante.</p>
    <div class="row g-3">
      <div class="col-md-6"><label class="lbl">Moneda</label><select class="form-select" name="moneda_codigo" id="mCod"><?php foreach ($mon as $k => $m): ?><option value="<?= $k ?>" <?= $f['codigo'] === $k ? 'selected' : '' ?>><?= $k ?> · <?= e($m[0]) ?></option><?php endforeach; ?><?php if (!isset($mon[$f['codigo']])): ?><option value="<?= e($f['codigo']) ?>" selected><?= e($f['codigo']) ?> · personalizada</option><?php endif; ?></select></div>
      <div class="col-md-3"><label class="lbl">Símbolo</label><input class="form-control" name="moneda_simbolo" id="mSim" maxlength="6" value="<?= e($f['simbolo']) ?>" required></div>
      <div class="col-md-3"><label class="lbl">Decimales</label><select class="form-select" name="moneda_decimales" id="mDec"><?php foreach ([0, 1, 2, 3, 4] as $d): ?><option <?= $f['dec'] === $d ? 'selected' : '' ?>><?= $d ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="lbl">Separador decimal</label><select class="form-select" name="sep_decimal" id="mSd"><option value="." <?= $f['sd'] === '.' ? 'selected' : '' ?>>Punto ( 1234.56 )</option><option value="," <?= $f['sd'] === ',' ? 'selected' : '' ?>>Coma ( 1234,56 )</option></select></div>
      <div class="col-md-4"><label class="lbl">Separador de miles</label><select class="form-select" name="sep_miles" id="mSm"><?php foreach ([',' => 'Coma ( 1,234 )', '.' => 'Punto ( 1.234 )', 'space' => 'Espacio ( 1 234 )', "'" => "Apóstrofo ( 1'234 )", 'none' => 'Ninguno ( 1234 )'] as $k => $l): ?><option value="<?= e($k) ?>" <?= $sm === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="lbl">Posición del símbolo</label><select class="form-select" name="moneda_pos" id="mPos"><option value="antes" <?= $f['pos'] === 'antes' ? 'selected' : '' ?>>Antes ( S/ 100 )</option><option value="despues" <?= $f['pos'] === 'despues' ? 'selected' : '' ?>>Después ( 100 S/ )</option></select></div>
      <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="moneda_espacio" id="mEsp" value="1" <?= $f['espacio'] ? 'checked' : '' ?>><label class="form-check-label" for="mEsp">Dejar un espacio entre el símbolo y el número</label></div></div>
    </div>
    <div class="mon-prev mt-4"><div><small>Importe</small><b id="pv1">—</b></div><div><small>Número grande</small><b id="pv2">—</b></div><div><small>Cantidad</small><b id="pv3">—</b></div></div>
    <button class="btn btn-brand mt-4"><i class="bi bi-check2-circle me-1"></i>Guardar formato</button></form>

<?php elseif ($tab === 'regional'): ?>
  <form method="post" action="<?= url('empresa/guardar') ?>" class="card-ui bk-card form-ui"><?= csrf_field() ?><input type="hidden" name="tab" value="regional">
    <h5 class="mb-3">Regional y operación</h5>
    <div class="row g-3">
      <div class="col-md-6"><label class="lbl">Zona horaria</label><select class="form-select" name="zona_horaria"><?php $tz = $c('zona_horaria', date_default_timezone_get()); foreach ($zonas as $g => $l): ?><optgroup label="<?= e($g) ?>"><?php foreach ($l as $z): ?><option <?= $tz === $z ? 'selected' : '' ?>><?= e($z) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="lbl">Formato de fecha</label><select class="form-select" name="formato_fecha" id="rFec"><?php foreach (EmpresaController::FECHAS as $k => $ej): ?><option value="<?= e($k) ?>" <?= $c('formato_fecha', 'd/m/Y') === $k ? 'selected' : '' ?>><?= e($ej) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="lbl">Nombre del impuesto</label><input class="form-control" name="impuesto_nombre" maxlength="12" value="<?= e($c('impuesto_nombre', 'IGV')) ?>" placeholder="IGV / IVA"></div>
      <div class="col-md-4"><label class="lbl">Impuesto por defecto (%)</label><input type="number" step="0.01" min="0" class="form-control" name="igv" value="<?= e($c('igv', '18')) ?>"></div>
      <div class="col-md-4"><label class="lbl">Tarifa por hora de servicio</label><input type="number" step="0.01" min="0" class="form-control" name="tarifa_hora" value="<?= e($c('tarifa_hora', '45')) ?>"></div>
      <div class="col-12"><label class="lbl">Horario de atención</label><input class="form-control" name="horario_atencion" maxlength="120" value="<?= e($c('horario_atencion', 'Lun–Vie 8:00–18:00')) ?>"></div>
    </div>
    <button class="btn btn-brand mt-4"><i class="bi bi-check2-circle me-1"></i>Guardar</button></form>

<?php else: ?>
  <form method="post" action="<?= url('empresa/guardar') ?>" class="card-ui bk-card form-ui" id="fApa"><?= csrf_field() ?><input type="hidden" name="tab" value="apariencia">
    <h5 class="mb-1">Color de la marca</h5><p class="text-muted small">Cambia los acentos del sistema (botones, menú activo, gráficos).</p>
    <div class="col-swatches mb-4"><?php foreach (EmpresaController::COLORES as $k => [$l, $c1, $c2]): ?><label class="sw"><input type="radio" name="color_marca" value="<?= $k ?>" <?= $color === $k ? 'checked' : '' ?>><span style="background:linear-gradient(135deg,<?= $c1 ?>,<?= $c2 ?>)"><i class="bi bi-check-lg"></i></span><small><?= $l ?></small></label><?php endforeach; ?></div>
    <h5 class="mb-1">Pie de cotizaciones y facturas</h5><p class="text-muted small">Texto que aparece al final de los documentos impresos.</p>
    <textarea class="form-control" name="pie_documentos" rows="3" maxlength="300" placeholder="Gracias por su preferencia. Cuentas bancarias, condiciones de pago…"><?= e($c('pie_documentos')) ?></textarea>
    <button class="btn btn-brand mt-4"><i class="bi bi-check2-circle me-1"></i>Guardar</button></form>
<?php endif; ?>
</div>

<div class="col-xl-4"><div class="emp-prev card-ui" style="position:sticky;top:90px">
  <small class="text-muted text-uppercase fw-bold" style="letter-spacing:.06em;font-size:.68rem">Vista previa</small>
  <div class="emp-card mt-2"><div class="brand"><?= brand_mark() ?><span class="brand-text"><b id="pvNombre"><?= e(empresa_nombre()) ?></b><small id="pvEslogan"><?= e($c('empresa_eslogan', 'Soporte TI')) ?></small></span></div></div>
  <ul class="bk-list mt-3 mb-0">
    <li><span>Importe de ejemplo</span><b class="text-body" id="pvMoney"><?= e(money(1234567.891)) ?></b></li>
    <li><span>Fecha de ejemplo</span><b class="text-body" id="pvFecha"><?= e(fdate('2026-12-31')) ?></b></li>
    <li><span>Impuesto</span><b class="text-body"><?= e($c('impuesto_nombre', 'IGV')) ?> <?= e(num($c('igv', '18'), 0)) ?>%</b></li>
    <li><span>Zona horaria</span><b class="text-body"><?= e(date_default_timezone_get()) ?></b></li>
  </ul>
</div></div>
</div>
<?php
$monJs = json_encode(array_map(fn($m) => ['s' => $m[1], 'd' => $m[2], 'sd' => $m[3], 'sm' => $m[4] === ',' ? ',' : ($m[4] === '.' ? '.' : $m[4]), 'p' => $m[5]], $mon), JSON_UNESCAPED_UNICODE);
$scripts = '<script>window.MON_DEF=' . $monJs . ';</script><script src="' . asset('js/empresa.js') . '"></script>'; ?>

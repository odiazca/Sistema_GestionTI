<?php
$est = ['manual' => ['info', 'bi-hand-index-thumb'], 'auto' => ['success', 'bi-clock-history'], 'previo' => ['warning', 'bi-shield-exclamation'], 'reinicio' => ['danger', 'bi-arrow-repeat']];
$edad = $ultimo ? (time() - strtotime($ultimo['fecha'])) / 86400 : null;
$salud = $ultimo === null ? ['danger', 'Sin copias de seguridad', 'bi-shield-x'] : ($edad > 7 ? ['warning', 'Última copia hace ' . hace($ultimo['fecha']), 'bi-shield-exclamation'] : ['success', 'Protegido · última copia ' . hace($ultimo['fecha']), 'bi-shield-check']);
$totMov = array_sum($mov); $totMae = array_sum($mae);
?>
<div class="bk-hero bk-<?= $salud[0] ?>">
  <div class="bk-hero-ic"><i class="bi <?= $salud[2] ?>"></i></div>
  <div class="flex-grow-1"><small>Respaldo y restauración</small><h2><?= e($salud[1]) ?></h2>
    <div class="bk-chips"><span><i class="bi bi-database"></i><?= $mb ?> MB de datos</span><span><i class="bi bi-table"></i><?= $tablas ?> tablas</span><span><i class="bi bi-list-ul"></i>~<?= num($filas, 0) ?> registros</span><span><i class="bi bi-archive"></i><?= count($lista) ?> copias guardadas</span><?php if ($auto !== 'no'): ?><span><i class="bi bi-clock-history"></i>Automático <?= e($auto) ?></span><?php endif; ?></div></div>
</div>

<div class="seg mb-3">
  <a class="<?= $tab === 'copias' ? 'on' : '' ?>" href="<?= url('respaldo') ?>"><i class="bi bi-cloud-arrow-down"></i>Copias de seguridad</a>
  <a class="<?= $tab === 'restaurar' ? 'on' : '' ?>" href="<?= url('respaldo?tab=restaurar') ?>"><i class="bi bi-arrow-counterclockwise"></i>Restaurar</a>
  <a class="<?= $tab === 'reiniciar' ? 'on danger' : 'danger-h' ?>" href="<?= url('respaldo?tab=reiniciar') ?>"><i class="bi bi-arrow-repeat"></i>Reiniciar sistema</a>
</div>

<?php if ($tab === 'copias'): ?>
<div class="row g-3 mb-3">
  <div class="col-lg-7"><div class="card-ui bk-card h-100">
    <div class="d-flex gap-3 align-items-start"><span class="bk-ic" style="--c:#4f46e5"><i class="bi bi-cloud-arrow-down-fill"></i></span><div><h5>Crear copia de seguridad</h5><p class="text-muted small mb-0">Guarda todos los datos del sistema (clientes, tickets, facturación, CRM, usuarios y configuración, incluido el logo) en un solo archivo.</p></div></div>
    <form method="post" action="<?= url('respaldo/crear') ?>" class="mt-3"><?= csrf_field() ?>
      <?php if ($zlib): ?><div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="comprimir" id="cmp" value="1" checked><label class="form-check-label" for="cmp">Comprimir (.sql.gz) — ocupa mucho menos</label></div><?php endif; ?>
      <div class="d-flex gap-2 flex-wrap"><button class="btn btn-brand"><i class="bi bi-hdd me-1"></i>Crear y guardar en el servidor</button><button class="btn btn-soft" name="descargar" value="1"><i class="bi bi-download me-1"></i>Crear y descargar</button></div>
    </form></div></div>
  <div class="col-lg-5"><div class="card-ui bk-card h-100">
    <div class="d-flex gap-3 align-items-start"><span class="bk-ic" style="--c:#10b981"><i class="bi bi-clock-history"></i></span><div><h5>Respaldo automático</h5><p class="text-muted small mb-0">Se crea solo al entrar el administrador al Dashboard.</p></div></div>
    <form method="post" action="<?= url('respaldo/auto') ?>" class="form-ui mt-3"><?= csrf_field() ?>
      <div class="row g-2"><div class="col-7"><label class="lbl">Frecuencia</label><select class="form-select" name="modo"><?php foreach (['no' => 'Desactivado', 'diario' => 'Diario', 'semanal' => 'Semanal'] as $k => $l): ?><option value="<?= $k ?>" <?= $auto === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="col-5"><label class="lbl">Conservar</label><input type="number" min="1" max="60" class="form-control" name="retencion" value="<?= $ret ?>"></div></div>
      <button class="btn btn-soft mt-3"><i class="bi bi-check2 me-1"></i>Guardar</button></form></div></div>
</div>

<div class="card-ui">
  <div class="card-head"><div><h6>Copias guardadas en el servidor</h6><small>Se guardan en <code>storage/respaldos</code>, carpeta protegida contra acceso web</small></div></div>
  <div class="table-responsive"><table class="table table-ui align-middle mb-0"><thead><tr><th>Archivo</th><th>Tipo</th><th>Fecha</th><th>Tamaño</th><th></th></tr></thead><tbody>
  <?php foreach ($lista as $r): [$c, $ic] = $est[$r['tipo']] ?? ['secondary', 'bi-file-earmark']; ?>
    <tr><td><i class="bi bi-file-earmark-zip text-muted me-1"></i><span class="small fw-semibold"><?= e($r['nombre']) ?></span></td><td><span class="pill pill-<?= $c ?>"><i class="bi <?= $ic ?> me-1"></i><?= e(Respaldo::TIPOS[$r['tipo']] ?? $r['tipo']) ?></span></td>
      <td class="small text-nowrap"><?= fdate($r['fecha'], 'd/m/Y H:i') ?><div class="text-muted"><?= e(hace($r['fecha'])) ?></div></td><td class="small"><?= Respaldo::tamano($r['tamano']) ?></td>
      <td class="text-end text-nowrap"><a class="btn btn-sm btn-ghost" href="<?= url('respaldo/descargar/' . $r['nombre']) ?>" title="Descargar"><i class="bi bi-download"></i></a>
        <a class="btn btn-sm btn-soft" href="<?= url('respaldo?tab=restaurar&usar=' . urlencode($r['nombre'])) ?>" title="Restaurar esta copia"><i class="bi bi-arrow-counterclockwise"></i></a>
        <form method="post" action="<?= url('respaldo/eliminar/' . $r['nombre']) ?>" class="d-inline" onsubmit="return confirm('¿Eliminar esta copia?')"><?= csrf_field() ?><button class="btn btn-sm btn-ghost text-danger" title="Eliminar"><i class="bi bi-trash3"></i></button></form></td></tr>
  <?php endforeach; if (!$lista): ?><tr><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-1"></i>Aún no hay copias. Crea la primera arriba.</td></tr><?php endif; ?></tbody></table></div>
</div>

<?php elseif ($tab === 'restaurar'): $usar = $_GET['usar'] ?? ''; ?>
<div class="row g-3"><div class="col-lg-8">
  <form method="post" action="<?= url('respaldo/restaurar') ?>" enctype="multipart/form-data" class="card-ui bk-card form-ui" id="fRest"><?= csrf_field() ?>
    <div class="d-flex gap-3 align-items-start mb-3"><span class="bk-ic" style="--c:#f59e0b"><i class="bi bi-arrow-counterclockwise"></i></span><div><h5>Restaurar una copia de seguridad</h5><p class="text-muted small mb-0">Reemplaza los datos actuales por los de la copia elegida.</p></div></div>
    <div class="bk-step"><b>1</b><div class="flex-grow-1"><label class="lbl">Elige la copia</label>
      <select class="form-select mb-2" name="servidor"><option value="">— Copias guardadas en el servidor —</option><?php foreach ($lista as $r): ?><option value="<?= e($r['nombre']) ?>" <?= $usar === $r['nombre'] ? 'selected' : '' ?>><?= e($r['nombre']) ?> · <?= Respaldo::tamano($r['tamano']) ?></option><?php endforeach; ?></select>
      <div class="text-center text-muted small my-1">o sube un archivo</div>
      <input type="file" class="form-control" name="archivo" accept=".sql,.gz"><div class="form-text">Archivos .sql o .sql.gz creados por este sistema. Límite de subida del servidor: <?= Respaldo::tamano($maxSubida) ?>; si tu copia es mayor, cópiala a <code>storage/respaldos</code>.</div></div></div>
    <div class="bk-step"><b>2</b><div class="flex-grow-1"><div class="bk-warn"><i class="bi bi-exclamation-triangle-fill"></i><div><b>Se reemplazarán todos los datos actuales.</b> Antes de restaurar se crea automáticamente una copia «Antes de restaurar»; si algo falla, el sistema vuelve solo al estado anterior. Al terminar tendrás que iniciar sesión otra vez.</div></div></div></div>
    <div class="bk-step"><b>3</b><div class="flex-grow-1"><div class="row g-2"><div class="col-md-6"><label class="lbl">Escribe <code>RESTAURAR</code></label><input class="form-control" name="frase" autocomplete="off" id="fraseR"></div><div class="col-md-6"><label class="lbl">Tu contraseña</label><input type="password" class="form-control" name="clave" autocomplete="current-password"></div></div></div></div>
    <button class="btn btn-warning fw-bold mt-2" id="btnR" disabled><i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar ahora</button>
  </form></div>
  <div class="col-lg-4"><div class="card-ui p-3 small text-muted"><h6 class="fw-bold text-body"><i class="bi bi-lightbulb me-1 text-warning"></i>Recomendaciones</h6><ul class="mb-0 ps-3"><li class="mb-2">Restaura en horarios sin uso: los demás usuarios verán datos del momento de la copia.</li><li class="mb-2">Una copia de una versión anterior del sistema se actualiza sola al restaurar.</li><li>Guarda tus copias también fuera del servidor (descárgalas).</li></ul></div></div>
</div>
<?php $scripts = '<script>(function(){var f=document.getElementById("fraseR"),b=document.getElementById("btnR");f.addEventListener("input",function(){b.disabled=f.value.trim()!=="RESTAURAR";});document.getElementById("fRest").addEventListener("submit",function(e){if(!confirm("Se reemplazarán todos los datos actuales. ¿Continuar?"))e.preventDefault();else{b.disabled=true;b.innerHTML="<span class=\"spinner-border spinner-border-sm me-1\"></span>Restaurando…";}});})();</script>'; ?>

<?php else: ?>
<form method="post" action="<?= url('respaldo/reiniciar') ?>" id="fRei"><?= csrf_field() ?>
<div class="bk-danger-head"><i class="bi bi-exclamation-octagon-fill"></i><div><h5>Zona de peligro</h5><p>Estas acciones eliminan datos de forma permanente. Siempre se crea antes una copia de seguridad que podrás restaurar.</p></div></div>
<div class="row g-3 mb-3">
  <div class="col-lg-6"><label class="bk-opt"><input type="radio" name="modo" value="movimientos" checked><div class="card-ui bk-card h-100">
    <div class="d-flex gap-3"><span class="bk-ic" style="--c:#f59e0b"><i class="bi bi-eraser-fill"></i></span><div><h5>Limpiar datos de movimiento</h5><p class="small text-muted">Borra la operación diaria y conserva clientes, equipos, contratos, repuestos, usuarios y configuración. Útil al terminar pruebas o cerrar un periodo.</p></div></div>
    <ul class="bk-list"><?php foreach ($mov as $g => $n): ?><li><span><?= e($g) ?></span><b><?= num($n, 0) ?></b></li><?php endforeach; ?></ul></div></label></div>
  <div class="col-lg-6"><label class="bk-opt"><input type="radio" name="modo" value="completo"><div class="card-ui bk-card h-100">
    <div class="d-flex gap-3"><span class="bk-ic" style="--c:#ef4444"><i class="bi bi-building-fill-gear"></i></span><div><h5>Nueva empresa (reinicio completo)</h5><p class="small text-muted">Además de lo anterior borra clientes, contactos, equipos, contratos y repuestos. Deja el sistema listo para configurar otra empresa; tu usuario administrador se conserva.</p></div></div>
    <ul class="bk-list"><?php foreach ($mov as $g => $n): ?><li><span><?= e($g) ?></span><b><?= num($n, 0) ?></b></li><?php endforeach; ?><?php foreach ($mae as $g => $n): ?><li><span><?= e($g) ?></span><b><?= num($n, 0) ?></b></li><?php endforeach; ?></ul>
    <div id="optsC" class="bk-keep"><div class="small fw-bold mb-1">Conservar:</div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="c_usuarios" id="cu" value="1" checked><label class="form-check-label" for="cu">Usuarios del equipo (<?= max(0, $usuariosInternos) ?> además de ti)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="c_catalogos" id="cc" value="1" checked><label class="form-check-label" for="cc">Categorías y base de conocimiento (<?= num(array_sum($cat), 0) ?>)</label></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" name="c_empresa" id="ce" value="1"><label class="form-check-label" for="ce">Datos y logo de la empresa actual</label></div></div>
  </div></label></div>
</div>
<div class="card-ui bk-card" style="border-color:#fecaca">
  <div class="row g-3 align-items-end"><div class="col-md-4"><label class="lbl">Escribe <code>REINICIAR</code></label><input class="form-control" name="frase" id="fraseX" autocomplete="off"></div>
    <div class="col-md-4"><label class="lbl">Tu contraseña</label><input type="password" class="form-control" name="clave" autocomplete="current-password"></div>
    <div class="col-md-4"><button class="btn btn-danger fw-bold w-100" id="btnX" disabled><i class="bi bi-trash3 me-1"></i>Eliminar datos</button></div></div>
</div></form>
<?php $scripts = '<script>(function(){var f=document.getElementById("fraseX"),b=document.getElementById("btnX"),fm=document.getElementById("fRei"),o=document.getElementById("optsC");function r(){var c=fm.modo.value==="completo";o.style.display=c?"":"none";document.querySelectorAll(".bk-opt").forEach(function(l){l.classList.toggle("sel",l.querySelector("input").checked);});}f.addEventListener("input",function(){b.disabled=f.value.trim()!=="REINICIAR";});fm.addEventListener("change",r);r();fm.addEventListener("submit",function(e){if(!confirm("Esta acción elimina datos de forma permanente. ¿Continuar?"))e.preventDefault();else{b.disabled=true;}});})();</script>'; ?>
<?php endif; ?>

<?php
$val = fn($k) => $row[$k] ?? '';
$err = fn($k) => isset($errors[$k]) ? '<div class="err"><i class="bi bi-exclamation-circle"></i> ' . e($errors[$k]) . '</div>' : '';
$inv = fn($k) => isset($errors[$k]) ? 'is-invalid' : '';
?>
<div class="page-head"><div>
  <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('leads') ?>">Leads</a> / <?= $id ? 'Editar' : 'Nuevo' ?></div>
  <h1><?= $id ? 'Editar lead' : 'Nuevo lead' ?></h1></div></div>
<form method="post" class="form-ui" novalidate><?= csrf_field() ?>
  <div class="row g-3"><div class="col-xl-8"><div class="card-ui">
    <div class="form-section" style="margin-top:0">Empresa y contacto</div>
    <div class="row g-3">
      <div class="col-md-8"><label class="lbl">Empresa / prospecto <span class="req">*</span></label><input class="form-control <?= $inv('empresa') ?>" name="empresa" maxlength="150" value="<?= e($val('empresa')) ?>" placeholder="Ej: Clínica Santa Elena"><?= $err('empresa') ?></div>
      <div class="col-md-4"><label class="lbl">Rubro</label><input class="form-control" name="rubro" maxlength="80" value="<?= e($val('rubro')) ?>" list="rubros"><datalist id="rubros"><?php foreach (['Salud', 'Comercio', 'Educación', 'Servicios', 'Construcción', 'Turismo', 'Transporte', 'Retail', 'Industria', 'Tecnología'] as $r): ?><option value="<?= $r ?>"><?php endforeach; ?></datalist></div>
      <div class="col-md-6"><label class="lbl">Persona de contacto</label><input class="form-control" name="contacto" maxlength="100" value="<?= e($val('contacto')) ?>"></div>
      <div class="col-md-6"><label class="lbl">Cargo</label><input class="form-control" name="cargo" maxlength="80" value="<?= e($val('cargo')) ?>" placeholder="Gerente, Jefe de TI…"><div class="help">Los cargos de decisión suben el puntaje del lead.</div></div>
      <div class="col-md-6"><label class="lbl">Teléfono</label><input class="form-control <?= $inv('telefono') ?>" name="telefono" maxlength="25" value="<?= e($val('telefono')) ?>"><?= $err('telefono') ?></div>
      <div class="col-md-6"><label class="lbl">Correo</label><input type="email" class="form-control <?= $inv('email') ?>" name="email" maxlength="120" value="<?= e($val('email')) ?>"><?= $err('email') ?></div>
    </div>
    <div class="form-section">Interés y notas</div>
    <div class="row g-3">
      <div class="col-12"><label class="lbl">¿Qué necesita?</label><input class="form-control" name="interes" maxlength="200" value="<?= e($val('interes')) ?>" placeholder="Ej: Soporte 24/7 y migración de servidores"></div>
      <div class="col-12"><label class="lbl">Notas</label><textarea class="form-control" name="notas" rows="3" maxlength="2000"><?= e($val('notas')) ?></textarea></div>
    </div>
  </div></div>
  <div class="col-xl-4"><div class="card-ui">
    <div class="form-section" style="margin-top:0">Gestión</div>
    <div class="mb-3"><label class="lbl">Origen <span class="req">*</span></label><select class="form-select <?= $inv('origen') ?>" name="origen"><?php foreach (LEAD_ORIGENES as $k => $v): ?><option value="<?= $k ?>" <?= $val('origen') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select><?= $err('origen') ?></div>
    <div class="mb-3"><label class="lbl">Responsable</label><select class="form-select" name="responsable_id"><?php foreach ($vendedores as $v): ?><option value="<?= $v['id'] ?>" <?= (int)$val('responsable_id') === (int)$v['id'] ? 'selected' : '' ?>><?= e($v['nombre']) ?></option><?php endforeach; ?></select></div>
    <div class="alert alert-light border small"><i class="bi bi-stars me-1 text-primary"></i>El <b>score</b> se calcula solo: origen, datos de contacto, cargo, interés y actividades realizadas.</div>
    <div class="d-grid gap-2"><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i><?= $id ? 'Guardar cambios' : 'Registrar lead' ?></button><a class="btn btn-ghost" href="<?= url($id ? 'leads/ver/' . $id : 'leads') ?>">Cancelar</a></div>
  </div></div></div>
</form>

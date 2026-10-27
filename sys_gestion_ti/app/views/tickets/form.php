<?php
$val = fn($k) => $row[$k] ?? '';
$err = fn($k) => isset($errors[$k]) ? '<div class="err"><i class="bi bi-exclamation-circle"></i> ' . e($errors[$k]) . '</div>' : '';
?>
<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('tickets') ?>">Tickets</a> / <?= $id ? 'Editar' : 'Nuevo' ?></div>
    <h1><?= $id ? 'Editar ticket ' . e($row['codigo'] ?? '') : 'Nuevo ticket' ?></h1>
  </div>
</div>

<form method="post" class="form-ui" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-xl-8">
      <div class="card-ui">
        <div class="form-section" style="margin-top:0">¿Quién reporta?</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="lbl" for="cliente_id">Cliente <span class="req">*</span></label>
            <select class="form-select <?= isset($errors['cliente_id']) ? 'is-invalid' : '' ?>" name="cliente_id" id="cliente_id">
              <option value="">— Seleccionar —</option>
              <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>" <?= (string)$val('cliente_id') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['razon_social']) ?></option><?php endforeach; ?>
            </select><?= $err('cliente_id') ?>
          </div>
          <div class="col-md-6">
            <label class="lbl" for="activo_id">Equipo afectado</label>
            <select class="form-select" name="activo_id" id="activo_id">
              <option value="">— Ninguno / no aplica —</option>
              <?php foreach ($activos as $a): ?><option value="<?= $a['id'] ?>" <?= (string)$val('activo_id') === (string)$a['id'] ? 'selected' : '' ?>><?= e($a['codigo'] . ' · ' . $a['tipo'] . ' ' . $a['marca'] . ' ' . $a['modelo']) ?></option><?php endforeach; ?>
            </select>
            <div class="help">Se cargan los equipos del cliente seleccionado.</div>
          </div>
        </div>

        <div class="form-section">El problema</div>
        <div class="row g-3">
          <div class="col-12">
            <label class="lbl" for="asunto">Asunto <span class="req">*</span></label>
            <input class="form-control <?= isset($errors['asunto']) ? 'is-invalid' : '' ?>" name="asunto" id="asunto" maxlength="180" value="<?= e($val('asunto')) ?>" placeholder="Ej: No hay internet en el segundo piso"><?= $err('asunto') ?>
          </div>
          <div class="col-12">
            <label class="lbl" for="descripcion">Descripción detallada</label>
            <textarea class="form-control" rows="5" name="descripcion" id="descripcion" placeholder="¿Qué ocurre? ¿Desde cuándo? ¿Qué se intentó hasta ahora?"><?= e($val('descripcion')) ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="card-ui">
        <div class="form-section" style="margin-top:0">Clasificación</div>
        <div class="mb-3">
          <label class="lbl" for="categoria_id">Categoría <span class="req">*</span></label>
          <select class="form-select <?= isset($errors['categoria_id']) ? 'is-invalid' : '' ?>" name="categoria_id" id="categoria_id"><option value="">— Seleccionar —</option>
            <?php foreach ($categorias as $c): ?><option value="<?= $c['id'] ?>" <?= (string)$val('categoria_id') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select><?= $err('categoria_id') ?>
        </div>
        <div class="mb-3">
          <label class="lbl" for="canal">Canal de ingreso</label>
          <select class="form-select" name="canal" id="canal"><?php foreach (CANALES as $k => $v): ?><option value="<?= $k ?>" <?= $val('canal') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
        </div>
        <?php if (!$id): ?>
        <div class="mb-3">
          <label class="lbl" for="prioridad">Prioridad</label>
          <select class="form-select" name="prioridad" id="prioridad"><?php foreach (PRIORIDADES as $k => $v): ?><option value="<?= $k ?>" <?= $val('prioridad') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
        </div>
        <div class="mb-3">
          <label class="lbl" for="tecnico_id">Asignar a</label>
          <select class="form-select" name="tecnico_id" id="tecnico_id"><option value="">Sin asignar</option>
            <?php foreach ($tecnicos as $c): ?><option value="<?= $c['id'] ?>" <?= (string)$val('tecnico_id') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="sla-box ok" id="slaPrev">
          <div class="top"><span>Plazo de resolución estimado</span><i class="bi bi-shield-check"></i></div>
          <div class="time" id="slaTime">—</div>
          <small class="text-muted" id="slaNote">Según el contrato del cliente y la prioridad.</small>
        </div>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2 mt-3">
        <a href="<?= $id ? url('tickets/ver/' . $id) : url('tickets') ?>" class="btn btn-ghost flex-fill">Cancelar</a>
        <button class="btn btn-brand flex-fill"><i class="bi bi-check2-circle me-1"></i><?= $id ? 'Guardar' : 'Crear ticket' ?></button>
      </div>
    </div>
  </div>
</form>

<?php
$scripts = '<script>window.TK = {base:' . (int)$sla_base . ',factor:' . json_encode(Ticket::FACTOR) . ',urlActivos:' . json_encode(url('tickets/activos/')) . '};</script>'
         . '<script src="' . asset('js/ticket-form.js') . '"></script>';
?>

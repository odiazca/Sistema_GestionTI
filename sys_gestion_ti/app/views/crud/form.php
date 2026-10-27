<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url($c['slug']) ?>"><?= e($c['title']) ?></a> / <?= $id ? 'Editar' : 'Nuevo' ?></div>
    <h1><?= $id ? 'Editar' : 'Nuevo' ?> <?= e(mb_strtolower($c['singular'])) ?></h1>
  </div>
</div>

<form method="post" class="form-ui" novalidate>
  <?= csrf_field() ?>
  <div class="card-ui">
    <?php $sec = null; ?>
    <div class="row g-3">
    <?php foreach ($fields as $f):
        $n = $f['name']; $v = $row[$n] ?? ''; $err = $errors[$n] ?? null;
        if (!empty($f['section']) && $f['section'] !== $sec): $sec = $f['section']; ?>
          <div class="col-12"><div class="form-section"><?= e($sec) ?></div></div>
        <?php endif; ?>
      <div class="col-md-<?= (int)($f['col'] ?? 6) ?>">
        <?php if ($f['type'] === 'switch'): ?>
          <div class="form-check form-switch mt-4 pt-1">
            <input class="form-check-input" type="checkbox" role="switch" name="<?= e($n) ?>" id="f_<?= e($n) ?>" <?= $v ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="f_<?= e($n) ?>"><?= e($f['label']) ?></label>
          </div>
        <?php else: ?>
          <label class="lbl" for="f_<?= e($n) ?>"><?= e($f['label']) ?><?= !empty($f['required']) ? ' <span class="req">*</span>' : '' ?></label>
          <?php if ($f['type'] === 'select'): ?>
            <select class="form-select <?= $err ? 'is-invalid' : '' ?>" name="<?= e($n) ?>" id="f_<?= e($n) ?>">
              <option value="">— Seleccionar —</option>
              <?php foreach ($f['opts'] as $k => $lab): ?><option value="<?= e($k) ?>" <?= (string)$v === (string)$k ? 'selected' : '' ?>><?= e($lab) ?></option><?php endforeach; ?>
            </select>
          <?php elseif ($f['type'] === 'textarea'): ?>
            <textarea class="form-control <?= $err ? 'is-invalid' : '' ?>" rows="<?= (int)($f['rows'] ?? 3) ?>" name="<?= e($n) ?>" id="f_<?= e($n) ?>"><?= e($v) ?></textarea>
          <?php elseif ($f['type'] === 'color'): ?>
            <input type="color" class="form-control form-control-color w-100 <?= $err ? 'is-invalid' : '' ?>" style="height:44px;padding:4px" name="<?= e($n) ?>" id="f_<?= e($n) ?>" value="<?= e($v ?: '#4f46e5') ?>">
          <?php else: ?>
            <input class="form-control <?= $err ? 'is-invalid' : '' ?>" type="<?= e($f['type']) ?>" name="<?= e($n) ?>" id="f_<?= e($n) ?>" value="<?= e($v) ?>"
              <?= isset($f['placeholder']) ? 'placeholder="' . e($f['placeholder']) . '"' : '' ?> <?= $f['type'] === 'number' ? 'step="' . e($f['step'] ?? '1') . '"' : '' ?>
              <?= !empty($f['maxlength']) ? 'maxlength="' . (int)$f['maxlength'] . '"' : '' ?> <?= $f['type'] === 'password' ? 'autocomplete="new-password"' : '' ?>>
          <?php endif; ?>
          <?php if ($err): ?><div class="err"><i class="bi bi-exclamation-circle"></i> <?= e($err) ?></div><?php endif; ?>
          <?php if (!empty($f['help'])): ?><div class="help"><?= e($f['help']) ?></div><?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>
    <div class="d-flex gap-2 justify-content-end mt-4 pt-3" style="border-top:1px solid var(--line)">
      <a href="<?= url($c['slug']) ?>" class="btn btn-ghost px-4">Cancelar</a>
      <button class="btn btn-brand px-4"><i class="bi bi-check2-circle me-1"></i><?= $id ? 'Guardar cambios' : 'Registrar' ?></button>
    </div>
  </div>
</form>

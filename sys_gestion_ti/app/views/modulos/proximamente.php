<div class="card-ui module-hero">
  <div class="empty-icon"><i class="bi <?= e($mod['icon']) ?>"></i></div>
  <span class="chip chip-soft mb-2"><i class="bi bi-hammer me-1"></i>Módulo en desarrollo</span>
  <h2 class="fw-bold mb-2"><?= e($mod['label']) ?></h2>
  <p class="text-muted mx-auto" style="max-width:520px"><?= e($mod['desc']) ?></p>
  <a href="<?= BASE_URL ?>/dashboard" class="btn btn-brand mt-2"><i class="bi bi-arrow-left me-1"></i>Volver al dashboard</a>
</div>
<?php if (!empty($mod['features'])): ?>
<h6 class="section-title mt-4 mb-3">Funcionalidades planificadas</h6>
<div class="row g-3">
  <?php foreach ($mod['features'] as $f): ?>
  <div class="col-md-6 col-xl-4"><div class="card-ui feature-item"><i class="bi bi-check2-circle"></i><span><?= e($f) ?></span></div></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

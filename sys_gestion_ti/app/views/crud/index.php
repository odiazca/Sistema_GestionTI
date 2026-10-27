<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <?= e($c['group'] ?? 'Gestión') ?></div>
    <h1><?= e($c['title']) ?></h1>
    <p><?= e($c['subtitle'] ?? '') ?></p>
  </div>
  <a href="<?= url($c['slug'] . '/crear') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Nuevo <?= e(mb_strtolower($c['singular'])) ?></a>
</div>

<?php if ($stats): ?>
<div class="row g-3 mb-3">
  <?php foreach ($stats as $s): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini">
    <span class="ic" style="background:<?= e($s['color']) ?>1f;color:<?= e($s['color']) ?>"><i class="bi <?= e($s['icon']) ?>"></i></span>
    <div><b><?= $s['fmt'] === 'money' ? money($s['value']) : e($s['value']) ?></b><span><?= e($s['label']) ?></span></div>
  </div></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card-ui">
  <form method="get" class="filter-bar">
    <div class="search-ui"><i class="bi bi-search"></i><input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar…"></div>
    <?php foreach ($filters as $f): ?>
      <select name="<?= e($f['name']) ?>" class="select-ui" onchange="this.form.submit()">
        <option value=""><?= e($f['label']) ?>: todos</option>
        <?php foreach ($f['opts'] as $k => $v): ?><option value="<?= e($k) ?>" <?= ((string)($_GET[$f['name']] ?? '') === (string)$k) ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
      </select>
    <?php endforeach; ?>
    <button class="btn btn-soft" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    <?php if ($q !== '' || array_filter(array_map(fn($f) => $_GET[$f['name']] ?? '', $filters))): ?>
      <a href="<?= url($c['slug']) ?>" class="btn btn-ghost">Limpiar</a>
    <?php endif; ?>
  </form>

  <div class="table-responsive">
    <table class="table-ui">
      <thead><tr>
        <?php foreach ($c['columns'] as $col): ?><th><?= e($col['label']) ?></th><?php endforeach; ?>
        <th style="width:1%"></th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <?php foreach ($c['columns'] as $col): ?>
            <td class="<?= !empty($col['main']) ? 'cell-main' : '' ?>">
              <?php
              if (isset($col['html'])) echo ($col['html'])($r);
              elseif (($col['type'] ?? '') === 'date') echo e(fdate($r[$col['key']] ?? null));
              elseif (($col['type'] ?? '') === 'money') echo e(money($r[$col['key']] ?? 0));
              elseif (!empty($col['main'])) echo '<b>' . e($r[$col['key']] ?? '') . '</b>' . (!empty($col['sub']) ? '<small>' . e($r[$col['sub']] ?? '') . '</small>' : '');
              else echo e($r[$col['key']] ?? '—');
              ?>
            </td>
          <?php endforeach; ?>
          <td>
            <div class="row-actions">
              <?php if (!empty($c['view'])): ?><a class="act-btn" href="<?= url($c['slug'] . '/ver/' . $r['id']) ?>" title="Ver ficha"><i class="bi bi-eye"></i></a><?php endif; ?>
              <?php if (isset($c['extra_actions'])) echo ($c['extra_actions'])($r); ?>
              <a class="act-btn" href="<?= url($c['slug'] . '/editar/' . $r['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
              <button type="button" class="act-btn danger" title="Eliminar" data-delete="<?= url($c['slug'] . '/eliminar/' . $r['id']) ?>" data-name="<?= e($r[$c['label_field'] ?? 'nombre'] ?? $r['razon_social'] ?? $r['codigo'] ?? '') ?>"><i class="bi bi-trash3"></i></button>
            </div>
          </td>
        </tr>
      <?php endforeach; if (!$rows): ?>
        <tr><td colspan="<?= count($c['columns']) + 1 ?>" class="empty-row"><i class="bi bi-inbox"></i>No se encontraron registros.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pager($pg) ?>
</div>

<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <form class="modal-content p-4 text-center" method="post">
      <?= csrf_field() ?>
      <div class="empty-icon" style="width:64px;height:64px;font-size:1.7rem;background:linear-gradient(135deg,#ef4444,#f97316)"><i class="bi bi-trash3"></i></div>
      <h5 class="fw-bold">¿Eliminar registro?</h5>
      <p class="text-muted small">Se eliminará <b class="del-name"></b>. Esta acción no se puede deshacer.</p>
      <div class="d-flex gap-2"><button type="button" class="btn btn-ghost flex-fill" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger flex-fill" style="border-radius:12px;font-weight:600">Eliminar</button></div>
    </form>
  </div>
</div>

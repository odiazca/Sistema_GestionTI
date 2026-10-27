<?php $err = count($res) - $ok; ?>
<div class="page-head"><div><div class="crumbs"><a href="<?= url('importar') ?>">Importar</a> / <?= e($def[0]) ?></div><h1>Vista previa</h1><p><?= e($nombre) ?></p></div>
  <div class="d-flex gap-2"><a class="btn btn-ghost" href="<?= url('importar') ?>">Cancelar</a>
  <?php if ($ok): ?><form method="post" action="<?= url('importar/confirmar') ?>"><?= csrf_field() ?><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Importar <?= $ok ?> registros</button></form><?php endif; ?></div></div>
<div class="row g-3 mb-3">
  <div class="col-4"><div class="stat-mini"><div><b><?= count($res) ?></b><span>Filas leídas</span></div></div></div>
  <div class="col-4"><div class="stat-mini"><div><b class="text-success"><?= $ok ?></b><span>Listas para importar</span></div></div></div>
  <div class="col-4"><div class="stat-mini"><div><b class="<?= $err ? 'text-danger' : '' ?>"><?= $err ?></b><span>Con observaciones (se omiten)</span></div></div></div>
</div>
<div class="card-ui"><div class="table-responsive" style="max-height:560px"><table class="table table-ui align-middle mb-0">
  <thead><tr><th>Fila</th><th>Estado</th><?php foreach ($cols as $c): ?><th><?= e($def[3][$c][0]) ?></th><?php endforeach; ?></tr></thead><tbody>
  <?php foreach (array_slice($res, 0, 300) as $r): ?>
  <tr class="<?= $r['err'] ? 'table-danger-soft' : '' ?>"><td class="text-muted"><?= $r['n'] ?></td>
    <td><?= $r['err'] ? '<span class="pill pill-danger" title="' . e(implode('; ', $r['err'])) . '">' . e(implode('; ', $r['err'])) . '</span>' : '<span class="pill pill-success">OK</span>' ?></td>
    <?php foreach ($cols as $c): ?><td class="small"><?= e((string)($r['d'][$c] ?? '')) ?></td><?php endforeach; ?></tr>
  <?php endforeach; ?></tbody></table></div>
  <?php if (count($res) > 300): ?><div class="p-3 small text-muted text-center">Mostrando las primeras 300 filas de <?= count($res) ?>. Se importarán todas las válidas.</div><?php endif; ?></div>

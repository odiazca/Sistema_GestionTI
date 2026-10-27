<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('conocimiento') ?>">Base de conocimiento</a> / Artículo</div>
    <?php if ($a['categoria']): ?><span class="chip chip-soft mb-2"><i class="dot me-1" style="background:<?= e($a['cat_color']) ?>"></i><?= e($a['categoria']) ?></span><?php endif; ?>
    <h1 style="max-width:760px"><?= e($a['titulo']) ?></h1>
    <p>Por <?= e($a['autor'] ?: 'Equipo de soporte') ?> · <?= fdate($a['created_at']) ?> · <?= (int)$a['vistas'] + 1 ?> consultas</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-ghost" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
    <a href="<?= url('conocimiento/editar/' . $a['id']) ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Editar</a>
  </div>
</div>
<div class="row g-3">
  <div class="col-xl-8"><div class="card-ui"><div class="desc-box" style="background:none;padding:.3rem;font-size:.97rem;line-height:1.8"><?= e($a['contenido']) ?></div></div></div>
  <div class="col-xl-4"><div class="card-ui">
    <div class="card-head"><div><h6>Artículos relacionados</h6></div></div>
    <?php foreach ($rel as $r): ?><div class="alert-row"><a href="<?= url('conocimiento/ver/' . $r['id']) ?>" class="fw-semibold" style="color:var(--ink);font-size:.88rem"><?= e($r['titulo']) ?></a><span class="text-muted small"><i class="bi bi-eye"></i> <?= (int)$r['vistas'] ?></span></div><?php endforeach; ?>
    <?php if (!$rel) echo '<p class="text-muted small m-0">No hay más artículos en esta categoría.</p>'; ?>
  </div></div>
</div>

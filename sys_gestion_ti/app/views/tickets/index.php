<?php
$chips = ['' => ['Todos', $conteos['todos']], 'abierto' => ['Abiertos', $conteos['abierto'] ?? 0], 'en_proceso' => ['En proceso', $conteos['en_proceso'] ?? 0],
          'pendiente' => ['Pendientes', $conteos['pendiente'] ?? 0], 'resuelto' => ['Resueltos', $conteos['resuelto'] ?? 0], 'cerrado' => ['Cerrados', $conteos['cerrado'] ?? 0]];
$hay = $f['q'] !== '' || $f['estado'] !== '' || $f['prioridad'] !== '' || $f['categoria'] !== '' || $f['tecnico'] !== '' || $f['cliente'] !== '' || $f['vencidos'] !== '';
$base = function (array $over) use ($f) { $q = array_filter(array_merge($f, ['page' => null, 'orden' => null], $over), fn($v) => $v !== '' && $v !== null && $v !== false); return '?' . http_build_query($q); };
?>
<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Mesa de Ayuda</div>
    <h1>Tickets de soporte</h1>
    <p>Gestiona incidencias y requerimientos con control de SLA.</p>
  </div>
  <a href="<?= url('tickets/crear') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Nuevo ticket</a>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
  <?php foreach ($chips as $k => [$lab, $n]): ?>
    <a href="<?= e($base(['estado' => $k, 'vencidos' => ''])) ?>" class="state-chip <?= ($f['estado'] === (string)$k && $f['vencidos'] === '') ? 'active' : '' ?>"><?= e($lab) ?> <b><?= (int)$n ?></b></a>
  <?php endforeach; ?>
  <a href="<?= e($base(['estado' => '', 'vencidos' => '1'])) ?>" class="state-chip <?= $f['vencidos'] !== '' ? 'active' : '' ?>" style="<?= $conteos['vencidos'] ? 'color:#dc2626' : '' ?>"><i class="bi bi-alarm"></i> Fuera de SLA <b><?= (int)$conteos['vencidos'] ?></b></a>
</div>

<div class="card-ui">
  <form method="get" class="filter-bar">
    <?php if ($f['vencidos'] !== ''): ?><input type="hidden" name="vencidos" value="1"><?php endif; ?>
    <div class="search-ui"><i class="bi bi-search"></i><input type="text" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar por código, asunto o cliente…"></div>
    <?php if ($f['estado'] !== ''): ?><input type="hidden" name="estado" value="<?= e($f['estado']) ?>"><?php endif; ?>
    <select name="prioridad" class="select-ui" onchange="this.form.submit()"><option value="">Prioridad: todas</option>
      <?php foreach (PRIORIDADES as $k => $v): ?><option value="<?= $k ?>" <?= $f['prioridad'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
    <select name="categoria" class="select-ui" onchange="this.form.submit()"><option value="">Categoría: todas</option>
      <?php foreach ($categorias as $c): ?><option value="<?= $c['id'] ?>" <?= $f['categoria'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select>
    <select name="tecnico" class="select-ui" onchange="this.form.submit()"><option value="">Técnico: todos</option><option value="0" <?= $f['tecnico'] === '0' ? 'selected' : '' ?>>Sin asignar</option>
      <?php foreach ($tecnicos as $c): ?><option value="<?= $c['id'] ?>" <?= $f['tecnico'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?></select>
    <select name="cliente" class="select-ui" onchange="this.form.submit()"><option value="">Cliente: todos</option>
      <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>" <?= $f['cliente'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['razon_social']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-soft" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    <?php if ($hay): ?><a href="<?= url('tickets') ?>" class="btn btn-ghost">Limpiar</a><?php endif; ?>
  </form>

  <div class="table-responsive">
    <table class="table-ui">
      <thead><tr><th>Ticket</th><th>Cliente</th><th>Prioridad</th><th>Estado</th><th>Técnico</th><th>SLA</th><th>Creado</th><th style="width:1%"></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $t): [$sc, $st] = sla_info($t); ?>
        <tr>
          <td><a href="<?= url('tickets/ver/' . $t['id']) ?>" class="tk-code fw-bold"><?= e($t['codigo']) ?></a><div class="tk-sub"><?= e(mb_strimwidth($t['asunto'], 0, 46, '…')) ?></div></td>
          <td><span class="small"><?= e($t['cliente']) ?></span><div class="tk-sub"><i class="dot" style="background:<?= e($t['cat_color']) ?>"></i> <?= e($t['categoria']) ?></div></td>
          <td><?= prio_pill($t['prioridad']) ?></td>
          <td><?= estado_pill($t['estado']) ?></td>
          <td><?= $t['tecnico'] ? avatar_html($t['tecnico'], $t['color'], 'avatar-sm') : '<span class="text-muted small">Sin asignar</span>' ?></td>
          <td><span class="sla sla-<?= $sc === 'bad' ? 'bad' : 'ok' ?>" style="<?= $sc === 'warn' ? 'color:#d97706' : '' ?>"><i class="bi <?= $sc === 'bad' ? 'bi-alarm' : 'bi-clock' ?>"></i> <?= e($st) ?></span></td>
          <td class="small text-muted"><?= e(hace($t['created_at'])) ?></td>
          <td><div class="row-actions"><a class="act-btn" href="<?= url('tickets/ver/' . $t['id']) ?>" title="Abrir"><i class="bi bi-arrow-right-short fs-5"></i></a></div></td>
        </tr>
      <?php endforeach; if (!$rows): ?>
        <tr><td colspan="8" class="empty-row"><i class="bi bi-inbox"></i>No hay tickets con esos filtros.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pager($pg) ?>
</div>

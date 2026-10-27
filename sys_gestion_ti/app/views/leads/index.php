<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / CRM</div>
    <h1>Leads y prospectos</h1>
    <p>Captura, califica y convierte posibles clientes en oportunidades de venta.</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-ghost" href="<?= url('leads/exportar?' . http_build_query(array_diff_key($_GET, ['url' => 1, 'page' => 1]))) ?>"><i class="bi bi-file-earmark-excel me-1"></i>CSV</a>
    <a class="btn btn-brand" href="<?= url('leads/crear') ?>"><i class="bi bi-person-plus-fill me-1"></i>Nuevo lead</a>
  </div>
</div>
<div class="row g-3 mb-3">
  <?php foreach ([['Leads abiertos', (int)$st['abiertos'], 'bi-people-fill', '#4f46e5'], ['Nuevos (7 días)', (int)$st['nuevos7'], 'bi-stars', '#0ea5e9'], ['Calificados', (int)$st['calificados'], 'bi-patch-check-fill', '#10b981'], ['Score promedio', (int)$st['score'], 'bi-speedometer2', '#f59e0b']] as $s): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:<?= $s[3] ?>1f;color:<?= $s[3] ?>"><i class="bi <?= $s[2] ?>"></i></span><div><b><?= $s[1] ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card-ui">
  <form method="get" class="filter-bar form-ui">
    <div class="search-ui"><i class="bi bi-search"></i><input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar empresa, contacto o correo…"></div>
    <select name="estado" class="select-ui" onchange="this.form.submit()"><option value="">Estado: todos</option><option value="abiertos" <?= ($_GET['estado'] ?? '') === 'abiertos' ? 'selected' : '' ?>>Abiertos</option>
      <?php foreach (LEAD_ESTADOS as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
    <select name="origen" class="select-ui" onchange="this.form.submit()"><option value="">Origen: todos</option><?php foreach (LEAD_ORIGENES as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['origen'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
    <select name="resp" class="select-ui" onchange="this.form.submit()"><option value="">Responsable: todos</option><option value="mine" <?= ($_GET['resp'] ?? '') === 'mine' ? 'selected' : '' ?>>Mis leads</option>
      <?php foreach ($vendedores as $v): ?><option value="<?= $v['id'] ?>" <?= ($_GET['resp'] ?? '') === (string)$v['id'] ? 'selected' : '' ?>><?= e($v['nombre']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    <?php if (array_filter(array_diff_key($_GET, ['url' => 1, 'page' => 1]))): ?><a href="<?= url('leads') ?>" class="btn btn-ghost">Limpiar</a><?php endif; ?>
  </form>
  <div class="table-responsive"><table class="table-ui">
    <thead><tr><th>Lead</th><th>Contacto</th><th>Origen</th><th>Score</th><th>Estado</th><th>Próxima acción</th><th>Responsable</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $l): ?>
      <tr>
        <td class="cell-main"><a href="<?= url('leads/ver/' . $l['id']) ?>"><b><?= e($l['empresa']) ?></b></a><small><?= e($l['codigo']) ?> · <?= e($l['interes'] ? mb_strimwidth($l['interes'], 0, 42, '…') : ($l['rubro'] ?: 'Sin detalle')) ?></small></td>
        <td class="cell-main"><b style="font-size:.84rem"><?= e($l['contacto'] ?: '—') ?></b><small><?= e($l['telefono'] ?: $l['email'] ?: '') ?></small></td>
        <td class="small"><?= e(LEAD_ORIGENES[$l['origen']] ?? $l['origen']) ?></td>
        <td style="min-width:110px"><?= score_bar((int)$l['score']) ?></td>
        <td><?= lead_pill($l['estado']) ?></td>
        <td class="small"><?php if ($l['prox']): $v = strtotime($l['prox']) < time() - 3600; ?><span class="<?= $v ? 'text-danger fw-semibold' : '' ?>"><?= fdate($l['prox'], 'd/m H:i') ?></span><?php elseif (in_array($l['estado'], ['nuevo', 'contactado', 'calificado'])): ?><span class="text-warning"><i class="bi bi-exclamation-circle"></i> Sin agendar</span><?php else: ?>—<?php endif; ?></td>
        <td><?php if ($l['responsable']): ?><?= avatar_html($l['responsable'], $l['color'] ?: '#4f46e5', 'avatar-sm') ?><?php else: ?>—<?php endif; ?></td>
        <td class="text-end"><a href="<?= url('leads/ver/' . $l['id']) ?>" class="btn btn-soft btn-sm">Abrir</a></td>
      </tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty-row"><i class="bi bi-inbox"></i>No hay leads con esos filtros.</td></tr><?php endif; ?>
    </tbody></table></div>
  <?= pager($pg) ?>
</div>

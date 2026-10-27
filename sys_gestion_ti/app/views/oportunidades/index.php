<?php $qs = http_build_query(array_diff_key($_GET, ['url' => 1, 'page' => 1, 'vista' => 1])); $qa = $qs ? "&$qs" : ''; ?>
<div class="page-head">
  <div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / CRM</div><h1>Pipeline de ventas</h1><p>Arrastra las oportunidades entre etapas para actualizar su avance.</p></div>
  <div class="d-flex gap-2 flex-wrap">
    <div class="seg"><a class="<?= !$lista ? 'on' : '' ?>" href="<?= url('oportunidades?' . $qs) ?>"><i class="bi bi-kanban"></i> Kanban</a><a class="<?= $lista ? 'on' : '' ?>" href="<?= url('oportunidades?vista=lista' . $qa) ?>"><i class="bi bi-list-ul"></i> Lista</a></div>
    <a class="btn btn-ghost" href="<?= url('oportunidades/exportar?' . $qs) ?>"><i class="bi bi-file-earmark-excel me-1"></i>CSV</a>
    <a class="btn btn-brand" href="<?= url('oportunidades/crear') ?>"><i class="bi bi-plus-lg me-1"></i>Nueva oportunidad</a>
  </div>
</div>
<div class="row g-3 mb-3">
  <?php foreach ([['En pipeline', (int)$resumen['n'] . ' oport.', 'bi-bullseye', '#4f46e5'], ['Valor total', money($resumen['valor']), 'bi-cash-stack', '#0ea5e9'], ['Valor ponderado', money($resumen['pond']), 'bi-graph-up-arrow', '#10b981'], ['Con cierre vencido', (int)$resumen['atrasadas'], 'bi-alarm', '#ef4444']] as $s): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:<?= $s[3] ?>1f;color:<?= $s[3] ?>"><i class="bi <?= $s[2] ?>"></i></span><div><b><?= $s[1] ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>
<form method="get" class="filter-bar form-ui card-ui mb-3" style="padding:.75rem 1rem">
  <?php if ($lista): ?><input type="hidden" name="vista" value="lista"><?php endif; ?>
  <div class="search-ui"><i class="bi bi-search"></i><input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar oportunidad, cliente o código…"></div>
  <select name="tipo" class="select-ui" onchange="this.form.submit()"><option value="">Tipo: todos</option><?php foreach (OPP_TIPOS as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['tipo'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
  <select name="resp" class="select-ui" onchange="this.form.submit()"><option value="">Responsable: todos</option><option value="mine" <?= ($_GET['resp'] ?? '') === 'mine' ? 'selected' : '' ?>>Mis oportunidades</option><?php foreach ($vendedores as $v): ?><option value="<?= $v['id'] ?>" <?= ($_GET['resp'] ?? '') === (string)$v['id'] ? 'selected' : '' ?>><?= e($v['nombre']) ?></option><?php endforeach; ?></select>
  <?php if ($lista): ?><select name="estado" class="select-ui" onchange="this.form.submit()"><option value="">Estado: todos</option><?php foreach (['abierta' => 'Abiertas', 'ganada' => 'Ganadas', 'perdida' => 'Perdidas'] as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select><?php endif; ?>
  <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filtrar</button>
</form>

<?php if (!$lista): ?>
<div class="kb-board" id="kbBoard" data-csrf="<?= e(Auth::csrf()) ?>" data-url="<?= url('oportunidades/mover') ?>">
<?php foreach ($cols as $c): ?>
  <div class="kb-col" data-etapa="<?= $c['id'] ?>" data-tipo="<?= $c['tipo'] ?>" style="--kc:<?= e($c['color']) ?>">
    <div class="kb-head"><div><b><?= e($c['nombre']) ?></b> <span class="kb-n"><?= $c['n'] ?></span></div><small><?= money($c['valor']) ?><?= $c['tipo'] === 'abierta' ? ' · pond. ' . money($c['pond']) : '' ?></small></div>
    <div class="kb-body">
    <?php foreach ($c['cards'] as $o): $atras = $o['estado'] === 'abierta' && $o['fecha_cierre_estimada'] < date('Y-m-d'); ?>
      <div class="kb-card" draggable="<?= $o['estado'] === 'abierta' ? 'true' : 'false' ?>" data-id="<?= $o['id'] ?>" data-titulo="<?= e($o['codigo'] . ' · ' . $o['titulo']) ?>" data-valor="<?= (float)$o['valor'] ?>">
        <div class="kb-top"><a href="<?= url('oportunidades/ver/' . $o['id']) ?>" class="kb-title"><?= e($o['titulo']) ?></a>
          <?php if ($o['estado'] === 'abierta'): ?><div class="dropdown"><button class="kb-mv" data-bs-toggle="dropdown" title="Mover a…"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end small">
            <li><h6 class="dropdown-header">Mover a…</h6></li><?php foreach ($etapas as $e2): if ((int)$e2['id'] === (int)$o['etapa_id']) continue; ?><li><a class="dropdown-item" href="#" data-mover="<?= $e2['id'] ?>" data-tipo="<?= $e2['tipo'] ?>"><?= e($e2['nombre']) ?></a></li><?php endforeach; ?></ul></div><?php endif; ?></div>
        <div class="kb-cli"><i class="bi bi-building"></i> <?= e($o['cliente']) ?></div>
        <div class="kb-val"><?= money($o['valor']) ?> <span class="kb-tipo"><?= e(OPP_TIPOS[$o['tipo']] ?? $o['tipo']) ?></span></div>
        <div class="kb-foot">
          <span class="<?= $atras ? 'text-danger fw-semibold' : '' ?>" title="Cierre estimado"><i class="bi bi-calendar-event"></i> <?= fdate($o['fecha_cierre_estimada'], 'd M') ?></span>
          <?php if ($o['prox_act']): ?><span title="Próxima actividad" class="<?= strtotime($o['prox_act']) < time() ? 'text-danger' : 'text-muted' ?>"><?= act_icon($o['prox_tipo']) ?> <?= fdate($o['prox_act'], 'd/m') ?></span><?php elseif ($o['estado'] === 'abierta'): ?><span class="text-warning" title="Sin actividad agendada"><i class="bi bi-exclamation-circle"></i> sin agenda</span><?php endif; ?>
          <?php if ($o['responsable']): ?><?= avatar_html($o['responsable'], $o['color'] ?: '#4f46e5', 'avatar-xs') ?><?php endif; ?>
        </div>
      </div>
    <?php endforeach; if (!$c['cards']): ?><div class="kb-empty">Sin oportunidades</div><?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="card-ui"><div class="table-responsive"><table class="table-ui">
  <thead><tr><th>Oportunidad</th><th>Cliente</th><th>Etapa</th><th class="text-end">Valor</th><th>Prob.</th><th>Cierre est.</th><th>Responsable</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $o): ?><tr>
    <td class="cell-main"><a href="<?= url('oportunidades/ver/' . $o['id']) ?>"><b><?= e($o['titulo']) ?></b></a><small><?= e($o['codigo']) ?> · <?= e(OPP_TIPOS[$o['tipo']] ?? '') ?></small></td>
    <td class="small"><?= e($o['cliente']) ?></td><td><?= etapa_pill($o['etapa'], $o['etapa_color']) ?></td>
    <td class="text-end fw-semibold"><?= money($o['valor']) ?></td><td class="small"><?= (int)$o['probabilidad'] ?>%</td>
    <td class="small <?= $o['estado'] === 'abierta' && $o['fecha_cierre_estimada'] < date('Y-m-d') ? 'text-danger fw-semibold' : '' ?>"><?= fdate($o['fecha_cierre_estimada']) ?></td>
    <td><?= $o['responsable'] ? avatar_html($o['responsable'], $o['color'] ?: '#4f46e5', 'avatar-sm') : '—' ?></td>
    <td class="text-end"><a href="<?= url('oportunidades/ver/' . $o['id']) ?>" class="btn btn-soft btn-sm">Abrir</a></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty-row"><i class="bi bi-inbox"></i>Sin oportunidades.</td></tr><?php endif; ?></tbody></table></div><?= pager($pg) ?></div>
<?php endif; ?>
<?php include __DIR__ . '/_modales.php'; ?>
<?php include APP_PATH . '/views/crm/_modales.php'; ?>

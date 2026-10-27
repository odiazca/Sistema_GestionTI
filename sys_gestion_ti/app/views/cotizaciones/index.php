<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / CRM</div><h1>Cotizaciones</h1><p>Propuestas económicas vinculadas a tus oportunidades.</p></div>
  <a class="btn btn-brand" href="<?= url('cotizaciones/crear') ?>"><i class="bi bi-plus-lg me-1"></i>Nueva cotización</a></div>
<div class="row g-3 mb-3">
  <?php foreach ([['Total emitidas', (int)$st['total'], 'bi-receipt', '#4f46e5'], ['Enviadas (esperando)', (int)$st['enviadas'] . ' · ' . money($st['monto_env']), 'bi-send-fill', '#0ea5e9'], ['Aprobado este mes', money($st['aprobado_mes']), 'bi-check2-circle', '#10b981'], ['Tasa de aprobación', (int)$st['tasa'] . '%', 'bi-percent', '#f59e0b']] as $s): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:<?= $s[3] ?>1f;color:<?= $s[3] ?>"><i class="bi <?= $s[2] ?>"></i></span><div><b><?= $s[1] ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card-ui">
  <form method="get" class="filter-bar form-ui"><div class="search-ui"><i class="bi bi-search"></i><input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar código o cliente…"></div>
    <select name="estado" class="select-ui" onchange="this.form.submit()"><option value="">Estado: todos</option><?php foreach (COT_ESTADOS as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
    <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filtrar</button></form>
  <div class="table-responsive"><table class="table-ui"><thead><tr><th>Código</th><th>Cliente</th><th>Oportunidad</th><th>Emisión</th><th>Vence</th><th class="text-end">Total</th><th>Estado</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr>
    <td><a href="<?= url('cotizaciones/ver/' . $r['id']) ?>"><b><?= e($r['codigo']) ?></b></a></td><td><?= e($r['cliente']) ?></td>
    <td class="small"><?= $r['oportunidad_id'] ? '<a href="' . url('oportunidades/ver/' . $r['oportunidad_id']) . '">' . e($r['op_codigo']) . '</a>' : '—' ?></td>
    <td class="small"><?= fdate($r['fecha']) ?></td><td class="small"><?= fdate($r['valida_hasta']) ?></td><td class="text-end fw-semibold"><?= money($r['total']) ?></td><td><?= cot_pill($r['estado']) ?></td>
    <td class="text-end"><a href="<?= url('cotizaciones/ver/' . $r['id']) ?>" class="btn btn-soft btn-sm">Abrir</a></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty-row"><i class="bi bi-inbox"></i>No hay cotizaciones.</td></tr><?php endif; ?></tbody></table></div><?= pager($pg) ?></div>

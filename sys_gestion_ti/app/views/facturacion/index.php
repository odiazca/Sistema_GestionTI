<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Facturación</div><h1>Facturación</h1><p>Comprobantes, pagos y cuentas por cobrar de tus clientes.</p></div>
  <div class="d-flex gap-2 flex-wrap"><a class="btn btn-ghost" href="<?= url('facturacion/exportar?' . http_build_query(array_diff_key($_GET, ['url' => 1, 'page' => 1]))) ?>"><i class="bi bi-file-earmark-excel me-1"></i>CSV</a><a class="btn btn-brand" href="<?= url('facturacion/crear') ?>"><i class="bi bi-plus-lg me-1"></i>Nuevo comprobante</a></div></div>
<?php include __DIR__ . '/_tabs.php'; ?>
<div class="row g-3 mb-3">
  <?php foreach ([['Facturado este mes', money($st['facturado_mes']), 'bi-receipt', '#4f46e5'], ['Cobrado este mes', money($st['cobrado_mes']), 'bi-cash-coin', '#10b981'], ['Por cobrar', money($st['por_cobrar']), 'bi-hourglass-split', '#0ea5e9'], ['Vencido (' . (int)$st['n_vencidos'] . ')', money($st['vencido']), 'bi-exclamation-triangle-fill', '#ef4444']] as $s): ?>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:<?= $s[3] ?>1f;color:<?= $s[3] ?>"><i class="bi <?= $s[2] ?>"></i></span><div><b><?= $s[1] ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card-ui">
  <form method="get" class="filter-bar form-ui">
    <div class="search-ui"><i class="bi bi-search"></i><input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar código, cliente o RUC…"></div>
    <select name="estado" class="select-ui" onchange="this.form.submit()"><option value="">Estado: todos</option><?php foreach (FAC_ESTADOS as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
    <select name="concepto" class="select-ui" onchange="this.form.submit()"><option value="">Concepto: todos</option><?php foreach (FAC_CONCEPTOS as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['concepto'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
    <select name="cliente" class="select-ui" onchange="this.form.submit()"><option value="">Cliente: todos</option><?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>" <?= ($_GET['cliente'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['razon_social']) ?></option><?php endforeach; ?></select>
    <input type="month" name="mes" class="select-ui" value="<?= e($_GET['mes'] ?? '') ?>" onchange="this.form.submit()" title="Mes de emisión">
    <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    <?php if (array_filter(array_diff_key($_GET, ['url' => 1, 'page' => 1]))): ?><a href="<?= url('facturacion') ?>" class="btn btn-ghost">Limpiar</a><?php endif; ?>
  </form>
  <div class="table-responsive"><table class="table-ui"><thead><tr><th>Comprobante</th><th>Cliente</th><th>Concepto</th><th>Emisión</th><th>Vence</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th>Estado</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr>
    <td class="cell-main"><a href="<?= url('facturacion/ver/' . $r['id']) ?>"><b><?= e($r['codigo']) ?></b></a><small><?= e(FAC_TIPOS[$r['tipo']]) ?></small></td>
    <td class="small"><?= e($r['cliente']) ?></td><td class="small"><?= e(FAC_CONCEPTOS[$r['concepto']] ?? '') ?><?= $r['periodo'] ? ' <span class="text-muted">· ' . e($r['periodo']) . '</span>' : '' ?></td>
    <td class="small"><?= fdate($r['fecha_emision']) ?></td><td class="small <?= $r['est'] === 'vencida' ? 'text-danger fw-semibold' : '' ?>"><?= fdate($r['fecha_vencimiento']) ?></td>
    <td class="text-end fw-semibold"><?= money($r['total']) ?></td><td class="text-end"><?= $r['estado'] === 'emitida' ? money($r['saldo']) : '—' ?></td><td><?= fac_pill($r['est']) ?></td>
    <td class="text-end"><a href="<?= url('facturacion/ver/' . $r['id']) ?>" class="btn btn-soft btn-sm">Abrir</a></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty-row"><i class="bi bi-inbox"></i>No hay comprobantes con esos filtros.</td></tr><?php endif; ?></tbody></table></div><?= pager($pg) ?></div>

<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Facturación</div><h1>Cuentas por cobrar</h1><p>Saldos pendientes por cliente según su antigüedad de vencimiento.</p></div></div>
<?php include __DIR__ . '/_tabs.php'; ?>
<div class="row g-3 mb-3">
  <?php foreach ([['Total por cobrar', $tot['saldo'], '#4f46e5'], ['Por vencer', $tot['vigente'], '#0ea5e9'], ['1–30 días', $tot['d30'], '#f59e0b'], ['31–60 días', $tot['d60'], '#f97316'], ['61–90 días', $tot['d90'], '#ef4444'], ['Más de 90', $tot['d90m'], '#991b1b']] as $s): ?>
  <div class="col-6 col-xl-2"><div class="stat-mini" style="border-top:3px solid <?= $s[2] ?>"><div><b style="font-size:1rem"><?= money($s[1]) ?></b><span><?= $s[0] ?></span></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card-ui"><div class="table-responsive"><table class="table-ui"><thead><tr><th>Cliente</th><th class="text-center">Docs</th><th class="text-end">Por vencer</th><th class="text-end">1–30</th><th class="text-end">31–60</th><th class="text-end">61–90</th><th class="text-end">+90</th><th class="text-end">Saldo</th><th></th></tr></thead><tbody>
<?php $f = fn($v) => $v > 0 ? money($v) : '<span class="text-muted">—</span>'; foreach ($rows as $r): ?><tr>
  <td class="cell-main"><a href="<?= url('facturacion?estado=&cliente=' . $r['id']) ?>"><b><?= e($r['cliente']) ?></b></a><small><?= $r['max_dias'] > 0 ? '<span class="text-danger">' . (int)$r['max_dias'] . ' días de atraso máx.</span>' : 'Al día' ?><?= $r['telefono'] ? ' · ' . e($r['telefono']) : '' ?></small></td>
  <td class="text-center"><?= (int)$r['docs'] ?></td><td class="text-end"><?= $f($r['vigente']) ?></td><td class="text-end"><?= $f($r['d30']) ?></td><td class="text-end"><?= $f($r['d60']) ?></td><td class="text-end"><?= $f($r['d90']) ?></td><td class="text-end"><?= $f($r['d90m']) ?></td><td class="text-end fw-bold"><?= money($r['saldo']) ?></td>
  <td class="text-end text-nowrap"><a href="<?= url('facturacion?estado=&cliente=' . $r['id']) ?>" class="btn btn-soft btn-sm">Ver</a>
    <?php if (in_array(Auth::user()['rol'], ['Administrador', 'Supervisor'])): ?><form method="post" action="<?= url('facturacion/agendar/' . $r['id']) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" title="Agendar llamada de cobranza en el CRM"><i class="bi bi-telephone-outbound"></i></button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty-row"><i class="bi bi-emoji-smile"></i>No hay saldos pendientes. ¡Todo cobrado!</td></tr><?php endif; ?></tbody></table></div></div>

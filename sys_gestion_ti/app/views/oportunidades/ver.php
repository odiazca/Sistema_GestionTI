<?php
$abierta = $o['estado'] === 'abierta';
$pend = array_filter($acts, fn($a) => !$a['completada'] && !$a['auto']); $hist = array_filter($acts, fn($a) => $a['completada'] || $a['auto']);
$ctx = json_encode(['oportunidad_id' => (int)$o['id'], 'cliente_id' => (int)$o['cliente_id'], 'ctx' => $o['codigo'] . ' · ' . $o['titulo']]);
?>
<div class="page-head">
  <div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('oportunidades') ?>">Pipeline</a> / <?= e($o['codigo']) ?></div>
    <h1><?= e($o['titulo']) ?></h1>
    <p><?= e($o['codigo']) ?> · <a href="<?= url('clientes/ver/' . $o['cliente_id']) ?>"><?= e($o['cliente']) ?></a> · <?= e(OPP_TIPOS[$o['tipo']] ?? '') ?> · <?= $abierta ? etapa_pill($o['etapa'], $o['etapa_color']) : ($o['estado'] === 'ganada' ? '<span class="pill pill-success">Ganada</span>' : '<span class="pill pill-danger">Perdida</span>') ?></p></div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($abierta): ?>
      <button class="btn btn-ghost" data-nueva-act='<?= $ctx ?>'><i class="bi bi-calendar-plus me-1"></i>Agendar</button>
      <a class="btn btn-ghost" href="<?= url('cotizaciones/crear?op=' . $o['id']) ?>"><i class="bi bi-file-earmark-text me-1"></i>Cotizar</a>
      <a class="btn btn-ghost" href="<?= url('oportunidades/editar/' . $o['id']) ?>"><i class="bi bi-pencil me-1"></i>Editar</a>
      <button class="btn btn-danger-soft" data-perder="<?= $o['id'] ?>" data-titulo="<?= e($o['codigo']) ?>"><i class="bi bi-hand-thumbs-down me-1"></i>Perdida</button>
      <button class="btn btn-brand" data-ganar="<?= $o['id'] ?>" data-titulo="<?= e($o['codigo'] . ' · ' . $o['titulo']) ?>" data-valor="<?= (float)$o['valor'] ?>"><i class="bi bi-trophy me-1"></i>Ganada</button>
    <?php else: ?><form method="post" action="<?= url('oportunidades/reabrir/' . $o['id']) ?>"><?= csrf_field() ?><button class="btn btn-ghost"><i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir</button></form><?php endif; ?>
  </div>
</div>

<div class="card-ui mb-3"><div class="stepper">
  <?php $pas = array_values(array_filter($etapas, fn($e) => $e['tipo'] === 'abierta')); foreach ($pas as $i => $e): $cur = $abierta && (int)$e['id'] === (int)$o['etapa_id']; $done = $o['estado'] === 'ganada' || ($abierta && $e['orden'] < $o['orden']); ?>
    <div class="step <?= $cur ? 'cur' : ($done ? 'done' : '') ?>"><span><?= $done ? '<i class="bi bi-check-lg"></i>' : $i + 1 ?></span><b><?= e($e['nombre']) ?></b><small><?= $e['probabilidad'] ?>%</small></div>
  <?php endforeach; ?>
</div></div>

<div class="row g-3">
  <div class="col-xl-4">
    <div class="card-ui mb-3"><div class="card-head"><div><h6>Resumen</h6></div></div>
      <div class="row g-2 text-center mb-2"><div class="col-6"><div class="stat-mini d-block"><b><?= money($o['valor']) ?></b><span>Valor</span></div></div><div class="col-6"><div class="stat-mini d-block"><b><?= money($o['valor'] * $o['probabilidad'] / 100) ?></b><span>Ponderado <?= (int)$o['probabilidad'] ?>%</span></div></div></div>
      <div class="info-list">
        <div class="info-item"><span>Cierre estimado</span><b class="<?= $abierta && $o['fecha_cierre_estimada'] < date('Y-m-d') ? 'text-danger' : '' ?>"><?= fdate($o['fecha_cierre_estimada']) ?></b></div>
        <?php if ($o['fecha_cierre_real']): ?><div class="info-item"><span>Cierre real</span><b><?= fdate($o['fecha_cierre_real']) ?></b></div><?php endif; ?>
        <div class="info-item"><span>Contacto</span><b><?= e($o['contacto'] ?: '—') ?><?= $o['contacto_cargo'] ? '<br><small class="text-muted fw-normal">' . e($o['contacto_cargo']) . '</small>' : '' ?></b></div>
        <?php if ($o['contacto_tel']): ?><div class="info-item"><span>Teléfono</span><b><a href="tel:<?= e($o['contacto_tel']) ?>"><?= e($o['contacto_tel']) ?></a></b></div><?php endif; ?>
        <div class="info-item"><span>Responsable</span><b><?= e($o['responsable'] ?: 'Sin asignar') ?></b></div>
        <?php if ($lead): ?><div class="info-item"><span>Lead origen</span><b><a href="<?= url('leads/ver/' . $lead['id']) ?>"><?= e($lead['codigo']) ?></a></b></div><?php endif; ?>
        <?php if ($o['contrato']): ?><div class="info-item"><span>Contrato</span><b><a href="<?= url('contratos') ?>"><?= e($o['contrato']) ?></a></b></div><?php endif; ?>
      </div>
      <?php if ($o['descripcion']): ?><div class="mini-title mt-3">Alcance</div><p class="small mb-0"><?= nl2br(e($o['descripcion'])) ?></p><?php endif; ?>
      <?php if ($o['estado'] === 'perdida'): ?><div class="alert alert-light border small mt-3 mb-0"><b>Motivo:</b> <?= e($o['motivo_perdida']) ?></div><?php endif; ?>
    </div>
    <div class="card-ui"><div class="card-head"><div><h6>Cotizaciones</h6></div><?php if ($abierta): ?><a href="<?= url('cotizaciones/crear?op=' . $o['id']) ?>" class="btn btn-soft btn-sm"><i class="bi bi-plus-lg"></i></a><?php endif; ?></div>
      <?php foreach ($cots as $c): ?><a href="<?= url('cotizaciones/ver/' . $c['id']) ?>" class="mini-row"><div><b><?= e($c['codigo']) ?></b><small class="d-block text-muted"><?= fdate($c['fecha']) ?></small></div><div class="text-end"><b><?= money($c['total']) ?></b><div><?= cot_pill($c['estado']) ?></div></div></a><?php endforeach; if (!$cots): ?><p class="text-muted small mb-0">Aún no hay cotizaciones.</p><?php endif; ?></div>
    <?php if (in_array(Auth::user()['rol'], ['Administrador', 'Supervisor'])): ?><form method="post" action="<?= url('oportunidades/eliminar/' . $o['id']) ?>" onsubmit="return confirm('¿Eliminar esta oportunidad y sus actividades?')" class="mt-3"><?= csrf_field() ?><button class="btn btn-ghost btn-sm w-100 text-danger"><i class="bi bi-trash3 me-1"></i>Eliminar oportunidad</button></form><?php endif; ?>
  </div>
  <div class="col-xl-8">
    <div class="card-ui mb-3"><div class="card-head"><div><h6>Próximas acciones</h6><small><?= count($pend) ?> pendiente(s)</small></div><?php if ($abierta): ?><button class="btn btn-soft btn-sm" data-nueva-act='<?= $ctx ?>'><i class="bi bi-plus-lg"></i> Nueva</button><?php endif; ?></div>
      <?php foreach ($pend as $a) echo act_item($a, false); if (!$pend): ?><p class="text-muted small mb-0"><?= $abierta ? '<i class="bi bi-exclamation-circle text-warning"></i> Sin próxima acción. Una oportunidad sin agenda tiende a enfriarse.' : 'Sin acciones pendientes.' ?></p><?php endif; ?></div>
    <div class="card-ui"><div class="card-head"><div><h6>Historial</h6></div></div>
      <?php foreach ($hist as $a) echo act_item($a, false); if (!$hist): ?><p class="text-muted small mb-0">Sin historial todavía.</p><?php endif; ?></div>
  </div>
</div>
<?php include __DIR__ . '/_modales.php'; ?>
<?php include APP_PATH . '/views/crm/_modales.php'; ?>

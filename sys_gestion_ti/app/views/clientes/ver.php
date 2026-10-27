<?php $sla = $resumen['resueltos'] ? round($resumen['en_sla'] * 100 / $resumen['resueltos']) : 100; ?>
<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('clientes') ?>">Clientes</a> / Ficha</div>
    <div class="detail-hero">
      <div class="logo"><?= e(iniciales($cli['razon_social'])) ?></div>
      <div>
        <h1 class="m-0" style="font-size:1.5rem;font-weight:800"><?= e($cli['razon_social']) ?></h1>
        <p class="m-0 text-muted"><?= e($cli['rubro'] ?: 'Sin rubro') ?> · RUC <?= e($cli['ruc'] ?: '—') ?> · <?= $cli['estado'] ? pill('Activo', 'success') : pill('Inactivo', 'muted') ?></p>
      </div>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('clientes/editar/' . $cli['id']) ?>" class="btn btn-ghost"><i class="bi bi-pencil me-1"></i>Editar</a>
    <a href="<?= url('tickets/crear?cliente=' . $cli['id']) ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Nuevo ticket</a>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:#4f46e51f;color:#4f46e5"><i class="bi bi-ticket-perforated-fill"></i></span><div><b><?= (int)$resumen['total'] ?></b><span>Tickets totales</span></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:#f59e0b1f;color:#f59e0b"><i class="bi bi-hourglass-split"></i></span><div><b><?= (int)$resumen['abiertos'] ?></b><span>Abiertos</span></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:#10b9811f;color:#10b981"><i class="bi bi-shield-check"></i></span><div><b><?= $sla ?>%</b><span>Cumplimiento SLA</span></div></div></div>
  <div class="col-6 col-xl-3"><div class="stat-mini"><span class="ic" style="background:#0ea5e91f;color:#0ea5e9"><i class="bi bi-pc-display-horizontal"></i></span><div><b><?= count($activos) ?><?= count($activos) >= 12 ? '+' : '' ?></b><span>Equipos</span></div></div></div>
</div>

<?php $crmOn = !empty($crm); $tab = $_GET['tab'] ?? 'resumen'; if (!$crmOn) $tab = 'resumen'; ?>
<?php if ($crmOn): ?>
<ul class="nav tabs-ui mb-3" id="cliTabs">
  <?php foreach (['resumen' => ['Resumen', 'bi-grid'], 'contactos' => ['Contactos (' . count($crm['contactos']) . ')', 'bi-people'], 'oportunidades' => ['Oportunidades (' . count($crm['ops']) . ')', 'bi-bullseye'], 'actividad' => ['Actividad', 'bi-clock-history'], 'cotizaciones' => ['Cotizaciones (' . count($crm['cots']) . ')', 'bi-receipt']] + (!empty($crm['fac']['ok']) ? ['facturacion' => ['Facturación', 'bi-cash-coin']] : []) as $k => $t): ?>
  <li class="nav-item"><button type="button" class="nav-link <?= $tab === $k ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tb-<?= $k ?>"><i class="bi <?= $t[1] ?> me-1"></i><?= $t[0] ?></button></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<div class="tab-content"><div class="tab-pane fade <?= $tab === 'resumen' ? 'show active' : '' ?>" id="tb-resumen">
<div class="row g-3">
  <div class="col-xl-4">
    <div class="card-ui h-100">
      <div class="card-head"><div><h6>Información de contacto</h6></div></div>
      <div class="info-list">
        <div class="info-item"><span>Contacto</span><b><?= e($cli['contacto'] ?: '—') ?></b></div>
        <div class="info-item"><span>Teléfono</span><b><?= e($cli['telefono'] ?: '—') ?></b></div>
        <div class="info-item"><span>Correo</span><b><?= e($cli['email'] ?: '—') ?></b></div>
        <div class="info-item"><span>Dirección</span><b><?= e($cli['direccion'] ?: '—') ?></b></div>
        <div class="info-item"><span>Cliente desde</span><b><?= fdate($cli['created_at']) ?></b></div>
      </div>
      <h6 class="mini-title mt-4">Contratos</h6>
      <?php foreach ($contratos as $k): ?>
        <div class="alert-row"><div><b><?= e($k['codigo']) ?> · <?= e($k['plan']) ?></b><small>Hasta <?= fdate($k['fecha_fin']) ?> · SLA <?= (int)$k['sla_respuesta_horas'] ?>h/<?= (int)$k['sla_resolucion_horas'] ?>h</small></div>
          <?= $k['estado'] === 'vencido' ? pill('Vencido', 'danger') : ($k['estado'] === 'por_vencer' ? pill('Por vencer', 'warning') : pill('Vigente', 'success')) ?></div>
      <?php endforeach; if (!$contratos) echo '<p class="text-muted small">Sin contratos registrados.</p>'; ?>
    </div>
  </div>
  <div class="col-xl-8">
    <div class="card-ui mb-3">
      <div class="card-head"><div><h6>Últimos tickets</h6></div><a href="<?= url('tickets?cliente=' . $cli['id']) ?>" class="btn btn-soft btn-sm">Ver todos</a></div>
      <div class="table-responsive"><table class="table-ui">
        <thead><tr><th>Ticket</th><th>Categoría</th><th>Prioridad</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr><td><a href="<?= url('tickets/ver/' . $t['id']) ?>" class="tk-code fw-bold"><?= e($t['codigo']) ?></a><div class="tk-sub"><?= e(mb_strimwidth($t['asunto'], 0, 44, '…')) ?></div></td>
              <td><?= e($t['categoria']) ?></td><td><?= prio_pill($t['prioridad']) ?></td><td><?= estado_pill($t['estado']) ?></td><td class="small"><?= fdate($t['created_at']) ?></td></tr>
        <?php endforeach; if (!$tickets): ?><tr><td colspan="5" class="empty-row">Sin tickets aún.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <div class="card-ui">
      <div class="card-head"><div><h6>Equipos del cliente</h6></div><a href="<?= url('activos?cliente=' . $cli['id']) ?>" class="btn btn-soft btn-sm">Ver inventario</a></div>
      <div class="row g-2">
        <?php foreach ($activos as $a): ?>
        <div class="col-md-6"><div class="alert-row" style="border:1px solid var(--line);border-radius:12px;padding:.65rem .85rem">
          <div><b><?= e($a['tipo']) ?> <?= e($a['marca']) ?></b><small><?= e($a['codigo']) ?> · <?= e($a['ubicacion'] ?: '—') ?></small></div>
          <?= $a['estado'] === 'operativo' ? pill('OK', 'success') : pill('Rep.', 'warning') ?></div></div>
        <?php endforeach; if (!$activos) echo '<p class="text-muted small m-0">Sin equipos registrados.</p>'; ?>
      </div>
    </div>
  </div>
</div>
</div>
<?php if ($crmOn): $vendedores = $crm['vendedores']; $ctx = json_encode(['cliente_id' => (int)$cli['id'], 'ctx' => $cli['razon_social']]); ?>
<div class="tab-pane fade <?= $tab === 'contactos' ? 'show active' : '' ?>" id="tb-contactos"><div class="card-ui">
  <div class="card-head"><div><h6>Personas de contacto</h6><small>El contacto principal se sincroniza con la ficha del cliente</small></div><button class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#mdlContacto" data-contacto='{}'><i class="bi bi-person-plus me-1"></i>Agregar contacto</button></div>
  <div class="row g-3">
  <?php foreach ($crm['contactos'] as $c): ?><div class="col-md-6 col-xl-4"><div class="contact-card">
    <div class="d-flex gap-2 align-items-center"><?= avatar_html($c['nombre'], '#4f46e5') ?><div class="flex-fill"><b><?= e($c['nombre']) ?></b> <?= $c['principal'] ? '<span class="pill pill-soft-primary">Principal</span>' : '' ?><small class="d-block text-muted"><?= e($c['cargo'] ?: 'Sin cargo') ?></small></div></div>
    <div class="small mt-2"><?= $c['email'] ? '<div><i class="bi bi-envelope me-1 text-muted"></i><a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a></div>' : '' ?><?= $c['telefono'] ? '<div><i class="bi bi-telephone me-1 text-muted"></i><a href="tel:' . e($c['telefono']) . '">' . e($c['telefono']) . '</a></div>' : '' ?><?= $c['notas'] ? '<div class="text-muted mt-1">' . e($c['notas']) . '</div>' : '' ?></div>
    <div class="d-flex gap-1 mt-2">
      <button class="btn btn-ghost btn-sm" data-bs-toggle="modal" data-bs-target="#mdlContacto" data-contacto='<?= e(json_encode($c, JSON_UNESCAPED_UNICODE)) ?>'><i class="bi bi-pencil"></i></button>
      <?php if (!$c['principal']): ?><form method="post" action="<?= url('contactos/principal/' . $c['id']) ?>"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" title="Marcar como principal"><i class="bi bi-star"></i></button></form><?php endif; ?>
      <?php if (in_array(Auth::user()['rol'], ['Administrador', 'Supervisor']) && $c['email']): ?><form method="post" action="<?= url('contactos/acceso/' . $c['id']) ?>" onsubmit="return confirm('¿Crear o restablecer el acceso al portal para este contacto?')"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" title="Acceso al portal del cliente"><i class="bi bi-key"></i></button></form><?php endif; ?>
      <form method="post" action="<?= url('contactos/eliminar/' . $c['id']) ?>" onsubmit="return confirm('¿Eliminar este contacto?')"><?= csrf_field() ?><button class="btn btn-ghost btn-sm text-danger"><i class="bi bi-trash3"></i></button></form></div>
  </div></div><?php endforeach; if (!$crm['contactos']): ?><p class="text-muted small">Aún no hay contactos registrados para este cliente.</p><?php endif; ?></div></div></div>

<div class="tab-pane fade <?= $tab === 'oportunidades' ? 'show active' : '' ?>" id="tb-oportunidades"><div class="card-ui">
  <div class="card-head"><div><h6>Oportunidades</h6></div><a href="<?= url('oportunidades/crear?cliente=' . $cli['id']) ?>" class="btn btn-brand btn-sm"><i class="bi bi-plus-lg me-1"></i>Nueva</a></div>
  <div class="table-responsive"><table class="table-ui"><thead><tr><th>Oportunidad</th><th>Etapa</th><th class="text-end">Valor</th><th>Cierre est.</th></tr></thead><tbody>
  <?php foreach ($crm['ops'] as $o): ?><tr><td class="cell-main"><a href="<?= url('oportunidades/ver/' . $o['id']) ?>"><b><?= e($o['titulo']) ?></b></a><small><?= e($o['codigo']) ?></small></td><td><?= etapa_pill($o['etapa'], $o['etapa_color']) ?></td><td class="text-end fw-semibold"><?= money($o['valor']) ?></td><td class="small"><?= fdate($o['fecha_cierre_estimada']) ?></td></tr>
  <?php endforeach; if (!$crm['ops']): ?><tr><td colspan="4" class="empty-row">Sin oportunidades.</td></tr><?php endif; ?></tbody></table></div></div></div>

<div class="tab-pane fade <?= $tab === 'actividad' ? 'show active' : '' ?>" id="tb-actividad"><div class="row g-3">
  <div class="col-xl-5"><div class="card-ui"><div class="card-head"><div><h6>Pendientes</h6></div><button class="btn btn-soft btn-sm" data-nueva-act='<?= $ctx ?>'><i class="bi bi-plus-lg"></i> Agendar</button></div>
    <?php foreach ($crm['pend'] as $a) echo act_item($a, false); if (!$crm['pend']): ?><p class="text-muted small mb-0">Sin acciones pendientes.</p><?php endif; ?></div></div>
  <div class="col-xl-7"><div class="card-ui"><div class="card-head"><div><h6>Línea de tiempo 360°</h6><small>CRM, soporte y contratos</small></div></div>
    <div class="timeline"><?php foreach ($crm['timeline'] as $t): ?><div class="tl-item"><span class="tl-ic" style="background:<?= $t['color'] ?>1f;color:<?= $t['color'] ?>"><i class="bi <?= $t['ic'] ?>"></i></span><div><b><?= e($t['tit']) ?></b> <span class="pill pill-muted"><?= e($t['tag']) ?></span><?= $t['det'] ? '<div class="small text-muted">' . e($t['det']) . '</div>' : '' ?><small class="text-muted"><?= fdate($t['f'], 'd/m/Y H:i') ?><?= $t['quien'] ? ' · ' . e($t['quien']) : '' ?></small></div></div><?php endforeach; if (!$crm['timeline']) echo '<p class="text-muted small">Sin eventos.</p>'; ?></div></div></div>
</div></div>

<div class="tab-pane fade <?= $tab === 'cotizaciones' ? 'show active' : '' ?>" id="tb-cotizaciones"><div class="card-ui">
  <div class="card-head"><div><h6>Cotizaciones</h6></div><a href="<?= url('cotizaciones/crear?cliente=' . $cli['id']) ?>" class="btn btn-brand btn-sm"><i class="bi bi-plus-lg me-1"></i>Nueva</a></div>
  <?php foreach ($crm['cots'] as $c): ?><a href="<?= url('cotizaciones/ver/' . $c['id']) ?>" class="mini-row"><div><b><?= e($c['codigo']) ?></b><small class="d-block text-muted"><?= fdate($c['fecha']) ?> · vence <?= fdate($c['valida_hasta']) ?></small></div><div class="text-end"><b><?= money($c['total']) ?></b><div><?= cot_pill($c['estado']) ?></div></div></a><?php endforeach; if (!$crm['cots']): ?><p class="text-muted small mb-0">Sin cotizaciones.</p><?php endif; ?></div></div>
<?php if (!empty($crm['fac']['ok'])): ?>
<div class="tab-pane fade <?= $tab === 'facturacion' ? 'show active' : '' ?>" id="tb-facturacion"><div class="card-ui">
  <div class="card-head"><div><h6>Comprobantes</h6><small>Saldo pendiente: <b class="<?= $crm['fac']['saldo'] > 0 ? 'text-danger' : 'text-success' ?>"><?= money($crm['fac']['saldo']) ?></b></small></div><a href="<?= url('facturacion/crear?cliente=' . $cli['id']) ?>" class="btn btn-brand btn-sm"><i class="bi bi-plus-lg me-1"></i>Nuevo</a></div>
  <?php foreach ($crm['fac']['rows'] as $f): ?><a href="<?= url('facturacion/ver/' . $f['id']) ?>" class="mini-row"><div><b><?= e($f['codigo']) ?></b><small class="d-block text-muted"><?= fdate($f['fecha_emision']) ?> · vence <?= fdate($f['fecha_vencimiento']) ?></small></div><div class="text-end"><b><?= money($f['total']) ?></b><div><?= fac_pill($f['est']) ?></div></div></a><?php endforeach; if (!$crm['fac']['rows']): ?><p class="text-muted small mb-0">Aún no hay comprobantes.</p><?php endif; ?></div></div>
<?php endif; ?>
<?php endif; ?>
</div>

<?php if ($crmOn): ?>
<div class="modal fade" id="mdlContacto" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 form-ui" style="border-radius:20px"><form method="post" action="<?= url('contactos/guardar') ?>"><?= csrf_field() ?><input type="hidden" name="cliente_id" value="<?= $cli['id'] ?>"><input type="hidden" name="id" id="kId" value="0">
  <div class="modal-header border-0 px-4 pt-4"><h5 class="modal-title fw-bold" id="kTit">Contacto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body px-4 pt-0"><div class="row g-2">
    <div class="col-12"><label class="lbl">Nombre <span class="req">*</span></label><input class="form-control" name="nombre" id="kNombre" maxlength="100" required></div>
    <div class="col-12"><label class="lbl">Cargo</label><input class="form-control" name="cargo" id="kCargo" maxlength="80"></div>
    <div class="col-6"><label class="lbl">Teléfono</label><input class="form-control" name="telefono" id="kTel" maxlength="25"></div><div class="col-6"><label class="lbl">Correo</label><input type="email" class="form-control" name="email" id="kEmail" maxlength="120"></div>
    <div class="col-12"><label class="lbl">Notas</label><input class="form-control" name="notas" id="kNotas" maxlength="255"></div>
    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="principal" value="1" id="kPrin"><label class="form-check-label" for="kPrin">Contacto principal</label></div></div></div></div>
  <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div></form></div></div></div>
<?php include APP_PATH . '/views/crm/_modales.php';
endif; ?>

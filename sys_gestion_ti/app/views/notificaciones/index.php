<?php $est = ['enviado' => ['success', 'Enviado'], 'simulado' => ['warning', 'Simulado'], 'error' => ['danger', 'Error']]; $admin = (Auth::user()['rol'] ?? '') === 'Administrador'; ?>
<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / Sistema</div>
    <h1>Notificaciones</h1>
    <p>Avisos por correo a clientes y bandeja de envíos.</p>
  </div>
  <span class="pill pill-<?= $listo ? 'success' : 'warning' ?>"><i class="bi <?= $listo ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-1"></i><?= $listo ? 'SMTP configurado' : 'Modo simulación (sin SMTP)' ?></span>
</div>
<div class="d-flex gap-2 mb-3">
  <a class="cfg-tab <?= $tab === 'bandeja' ? 'active' : '' ?>" href="<?= url('notificaciones') ?>"><i class="bi bi-inbox me-1"></i>Bandeja de envíos</a>
  <?php if ($admin): ?><a class="cfg-tab <?= $tab === 'config' ? 'active' : '' ?>" href="<?= url('notificaciones?tab=config') ?>"><i class="bi bi-sliders me-1"></i>Configuración</a><?php endif; ?>
</div>
<?php if ($tab === 'bandeja'): ?>
<div class="row g-3 mb-3">
  <?php foreach ($est as $k => [$c, $l]): ?><div class="col-4"><div class="stat-mini"><div><b><?= (int)($cnt[$k] ?? 0) ?></b><span><?= $l ?></span></div></div></div><?php endforeach; ?>
</div>
<div class="card-ui">
  <form method="get" class="filter-bar form-ui">
    <div class="search-ui"><i class="bi bi-search"></i><input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Destinatario o asunto…"></div>
    <select name="estado" class="select-ui" onchange="this.form.submit()"><option value="">Estado: todos</option><?php foreach ($est as $k => [$c, $l]): ?><option value="<?= $k ?>" <?= ($_GET['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filtrar</button>
  </form>
  <div class="table-responsive"><table class="table table-ui align-middle">
    <thead><tr><th>Fecha</th><th>Destinatario</th><th>Asunto</th><th>Cliente</th><th>Estado</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $n): [$c, $l] = $est[$n['estado']] ?? ['secondary', $n['estado']]; ?>
    <tr><td class="text-nowrap small"><?= fdate($n['created_at'], 'd/m/Y H:i') ?></td><td><?= e($n['destinatario']) ?></td><td><?= e($n['asunto']) ?><?php if ($n['error']): ?><div class="small text-danger"><?= e($n['error']) ?></div><?php endif; ?></td>
      <td class="small"><?= e($n['cliente'] ?? '—') ?></td><td><span class="pill pill-<?= $c ?>"><?= $l ?></span></td>
      <td class="text-end text-nowrap"><a class="btn btn-sm btn-ghost" target="_blank" href="<?= url('notificaciones/ver/' . $n['id']) ?>" title="Ver contenido"><i class="bi bi-eye"></i></a>
        <?php if ($n['estado'] !== 'enviado'): ?><form method="post" action="<?= url('notificaciones/reenviar/' . $n['id']) ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-soft" title="Reenviar"><i class="bi bi-arrow-repeat"></i></button></form><?php endif; ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">Aún no hay envíos registrados.</td></tr><?php endif; ?>
    </tbody></table></div>
  <?= pager($pg) ?>
</div>
<?php else: ?>
<div class="row g-3">
  <div class="col-lg-7"><form method="post" action="<?= url('notificaciones/guardar') ?>" class="card-ui form-ui p-4"><?= csrf_field() ?>
    <h5 class="fw-bold mb-3">Servidor de correo (SMTP)</h5>
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label">Servidor</label><input class="form-control" name="smtp_host" value="<?= e($cfg['smtp_host']) ?>" placeholder="smtp.gmail.com"></div>
      <div class="col-md-4"><label class="form-label">Puerto</label><input class="form-control" name="smtp_port" value="<?= e($cfg['smtp_port']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Seguridad</label><select class="form-select" name="smtp_seguridad"><?php foreach (['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'Ninguna'] as $k => $l): ?><option value="<?= $k ?>" <?= $cfg['smtp_seguridad'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Usuario</label><input class="form-control" name="smtp_user" value="<?= e($cfg['smtp_user']) ?>" autocomplete="off"></div>
      <div class="col-md-4"><label class="form-label">Contraseña</label><input type="password" class="form-control" name="smtp_pass" placeholder="<?= $cfg['tiene_pass'] ? '•••••• (guardada)' : '' ?>" autocomplete="new-password"></div>
      <div class="col-md-6"><label class="form-label">Correo remitente</label><input type="email" class="form-control" name="smtp_from" value="<?= e($cfg['smtp_from']) ?>" placeholder="soporte@tuempresa.pe"></div>
      <div class="col-md-6"><label class="form-label">Nombre remitente</label><input class="form-control" name="smtp_nombre" value="<?= e($cfg['smtp_nombre']) ?>"></div>
      <div class="col-12"><label class="form-label">Copia interna de tickets del portal (opcional)</label><input type="email" class="form-control" name="notif_copia" value="<?= e($cfg['notif_copia']) ?>" placeholder="mesa@tuempresa.pe"></div>
    </div>
    <h6 class="fw-bold mt-4 mb-2">Avisos automáticos al cliente</h6>
    <?php foreach (['notif_ticket_creado' => 'Al registrar su ticket', 'notif_ticket_estado' => 'Cuando cambia el estado del ticket', 'notif_ticket_respuesta' => 'Cuando soporte responde en el ticket'] as $k => $l): ?>
    <div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" name="<?= $k ?>" id="<?= $k ?>" value="1" <?= $cfg[$k] === '1' ? 'checked' : '' ?>><label class="form-check-label" for="<?= $k ?>"><?= $l ?></label></div>
    <?php endforeach; ?>
    <?php if ($cfg['tiene_pass']): ?><div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="smtp_pass_borrar" id="pb" value="1"><label class="form-check-label small" for="pb">Borrar la contraseña guardada</label></div><?php endif; ?>
    <div class="mt-4"><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Guardar</button></div>
  </form></div>
  <div class="col-lg-5"><div class="card-ui p-4">
    <h5 class="fw-bold mb-2">Enviar prueba</h5><p class="text-muted small">Guarda primero y luego envía un correo de prueba para validar el servidor.</p>
    <form method="post" action="<?= url('notificaciones/probar') ?>" class="form-ui"><?= csrf_field() ?><input type="email" class="form-control mb-2" name="destino" value="<?= e(Auth::user()['email'] ?? '') ?>" required><button class="btn btn-soft"><i class="bi bi-send me-1"></i>Enviar prueba</button></form>
    <hr><h6 class="fw-bold">Ejemplos de servidores</h6>
    <p class="small text-muted mb-1"><b>Gmail:</b> smtp.gmail.com · 587 · STARTTLS · usa una «contraseña de aplicación».</p>
    <p class="small text-muted mb-1"><b>Outlook/365:</b> smtp.office365.com · 587 · STARTTLS.</p>
    <p class="small text-muted mb-0">Sin SMTP, los correos se guardan como «simulado» y puedes reenviarlos luego.</p>
  </div></div>
</div>
<?php endif; ?>

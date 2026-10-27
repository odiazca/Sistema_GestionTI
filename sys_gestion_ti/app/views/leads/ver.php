<?php
$abierto = in_array($l['estado'], ['nuevo', 'contactado', 'calificado'], true);
$pend = array_filter($acts, fn($a) => !$a['completada'] && !$a['auto']); $hist = array_filter($acts, fn($a) => $a['completada'] || $a['auto']);
?>
<div class="page-head">
  <div>
    <div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('leads') ?>">Leads</a> / <?= e($l['codigo']) ?></div>
    <div class="detail-hero"><div class="logo"><?= e(iniciales($l['empresa'])) ?></div>
      <div><h1 class="m-0" style="font-size:1.5rem;font-weight:800"><?= e($l['empresa']) ?></h1>
        <p class="m-0 text-muted"><?= e($l['codigo']) ?> · <?= e(LEAD_ORIGENES[$l['origen']] ?? '') ?> · registrado <?= e(hace($l['created_at'])) ?> · <?= lead_pill($l['estado']) ?></p></div></div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <?php if ($abierto): ?><button class="btn btn-ghost" data-nueva-act='<?= json_encode(['lead_id' => (int)$l['id'], 'ctx' => $l['empresa']]) ?>'><i class="bi bi-calendar-plus me-1"></i>Agendar</button>
    <a class="btn btn-ghost" href="<?= url('leads/editar/' . $l['id']) ?>"><i class="bi bi-pencil me-1"></i>Editar</a>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#mdlConvertir"><i class="bi bi-arrow-repeat me-1"></i>Convertir en cliente</button><?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-4">
    <div class="card-ui mb-3">
      <div class="card-head"><div><h6>Calidad del lead</h6><small>Puntaje automático de 0 a 100</small></div></div>
      <div class="score-lg"><div class="score-ring" style="--p:<?= (int)$l['score'] ?>"><b><?= (int)$l['score'] ?></b></div>
        <div class="small text-muted"><?= $l['score'] >= 70 ? '<b class="text-success">Alta prioridad.</b> Contáctalo hoy.' : ($l['score'] >= 40 ? '<b class="text-warning">Prioridad media.</b> Agenda seguimiento.' : '<b>Prioridad baja.</b> Completa sus datos y califícalo.') ?></div></div>
      <?php if ($abierto): ?>
      <div class="mini-title mt-3">Avance</div>
      <form method="post" action="<?= url('leads/estado/' . $l['id']) ?>" class="d-flex gap-1 mt-2"><?= csrf_field() ?>
        <?php foreach (['nuevo' => 'Nuevo', 'contactado' => 'Contactado', 'calificado' => 'Calificado'] as $k => $v): ?><button name="estado" value="<?= $k ?>" class="btn btn-sm flex-fill <?= $l['estado'] === $k ? 'btn-brand' : 'btn-ghost' ?>"><?= $v ?></button><?php endforeach; ?></form>
      <?php endif; ?>
    </div>
    <div class="card-ui mb-3">
      <div class="card-head"><div><h6>Información</h6></div></div>
      <div class="info-list">
        <div class="info-item"><span>Contacto</span><b><?= e($l['contacto'] ?: '—') ?><?= $l['cargo'] ? '<br><small class="text-muted fw-normal">' . e($l['cargo']) . '</small>' : '' ?></b></div>
        <div class="info-item"><span>Teléfono</span><b><?= $l['telefono'] ? '<a href="tel:' . e($l['telefono']) . '">' . e($l['telefono']) . '</a>' : '—' ?></b></div>
        <div class="info-item"><span>Correo</span><b><?= $l['email'] ? '<a href="mailto:' . e($l['email']) . '">' . e($l['email']) . '</a>' : '—' ?></b></div>
        <div class="info-item"><span>Rubro</span><b><?= e($l['rubro'] ?: '—') ?></b></div>
        <div class="info-item"><span>Interés</span><b><?= e($l['interes'] ?: '—') ?></b></div>
        <div class="info-item"><span>Responsable</span><b><?= e($l['responsable'] ?: 'Sin asignar') ?></b></div>
      </div>
      <?php if ($l['notas']): ?><div class="mini-title mt-3">Notas</div><p class="small mb-0"><?= nl2br(e($l['notas'])) ?></p><?php endif; ?>
      <?php if ($l['estado'] === 'descartado'): ?><div class="alert alert-light border small mt-3 mb-0"><b>Descartado:</b> <?= e($l['motivo_descarte']) ?>
        <form method="post" action="<?= url('leads/reabrir/' . $l['id']) ?>" class="mt-2"><?= csrf_field() ?><button class="btn btn-soft btn-sm">Reabrir lead</button></form></div><?php endif; ?>
      <?php if ($l['estado'] === 'convertido'): ?><div class="alert alert-success small mt-3 mb-0"><i class="bi bi-check-circle-fill me-1"></i>Convertido<?= $cliente ? ' en <a href="' . url('clientes/ver/' . $cliente['id']) . '"><b>' . e($cliente['razon_social']) . '</b></a>' : '' ?><?= $opp ? ' · <a href="' . url('oportunidades/ver/' . $opp['id']) . '"><b>' . e($opp['codigo']) . '</b></a>' : '' ?></div><?php endif; ?>
    </div>
    <?php if ($abierto): ?><div class="d-flex gap-2">
      <button class="btn btn-ghost btn-sm flex-fill text-danger" data-bs-toggle="modal" data-bs-target="#mdlDescartar"><i class="bi bi-x-circle me-1"></i>Descartar</button>
      <?php if (in_array(Auth::user()['rol'], ['Administrador', 'Supervisor'])): ?><form method="post" action="<?= url('leads/eliminar/' . $l['id']) ?>" onsubmit="return confirm('¿Eliminar este lead y su historial?')" class="flex-fill"><?= csrf_field() ?><button class="btn btn-ghost btn-sm w-100 text-danger"><i class="bi bi-trash3 me-1"></i>Eliminar</button></form><?php endif; ?></div><?php endif; ?>
  </div>
  <div class="col-xl-8">
    <div class="card-ui mb-3">
      <div class="card-head"><div><h6>Próximas acciones</h6><small><?= count($pend) ?> pendiente(s)</small></div><?php if ($abierto): ?><button class="btn btn-soft btn-sm" data-nueva-act='<?= json_encode(['lead_id' => (int)$l['id'], 'ctx' => $l['empresa']]) ?>'><i class="bi bi-plus-lg"></i> Nueva</button><?php endif; ?></div>
      <?php foreach ($pend as $a) echo act_item($a, false); if (!$pend): ?><p class="text-muted small mb-0"><?= $abierto ? '<i class="bi bi-exclamation-circle text-warning"></i> No hay nada agendado. Un lead sin seguimiento se enfría: programa el primer contacto.' : 'Sin acciones pendientes.' ?></p><?php endif; ?>
    </div>
    <div class="card-ui">
      <div class="card-head"><div><h6>Historial</h6><small>Todo lo ocurrido con este lead</small></div></div>
      <?php foreach ($hist as $a) echo act_item($a, false); if (!$hist): ?><p class="text-muted small mb-0">Aún no hay historial.</p><?php endif; ?>
    </div>
  </div>
</div>

<div class="modal fade" id="mdlDescartar" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 form-ui" style="border-radius:20px"><form method="post" action="<?= url('leads/descartar/' . $l['id']) ?>"><?= csrf_field() ?>
  <div class="modal-header border-0 px-4 pt-4"><h5 class="modal-title fw-bold">Descartar lead</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body px-4 pt-0"><label class="lbl">Motivo <span class="req">*</span></label><select class="form-select mb-2" onchange="this.form.motivo.value=this.value"><option value="">— Elegir motivo frecuente —</option><option>Ya cuenta con proveedor de TI</option><option>Presupuesto insuficiente</option><option>No responde</option><option>No es nuestro público objetivo</option><option>Datos falsos / duplicado</option></select><input class="form-control" name="motivo" maxlength="200" required placeholder="Describe el motivo"></div>
  <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger">Descartar</button></div></form></div></div></div>

<div class="modal fade" id="mdlConvertir" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 form-ui" style="border-radius:20px"><form method="post" action="<?= url('leads/convertir/' . $l['id']) ?>"><?= csrf_field() ?>
  <div class="modal-header border-0 px-4 pt-4"><div><h5 class="modal-title fw-bold">Convertir en cliente</h5><div class="small text-muted">Se crea la ficha del cliente y su contacto principal</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body px-4 pt-0">
    <label class="lbl">Cliente</label>
    <select class="form-select mb-3" name="cliente_id"><option value="">➕ Crear cliente nuevo: <?= e($l['empresa']) ?></option><?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>">Vincular a: <?= e($c['razon_social']) ?></option><?php endforeach; ?></select>
    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="crear_oportunidad" value="1" id="cvOp" checked><label class="form-check-label fw-semibold" for="cvOp">Crear oportunidad en el pipeline</label></div>
    <div class="row g-2" id="cvBox">
      <div class="col-12"><input class="form-control" name="titulo" maxlength="180" value="<?= e($l['interes'] ?: 'Oportunidad ' . $l['empresa']) ?>" placeholder="Título de la oportunidad"></div>
      <div class="col-6"><label class="lbl">Valor estimado (S/)</label><input type="number" min="0" step="0.01" class="form-control" name="valor" placeholder="0.00"></div>
      <div class="col-6"><label class="lbl">Cierre estimado</label><input type="date" class="form-control" name="cierre" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"></div>
    </div>
  </div>
  <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-arrow-repeat me-1"></i>Convertir</button></div></form></div></div></div>
<script>document.getElementById('cvOp').addEventListener('change',function(){document.getElementById('cvBox').style.display=this.checked?'':'none'});</script>
<?php include APP_PATH . '/views/crm/_modales.php'; ?>

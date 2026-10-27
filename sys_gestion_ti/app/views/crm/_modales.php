<?php
/** Modales compartidos del CRM: nueva actividad, completar y reprogramar. Requiere $vendedores. */
$qs = $_GET; unset($qs['url']);
$volver = trim($_GET['url'] ?? '', '/') . ($qs ? '?' . http_build_query($qs) : '');
?>
<div class="modal fade" id="mdlActividad" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 form-ui" style="border-radius:20px">
  <form method="post" action="<?= url('actividades/guardar') ?>">
    <?= csrf_field() ?><input type="hidden" name="volver" value="<?= e($volver) ?>">
    <input type="hidden" name="cliente_id" id="aCli"><input type="hidden" name="lead_id" id="aLead"><input type="hidden" name="oportunidad_id" id="aOp">
    <div class="modal-header border-0 pb-0 px-4 pt-4"><div><h5 class="modal-title fw-bold">Nueva actividad</h5><div class="small text-muted" id="aCtx">Agenda un seguimiento o registra lo que ya hiciste</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body px-4">
      <div class="act-types mb-3" id="aTipos">
        <?php foreach (ACT_TIPOS as $k => $t): if ($k === 'nota') continue; ?>
        <label class="act-type"><input type="radio" name="tipo" value="<?= $k ?>" <?= $k === 'llamada' ? 'checked' : '' ?>><span style="--c:<?= $t[2] ?>"><i class="bi <?= $t[1] ?>"></i><?= $t[0] ?></span></label>
        <?php endforeach; ?>
      </div>
      <div class="row g-3">
        <div class="col-12"><label class="lbl">Asunto <span class="req">*</span></label><input class="form-control" name="asunto" id="aAsunto" maxlength="180" required placeholder="Ej: Llamar para confirmar la propuesta"></div>
        <div class="col-6"><label class="lbl">Fecha</label><input type="date" class="form-control" name="fecha" id="aFecha" value="<?= date('Y-m-d', strtotime('+1 day')) ?>"></div>
        <div class="col-6"><label class="lbl">Hora</label><input type="time" class="form-control" name="hora" value="09:00"></div>
        <div class="col-12"><label class="lbl">Responsable</label><select class="form-select" name="responsable_id"><?php foreach ($vendedores as $v): ?><option value="<?= $v['id'] ?>" <?= (int)$v['id'] === (int)Auth::user()['id'] ? 'selected' : '' ?>><?= e($v['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><label class="lbl">Detalle</label><textarea class="form-control" name="detalle" rows="2" maxlength="2000" placeholder="Notas o contexto (opcional)"></textarea></div>
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="ya_realizada" id="aHecha" value="1"><label class="form-check-label" for="aHecha">Ya la realicé (registrar en el historial ahora)</label></div></div>
      </div>
    </div>
    <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Guardar</button></div>
  </form></div></div></div>

<div class="modal fade" id="mdlCompletar" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 form-ui" style="border-radius:20px">
  <form method="post" id="frmCompletar">
    <?= csrf_field() ?><input type="hidden" name="volver" value="<?= e($volver) ?>">
    <div class="modal-header border-0 pb-0 px-4 pt-4"><div><h5 class="modal-title fw-bold">Completar actividad</h5><div class="small text-muted" id="cAsunto"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body px-4">
      <label class="lbl">¿Cómo te fue? (resultado)</label><textarea class="form-control" name="resultado" rows="3" maxlength="2000" placeholder="Ej: Cliente interesado, pidió propuesta con 3 opciones"></textarea>
      <div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="seguimiento" id="cSeg" value="1"><label class="form-check-label" for="cSeg">Programar el siguiente paso</label></div>
      <div class="row g-2 mt-1" id="cSegBox" hidden>
        <div class="col-12"><input class="form-control" name="sig_asunto" maxlength="180" placeholder="Siguiente acción (ej: Enviar cotización)"></div>
        <div class="col-6"><select class="form-select" name="sig_tipo"><?php foreach (ACT_TIPOS as $k => $t): if ($k === 'nota') continue; ?><option value="<?= $k ?>"><?= $t[0] ?></option><?php endforeach; ?></select></div>
        <div class="col-6"><input type="date" class="form-control" name="sig_fecha" value="<?= date('Y-m-d', strtotime('+3 days')) ?>"></div>
      </div>
    </div>
    <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Marcar como hecha</button></div>
  </form></div></div></div>

<div class="modal fade" id="mdlReprog" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content border-0 form-ui" style="border-radius:20px">
  <form method="post" id="frmReprog">
    <?= csrf_field() ?><input type="hidden" name="volver" value="<?= e($volver) ?>">
    <div class="modal-header border-0 pb-0 px-4 pt-4"><h5 class="modal-title fw-bold">Reprogramar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body px-4">
      <div class="d-flex gap-2 mb-3"><button name="dias" value="1" class="btn btn-soft btn-sm flex-fill">+1 día</button><button name="dias" value="3" class="btn btn-soft btn-sm flex-fill">+3 días</button><button name="dias" value="7" class="btn btn-soft btn-sm flex-fill">+1 semana</button></div>
      <label class="lbl">O elige una fecha</label><div class="d-flex gap-2"><input type="date" class="form-control" name="fecha" value="<?= date('Y-m-d', strtotime('+1 day')) ?>"><button class="btn btn-brand btn-sm">Ir</button></div>
    </div>
  </form></div></div></div>

<form method="post" id="frmDelAct" hidden><?= csrf_field() ?><input type="hidden" name="volver" value="<?= e($volver) ?>"></form>

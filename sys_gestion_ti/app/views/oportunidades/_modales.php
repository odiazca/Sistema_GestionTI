<?php /* requiere $o opcional; formularios con action dinámico (data-id) */ ?>
<div class="modal fade" id="mdlGanar" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 form-ui" style="border-radius:20px"><form method="post" id="frmGanar"><?= csrf_field() ?>
  <div class="modal-header border-0 px-4 pt-4"><div><h5 class="modal-title fw-bold">🏆 Cerrar como ganada</h5><div class="small text-muted" id="gTitulo"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body px-4 pt-0">
    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="crear_contrato" value="1" id="gCt" checked><label class="form-check-label fw-semibold" for="gCt">Crear contrato de soporte automáticamente</label></div>
    <div class="row g-2" id="gBox">
      <div class="col-6"><label class="lbl">Plan SLA</label><select class="form-select" name="plan" id="gPlan"><option value="Básico" data-m="420">Básico · 8h/72h</option><option value="Business" data-m="950">Business · 4h/36h</option><option value="Premium" data-m="1800">Premium · 2h/24h</option></select></div>
      <div class="col-6"><label class="lbl">Cuota mensual (S/)</label><input type="number" step="0.01" min="0" class="form-control" name="monto_mensual" id="gMonto" value="420"></div>
      <div class="col-6"><label class="lbl">Inicio</label><input type="date" class="form-control" name="fecha_inicio" value="<?= date('Y-m-d') ?>"></div>
      <div class="col-6"><label class="lbl">Duración (meses)</label><input type="number" min="1" max="60" class="form-control" name="meses" value="12"></div>
    </div>
  </div>
  <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-trophy me-1"></i>Confirmar</button></div></form></div></div></div>

<div class="modal fade" id="mdlPerder" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 form-ui" style="border-radius:20px"><form method="post" id="frmPerder"><?= csrf_field() ?>
  <div class="modal-header border-0 px-4 pt-4"><div><h5 class="modal-title fw-bold">Marcar como perdida</h5><div class="small text-muted" id="pTitulo"></div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body px-4 pt-0"><label class="lbl">Motivo <span class="req">*</span></label>
    <select class="form-select mb-2" onchange="this.form.motivo.value=this.value"><option value="">— Motivo frecuente —</option><option>Precio superior a la competencia</option><option>Eligió otro proveedor</option><option>Proyecto pospuesto o cancelado</option><option>Sin presupuesto</option><option>Sin respuesta del cliente</option></select>
    <input class="form-control" name="motivo" maxlength="200" required placeholder="Describe el motivo"></div>
  <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger">Marcar perdida</button></div></form></div></div></div>

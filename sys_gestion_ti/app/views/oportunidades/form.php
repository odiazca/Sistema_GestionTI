<?php
$val = fn($k) => $row[$k] ?? '';
$err = fn($k) => isset($errors[$k]) ? '<div class="err"><i class="bi bi-exclamation-circle"></i> ' . e($errors[$k]) . '</div>' : '';
$inv = fn($k) => isset($errors[$k]) ? 'is-invalid' : '';
?>
<div class="page-head"><div><div class="crumbs"><a href="<?= url('dashboard') ?>">Inicio</a> / <a href="<?= url('oportunidades') ?>">Pipeline</a> / <?= $id ? 'Editar' : 'Nueva' ?></div><h1><?= $id ? 'Editar oportunidad' : 'Nueva oportunidad' ?></h1></div></div>
<form method="post" class="form-ui" novalidate><?= csrf_field() ?>
<div class="row g-3"><div class="col-xl-8"><div class="card-ui">
  <div class="form-section" style="margin-top:0">Oportunidad</div>
  <div class="row g-3">
    <div class="col-12"><label class="lbl">Título <span class="req">*</span></label><input class="form-control <?= $inv('titulo') ?>" name="titulo" maxlength="180" value="<?= e($val('titulo')) ?>" placeholder="Ej: Contrato de soporte Business 12 meses"><?= $err('titulo') ?></div>
    <div class="col-md-6"><label class="lbl">Cliente <span class="req">*</span></label><select class="form-select <?= $inv('cliente_id') ?>" name="cliente_id" id="opCli"><option value="">— Seleccionar —</option><?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$val('cliente_id') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['razon_social']) ?></option><?php endforeach; ?></select><?= $err('cliente_id') ?></div>
    <div class="col-md-6"><label class="lbl">Contacto</label><select class="form-select" name="contacto_id" id="opCon" data-sel="<?= (int)$val('contacto_id') ?>"><option value="">— Sin contacto —</option><?php foreach ($contactos as $k): ?><option value="<?= $k['id'] ?>" data-cli="<?= $k['cliente_id'] ?>"><?= e($k['nombre'] . ($k['cargo'] ? ' · ' . $k['cargo'] : '')) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="lbl">Tipo <span class="req">*</span></label><select class="form-select <?= $inv('tipo') ?>" name="tipo"><?php foreach (OPP_TIPOS as $k => $v): ?><option value="<?= $k ?>" <?= $val('tipo') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="lbl">Valor estimado (S/)</label><input type="number" step="0.01" min="0" class="form-control <?= $inv('valor') ?>" name="valor" value="<?= e($val('valor')) ?>"><?= $err('valor') ?></div>
    <div class="col-md-4"><label class="lbl">Cierre estimado <span class="req">*</span></label><input type="date" class="form-control <?= $inv('fecha_cierre_estimada') ?>" name="fecha_cierre_estimada" value="<?= e($val('fecha_cierre_estimada')) ?>"><?= $err('fecha_cierre_estimada') ?></div>
    <div class="col-12"><label class="lbl">Descripción / alcance</label><textarea class="form-control" name="descripcion" rows="3" maxlength="2000"><?= e($val('descripcion')) ?></textarea></div>
  </div></div></div>
<div class="col-xl-4"><div class="card-ui">
  <div class="form-section" style="margin-top:0">Seguimiento</div>
  <div class="mb-3"><label class="lbl">Etapa <span class="req">*</span></label><select class="form-select <?= $inv('etapa_id') ?>" name="etapa_id" id="opEtapa"><?php foreach ($etapas as $e): ?><option value="<?= $e['id'] ?>" data-p="<?= $e['probabilidad'] ?>" <?= (int)$val('etapa_id') === (int)$e['id'] ? 'selected' : '' ?>><?= e($e['nombre']) ?> (<?= $e['probabilidad'] ?>%)</option><?php endforeach; ?></select></div>
  <div class="mb-3"><label class="lbl">Probabilidad (%)</label><input type="number" min="0" max="100" class="form-control <?= $inv('probabilidad') ?>" name="probabilidad" id="opProb" value="<?= e($val('probabilidad')) ?>"><?= $err('probabilidad') ?></div>
  <div class="mb-3"><label class="lbl">Responsable</label><select class="form-select" name="responsable_id"><?php foreach ($vendedores as $v): ?><option value="<?= $v['id'] ?>" <?= (int)$val('responsable_id') === (int)$v['id'] ? 'selected' : '' ?>><?= e($v['nombre']) ?></option><?php endforeach; ?></select></div>
  <div class="d-grid gap-2"><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i><?= $id ? 'Guardar cambios' : 'Crear oportunidad' ?></button><a class="btn btn-ghost" href="<?= url($id ? 'oportunidades/ver/' . $id : 'oportunidades') ?>">Cancelar</a></div>
</div></div></div></form>
<script>
(function(){
  var cli=document.getElementById('opCli'),con=document.getElementById('opCon'),et=document.getElementById('opEtapa'),pr=document.getElementById('opProb');
  function filtra(){var c=cli.value,sel=con.dataset.sel;[].forEach.call(con.options,function(o){if(!o.value)return;var ok=o.dataset.cli===c;o.hidden=!ok;o.disabled=!ok});
    if(con.selectedOptions[0]&&con.selectedOptions[0].disabled)con.value='';if(sel&&con.querySelector('option[value="'+sel+'"]:not([disabled])')){con.value=sel;con.dataset.sel=''}}
  cli.addEventListener('change',function(){con.dataset.sel='';con.value='';filtra()});filtra();
  et.addEventListener('change',function(){pr.value=et.selectedOptions[0].dataset.p});
})();
</script>

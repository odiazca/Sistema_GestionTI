<!doctype html><html lang="es"><head><meta charset="utf-8"><title><?= e($q['codigo']) ?> · <?= e($q['cliente']) ?></title>
<style>
*{box-sizing:border-box}body{font:14px/1.5 'Segoe UI',Arial,sans-serif;color:#1e293b;margin:0;background:#e2e8f0}
.sheet{max-width:820px;margin:20px auto;background:#fff;padding:44px 52px;box-shadow:0 8px 30px #0002}
.top .emp{display:flex;align-items:center}.top{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #4f46e5;padding-bottom:16px}
.top h1{margin:0;font-size:1.5rem;color:#0f172a}.top small{color:#64748b}.cod{text-align:right}.cod b{display:block;font-size:1.4rem;color:#4f46e5}
.cols{display:flex;gap:30px;margin:22px 0}.cols>div{flex:1}.cols h4{margin:0 0 4px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:#64748b}
table{width:100%;border-collapse:collapse;margin-top:6px}th{background:#f1f5f9;text-align:left;padding:8px 10px;font-size:.75rem;text-transform:uppercase;color:#475569}td{padding:9px 10px;border-bottom:1px solid #e2e8f0}.r{text-align:right}
.tot{margin-left:auto;width:290px;margin-top:14px}.tot div{display:flex;justify-content:space-between;padding:4px 0}.tot .g{border-top:2px solid #0f172a;margin-top:6px;padding-top:8px;font-size:1.15rem;font-weight:700;color:#4f46e5}
.cond{margin-top:26px;font-size:.85rem;color:#475569;white-space:pre-line}.foot{margin-top:40px;text-align:center;font-size:.75rem;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:12px}
.bar{max-width:820px;margin:14px auto 0;text-align:right}.bar button{background:#4f46e5;color:#fff;border:0;border-radius:8px;padding:9px 18px;cursor:pointer;font-weight:600}
@media print{body{background:#fff}.sheet{box-shadow:none;margin:0;padding:20px}.bar{display:none}}
</style></head><body>
<div class="bar"><button onclick="window.print()">Imprimir / Guardar PDF</button></div>
<div class="sheet">
  <div class="top"><div class="emp"><?php if ($lg = logo_url()): ?><img src="<?= e($lg) ?>" alt="" style="max-height:64px;max-width:160px;margin-right:14px"><?php endif; ?><div><h1><?= e(empresa_nombre()) ?></h1><small><?= cfg('empresa_ruc') ? e(doc_label()) . ' ' . e(cfg('empresa_ruc')) . '<br>' : '' ?><?= e(trim(cfg('empresa_direccion') . ' ' . cfg('empresa_ciudad'))) ?><br><?= e(cfg('empresa_telefono')) ?> <?= e(cfg('empresa_email')) ?></small></div></div>
    <div class="cod"><small>COTIZACIÓN</small><b><?= e($q['codigo']) ?></b><small>Emitida: <?= fdate($q['fecha']) ?><br>Válida hasta: <?= fdate($q['valida_hasta']) ?></small></div></div>
  <div class="cols"><div><h4>Cliente</h4><b><?= e($q['cliente']) ?></b><br><?= $q['ruc'] ? doc_label() . ' ' . e($q['ruc']) . '<br>' : '' ?><?= e($q['direccion'] ?? '') ?></div>
    <div><h4>Atención</h4><b><?= e($q['contacto'] ?: '—') ?></b><br><?= e($q['contacto_cargo'] ?? '') ?><br><?= e($q['contacto_email'] ?? '') ?></div></div>
  <table><thead><tr><th>Descripción</th><th class="r">Cant.</th><th class="r">P. unit.</th><th class="r">Importe</th></tr></thead><tbody>
  <?php foreach ($items as $it): ?><tr><td><?= e($it['descripcion']) ?></td><td class="r"><?= rtrim(rtrim(number_format($it['cantidad'], 2), '0'), '.') ?></td><td class="r"><?= money($it['precio_unit']) ?></td><td class="r"><?= money($it['cantidad'] * $it['precio_unit']) ?></td></tr><?php endforeach; ?></tbody></table>
  <div class="tot"><div><span>Subtotal</span><span><?= money($q['subtotal']) ?></span></div><?php if ($q['descuento'] > 0): ?><div><span>Descuento (<?= (float)$q['descuento_pct'] ?>%)</span><span>− <?= money($q['descuento']) ?></span></div><?php endif; ?><div><span>IGV (<?= (float)$q['igv_pct'] ?>%)</span><span><?= money($q['igv']) ?></span></div><div class="g"><span>TOTAL</span><span><?= money($q['total']) ?></span></div></div>
  <?php if ($q['condiciones']): ?><div class="cond"><b>Condiciones comerciales</b><br><?= e($q['condiciones']) ?></div><?php endif; ?>
  <?php if (cfg('pie_documentos')): ?><div class=\"cond\" style=\"text-align:center\"><?= e(cfg('pie_documentos')) ?></div><?php endif; ?>
  <div class="foot">Elaborada por <?= e($q['autor'] ?: '') ?> · <?= e(empresa_nombre()) ?></div>
</div></body></html>

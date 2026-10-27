/* Facturación: formulario de comprobante y gráficos de ingresos */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); }, $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };
  var body = $('#fcBody');
  if (body) {
    var money = function (n) { return fmtNum(n); };
    var calc = function () {
      var sub = 0; $$('tr', body).forEach(function (tr) { var v = (parseFloat($('.cq-c', tr).value) || 0) * (parseFloat($('.cq-p', tr).value) || 0); $('.cq-i', tr).textContent = money(v); sub += v; });
      var ig = sub * (parseFloat($('#fcIgv').value) || 0) / 100; $('#tSub').textContent = money(sub); $('#tIgv').textContent = money(ig); $('#tTot').textContent = window.money(sub + ig);
    };
    var fila = function () { var tr = document.createElement('tr'); tr.innerHTML = '<td><input class="form-control" name="item_desc[]" maxlength="255"></td><td><input type="number" class="form-control cq-c" name="item_cant[]" min="0.01" step="0.01" value="1"></td><td><input type="number" class="form-control cq-p" name="item_precio[]" min="0" step="0.01"></td><td class="text-end fw-semibold cq-i">0.00</td><td><button type="button" class="btn btn-ghost btn-sm text-danger cq-del"><i class="bi bi-x-lg"></i></button></td>'; body.appendChild(tr); return tr; };
    $('#fcAdd').addEventListener('click', function () { fila().querySelector('input').focus(); });
    $('#frmFac').addEventListener('input', calc);
    body.addEventListener('click', function (e) { var b = e.target.closest('.cq-del'); if (!b) return; if ($$('tr', body).length > 1) b.closest('tr').remove(); calc(); });
    if (!$$('tr', body).length) fila(); calc();
    $$('[data-dias]').forEach(function (b) { b.addEventListener('click', function () { var d = new Date($('#fcEm').value || Date.now()); d.setDate(d.getDate() + parseInt(b.dataset.dias, 10)); $('#fcVe').value = d.toISOString().slice(0, 10); }); });
    var cli = $('#fcCli'), tipo = $('#fcTipo'), rucs = {}; try { rucs = JSON.parse(cli.dataset.rucs || '{}'); } catch (e) {}
    cli.addEventListener('change', function () { if (cli.value) tipo.value = rucs[cli.value] ? 'factura' : 'boleta'; });
  }
  var D = window.FAC_DATA;
  if (D && window.Chart) {
    var charts = [], css = function (v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); };
    var pal = ['#4f46e5', '#10b981', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b'];
    var draw = function () {
      charts.forEach(function (c) { c.destroy(); }); charts = [];
      var line = css('--line') || '#e2e8f0'; Chart.defaults.color = css('--muted') || '#64748b'; Chart.defaults.font.family = 'inherit';
      var k = function (v) { return FMT.s + ' ' + (v >= 1000 ? Math.round(v / 1000) + 'k' : v); };
      var a = document.getElementById('chIng');
      if (a) charts.push(new Chart(a, { type: 'bar', data: { labels: D.serie.map(function (x) { return x.l; }), datasets: [{ label: 'Facturado', data: D.serie.map(function (x) { return x.f; }), backgroundColor: '#c7d2fe', borderRadius: 6 }, { label: 'Cobrado', data: D.serie.map(function (x) { return x.c; }), backgroundColor: '#10b981', borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } }, scales: { x: { grid: { display: false } }, y: { grid: { color: line }, ticks: { callback: k } } } } }));
      [['chMet', D.metodos], ['chCon', D.conceptos]].forEach(function (p) { var c = document.getElementById(p[0]); if (c) charts.push(new Chart(c, { type: 'doughnut', data: { labels: p[1].map(function (x) { return x.l; }), datasets: [{ data: p[1].map(function (x) { return x.v; }), backgroundColor: pal, borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8 } } } } })); });
    };
    draw(); new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
  }
})();

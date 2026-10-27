/* CRM: Kanban, modales de actividades, cotizaciones y gráficos */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); }, $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };
  var me = document.querySelector('script[src*="js/crm.js"]'), BASE = me ? me.src.replace(/\/assets\/js\/crm\.js.*$/, '') : '';
  function U(p) { return BASE + '/' + p; }
  function modal(id) { var el = document.getElementById(id); return el ? bootstrap.Modal.getOrCreateInstance(el) : null; }

  /* ---------- Actividades ---------- */
  window.crmNuevaActividad = function (o) {
    o = o || {};
    var set = function (id, v) { var e = document.getElementById(id); if (e) e.value = v || ''; };
    set('aCli', o.cliente_id); set('aLead', o.lead_id); set('aOp', o.oportunidad_id); set('aAsunto', '');
    var c = document.getElementById('aCtx'); if (c) c.textContent = o.ctx ? 'Vinculada a: ' + o.ctx : 'Agenda un seguimiento o registra lo que ya hiciste';
    var h = document.getElementById('aHecha'); if (h) h.checked = false;
    var m = modal('mdlActividad'); if (m) m.show();
  };
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('[data-nueva-act],[data-completar],[data-reprog],[data-delact],[data-ganar],[data-perder],[data-mover]'); if (!t) return;
    if (t.dataset.nuevaAct !== undefined) { ev.preventDefault(); var o = {}; try { o = JSON.parse(t.dataset.nuevaAct || '{}'); } catch (e) {} window.crmNuevaActividad(o); }
    else if (t.dataset.completar) { $('#frmCompletar').action = U('actividades/completar/' + t.dataset.completar); $('#cAsunto').textContent = t.dataset.asunto || ''; $('#frmCompletar').reset(); $('#cSegBox').hidden = true; modal('mdlCompletar').show(); }
    else if (t.dataset.reprog) { $('#frmReprog').action = U('actividades/reprogramar/' + t.dataset.reprog); modal('mdlReprog').show(); }
    else if (t.dataset.delact) { if (confirm('¿Eliminar esta actividad?')) { var f = $('#frmDelAct'); f.action = U('actividades/eliminar/' + t.dataset.delact); f.submit(); } }
    else if (t.dataset.ganar) { ev.preventDefault(); abrirGanar(t.dataset.ganar, t.dataset.titulo); }
    else if (t.dataset.perder) { ev.preventDefault(); abrirPerder(t.dataset.perder, t.dataset.titulo); }
    else if (t.dataset.mover) { ev.preventDefault(); var card = t.closest('.kb-card'); mover(card, t.dataset.mover, t.dataset.tipo); }
  });
  var seg = $('#cSeg'); if (seg) seg.addEventListener('change', function () { $('#cSegBox').hidden = !seg.checked; });

  /* ---------- Ganar / perder ---------- */
  function abrirGanar(id, titulo) {
    $('#frmGanar').action = U('oportunidades/ganar/' + id); $('#gTitulo').textContent = titulo || ''; modal('mdlGanar').show();
  }
  function abrirPerder(id, titulo) { $('#frmPerder').action = U('oportunidades/perder/' + id); $('#pTitulo').textContent = titulo || ''; $('#frmPerder').reset(); modal('mdlPerder').show(); }
  var gp = $('#gPlan'); if (gp) gp.addEventListener('change', function () { $('#gMonto').value = gp.selectedOptions[0].dataset.m; });
  var gc = $('#gCt'); if (gc) gc.addEventListener('change', function () { $('#gBox').style.display = gc.checked ? '' : 'none'; });

  /* ---------- Kanban ---------- */
  var board = $('#kbBoard');
  function recalc() {
    $$('.kb-col', board).forEach(function (col) {
      var cards = $$('.kb-card', col), n = cards.length, tot = 0;
      cards.forEach(function (c) { tot += parseFloat(c.dataset.valor || 0); });
      $('.kb-n', col).textContent = n;
      var emp = $('.kb-empty', col), body = $('.kb-body', col);
      if (!n && !emp) { var d = document.createElement('div'); d.className = 'kb-empty'; d.textContent = 'Sin oportunidades'; body.appendChild(d); } else if (n && emp) emp.remove();
    });
  }
  function toast(msg, ok) {
    var t = document.createElement('div'); t.className = 'crm-toast ' + (ok ? 'ok' : 'err'); t.textContent = msg; document.body.appendChild(t);
    setTimeout(function () { t.classList.add('show'); }, 10); setTimeout(function () { t.remove(); }, 3200);
  }
  function mover(card, etapaId, tipo) {
    if (!card) return;
    if (tipo === 'ganada') return abrirGanar(card.dataset.id, card.dataset.titulo);
    if (tipo === 'perdida') return abrirPerder(card.dataset.id, card.dataset.titulo);
    var col = $('.kb-col[data-etapa="' + etapaId + '"]', board); if (!col) return;
    var origen = card.parentNode, next = card.nextSibling;
    $('.kb-body', col).prepend(card); recalc();
    fetch(board.dataset.url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify({ id: +card.dataset.id, etapa_id: +etapaId, csrf: board.dataset.csrf }) })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (!j.ok) throw new Error(j.error || 'Error'); toast('Oportunidad movida', true); setTimeout(function () { location.reload(); }, 500); })
      .catch(function (e) { origen.insertBefore(card, next); recalc(); toast(e.message, false); });
  }
  if (board) {
    var drag = null;
    board.addEventListener('dragstart', function (e) { var c = e.target.closest('.kb-card'); if (!c || c.getAttribute('draggable') !== 'true') return; drag = c; c.classList.add('drag'); e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', c.dataset.id); } catch (x) {} });
    board.addEventListener('dragend', function () { if (drag) drag.classList.remove('drag'); drag = null; $$('.kb-col', board).forEach(function (c) { c.classList.remove('over'); }); });
    board.addEventListener('dragover', function (e) { var col = e.target.closest('.kb-col'); if (!col || !drag) return; e.preventDefault(); $$('.kb-col', board).forEach(function (c) { c.classList.toggle('over', c === col); }); });
    board.addEventListener('drop', function (e) {
      var col = e.target.closest('.kb-col'); if (!col || !drag) return; e.preventDefault();
      var c = drag; col.classList.remove('over');
      if (c.closest('.kb-col') === col) return;
      mover(c, col.dataset.etapa, col.dataset.tipo);
    });
  }

  /* ---------- Contactos (modal) ---------- */
  var mc = document.getElementById('mdlContacto');
  if (mc) mc.addEventListener('show.bs.modal', function (ev) {
    var d = {}; try { d = JSON.parse((ev.relatedTarget && ev.relatedTarget.dataset.contacto) || '{}'); } catch (e) {}
    var set = function (i, v) { document.getElementById(i).value = v || ''; };
    set('kId', d.id || 0); set('kNombre', d.nombre); set('kCargo', d.cargo); set('kTel', d.telefono); set('kEmail', d.email); set('kNotas', d.notas);
    document.getElementById('kPrin').checked = !!(d.principal && +d.principal); document.getElementById('kTit').textContent = d.id ? 'Editar contacto' : 'Nuevo contacto';
  });
  var tabs = $('#cliTabs'); if (tabs && location.hash) { var b = $('[data-bs-target="' + location.hash + '"]', tabs); if (b) bootstrap.Tab.getOrCreateInstance(b).show(); }

  /* ---------- Cotización: filas y totales ---------- */
  var body = $('#cqBody');
  if (body) {
    var money = function (n) { return fmtNum(n); };
    var calc = function () {
      var sub = 0; $$('tr', body).forEach(function (tr) { var v = (parseFloat($('.cq-c', tr).value) || 0) * (parseFloat($('.cq-p', tr).value) || 0); $('.cq-i', tr).textContent = money(v); sub += v; });
      var dp = parseFloat($('#cqDesc').value) || 0, ip = parseFloat($('#cqIgv').value) || 0, d = sub * dp / 100, ig = (sub - d) * ip / 100;
      $('#tSub').textContent = money(sub); $('#tDesc').textContent = '− ' + money(d); $('#tIgv').textContent = money(ig); $('#tTot').textContent = window.money(sub - d + ig);
    };
    var fila = function () { var tr = document.createElement('tr'); tr.innerHTML = '<td><input class="form-control" name="item_desc[]" maxlength="255"></td><td><input type="number" class="form-control cq-c" name="item_cant[]" min="0.01" step="0.01" value="1"></td><td><input type="number" class="form-control cq-p" name="item_precio[]" min="0" step="0.01"></td><td class="text-end fw-semibold cq-i">0.00</td><td><button type="button" class="btn btn-ghost btn-sm text-danger cq-del"><i class="bi bi-x-lg"></i></button></td>'; body.appendChild(tr); return tr; };
    $('#cqAdd').addEventListener('click', function () { fila().querySelector('input').focus(); });
    document.getElementById('frmCot').addEventListener('input', calc);
    body.addEventListener('click', function (e) { var b = e.target.closest('.cq-del'); if (!b) return; if ($$('tr', body).length > 1) b.closest('tr').remove(); calc(); });
    if (!$$('tr', body).length) fila(); calc();
    var cli = $('#cqCli'), con = $('#cqCon'), op = $('#cqOp');
    var filtra = function () { [con, op].forEach(function (s) { var sel = s.dataset.sel; $$('option', s).forEach(function (o) { if (!o.value) return; var ok = o.dataset.cli === cli.value; o.hidden = !ok; o.disabled = !ok; }); if (s.selectedOptions[0] && s.selectedOptions[0].disabled) s.value = ''; if (sel && s.querySelector('option[value="' + sel + '"]:not([disabled])')) { s.value = sel; s.dataset.sel = ''; } }); };
    cli.addEventListener('change', function () { con.dataset.sel = op.dataset.sel = ''; con.value = op.value = ''; filtra(); }); filtra();
  }

  /* ---------- Gráficos del panel ---------- */
  var D = window.CRM_DATA;
  if (D && window.Chart) {
    var charts = [];
    var css = function (v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); };
    var draw = function () {
      charts.forEach(function (c) { c.destroy(); }); charts = [];
      var muted = css('--muted') || '#64748b', line = css('--line') || '#e2e8f0';
      Chart.defaults.color = muted; Chart.defaults.font.family = 'inherit';
      var f = document.getElementById('chForecast');
      if (f) charts.push(new Chart(f, { type: 'bar', data: { labels: D.forecast.map(function (x) { return x.label; }), datasets: [
        { label: 'Total', data: D.forecast.map(function (x) { return x.valor; }), backgroundColor: '#c7d2fe', borderRadius: 6 },
        { label: 'Ponderado', data: D.forecast.map(function (x) { return x.pond; }), backgroundColor: '#4f46e5', borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } }, scales: { x: { grid: { display: false } }, y: { grid: { color: line }, ticks: { callback: function (v) { return FMT.s + ' ' + (v >= 1000 ? Math.round(v / 1000) + 'k' : v); } } } } } }));
      var o = document.getElementById('chOrigen');
      if (o) charts.push(new Chart(o, { type: 'doughnut', data: { labels: D.origen.map(function (x) { return x.l; }), datasets: [{ data: D.origen.map(function (x) { return x.n; }), backgroundColor: ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '64%', plugins: { legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8 } } } } }));
    };
    draw(); new MutationObserver(draw).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
  }
})();

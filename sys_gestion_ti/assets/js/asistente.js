(function () {
  var $ = function (s) { return document.querySelector(s); };
  var SUG = ['Tickets creados por mes en los últimos 6 meses', 'Cumplimiento de SLA por cliente', 'Top 10 clientes con más tickets', 'Carga de trabajo por técnico', 'Tiempo promedio de resolución por categoría', 'Contratos que vencen en los próximos 60 días', 'Ingresos mensuales por plan de contrato', 'Repuestos con stock bajo', 'Tickets por canal de atención'];
  var COLORS = ['#5b5ef4', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899', '#84cc16', '#64748b'];
  var ST = null, last = null, chart = null, cur = null;
  var MONEY = /(importe|ingreso|monto|mensual|costo|precio|mano_obra|repuestos|tarifa|facturado|^total$)/i;
  var mdlCfg = new bootstrap.Modal($('#mdlCfg')), mdlEsq = new bootstrap.Modal($('#mdlEsq'));
  var isNum = function (v) { return typeof v === 'number'; };
  var isDate = function (v) { return typeof v === 'string' && /^\d{4}-\d{2}(-\d{2})?/.test(v); };
  var label = function (c) { return c.replace(/_/g, ' ').replace(/^./, function (x) { return x.toUpperCase(); }); };
  var cell = function (v, c) { return v === null || v === undefined ? '<span class="text-muted">—</span>' : isNum(v) ? (MONEY.test(c) ? money(v) : fmt(v, Number.isInteger(v) ? 0 : 2)) : esc(String(v)); };

  $('#iaChips').innerHTML = SUG.slice(0, 6).map(function (s) { return '<button type="button" class="ia-chip">' + esc(s) + '</button>'; }).join('');
  $('#iaChips').addEventListener('click', function (e) { var c = e.target.closest('.ia-chip'); if (c) { $('#iaQ').value = c.textContent; preguntar(true); } });
  $('#iaQ').addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); preguntar(); } });
  $('#iaQ').addEventListener('input', function (e) { e.target.style.height = 'auto'; e.target.style.height = Math.min(120, e.target.scrollHeight) + 'px'; });
  $('#frmAsk').onsubmit = function (e) { e.preventDefault(); preguntar(); };

  async function estado() {
    ST = await api('asistente/estado');
    $('#iaSetup').style.display = ST.listo ? 'none' : '';
    var lst = function (arr, fav) { return arr.map(function (x) { return '<div class="it" data-id="' + x.id + '"><i class="bi ' + (fav ? 'bi-star-fill text-warning' : 'bi-arrow-return-right text-muted') + '"></i><div class="tx">' + esc(x.titulo || x.pregunta) + '<small>' + esc(x.pregunta) + '</small></div><button data-fav="' + x.id + '" data-v="' + (fav ? 0 : 1) + '" title="' + (fav ? 'Quitar de favoritas' : 'Marcar como favorita') + '"><i class="bi bi-star"></i></button><button data-del="' + x.id + '" title="Eliminar"><i class="bi bi-x-lg"></i></button></div>'; }).join(''); };
    $('#iaFav').innerHTML = lst(ST.favoritos, true) || '<div class="text-muted small py-2">Marca con la estrella tus consultas frecuentes para repetirlas con un clic (sin gastar IA).</div>';
    $('#iaHis').innerHTML = lst(ST.historial, false) || '<div class="text-muted small py-2">Aún no hay consultas.</div>';
  }
  document.addEventListener('click', async function (e) {
    var side = e.target.closest('.ia-side .it'); if (!side) return;
    var f = e.target.closest('[data-fav]'), d = e.target.closest('[data-del]');
    try {
      if (f) { await api('asistente/favorito', { id: +f.dataset.fav, valor: +f.dataset.v }); estado(); if (cur && cur.id === +f.dataset.fav) { cur.favorito = +f.dataset.v; var b = $('#iaStar'); if (b) b.innerHTML = '<i class="bi bi-star' + (cur.favorito ? '-fill text-warning' : '') + '"></i>'; } return; }
      if (d) { await api('asistente/borrar', { id: +d.dataset.del }); estado(); return; }
      cargarGuardada(+side.dataset.id);
    } catch (x) { toast(x.message, 'danger'); }
  });

  function cargando(txt) { $('#iaOut').innerHTML = '<div class="card-ui ia-res"><div class="ia-load"><div class="ia-dots"><span></span><span></span><span></span></div><div id="iaLt">' + esc(txt) + '</div></div></div>'; }
  async function preguntar(auto) {
    var q = $('#iaQ').value.trim(); if (q.length < 4) return toast('Escribe tu pregunta', 'warning');
    if (!ST.listo) { toast('Primero configura el proveedor de IA', 'warning'); cargarCfg(); return mdlCfg.show(); }
    $('#iaGo').disabled = true; cargando('Interpretando tu pregunta…');
    var t = setTimeout(function () { var e = $('#iaLt'); if (e) e.textContent = 'Consultando tus datos…'; }, 2500);
    try {
      var r = await api('asistente/preguntar', { pregunta: q, previo: !auto && last ? { pregunta: last.pregunta, sql: last.sql } : null });
      if (r.mensaje) { $('#iaOut').innerHTML = '<div class="card-ui ia-res"><div class="d-flex gap-3"><i class="bi bi-info-circle fs-4 text-primary"></i><div>' + esc(r.mensaje) + '</div></div></div>'; }
      else { r.pregunta = q; mostrar(r); resumir(r.id); }
      estado();
    } catch (x) { $('#iaOut').innerHTML = '<div class="card-ui ia-res"><div class="d-flex gap-3"><i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i><div><b>No se pudo completar la consulta</b><div class="small text-muted mt-1">' + esc(x.message) + '</div></div></div></div>'; }
    clearTimeout(t); $('#iaGo').disabled = false;
  }
  async function cargarGuardada(id) {
    cargando('Ejecutando consulta guardada…');
    try { var r = await api('asistente/ejecutar', { id: id }); $('#iaQ').value = r.pregunta; mostrar(r); resumir(r.id); estado(); }
    catch (x) { $('#iaOut').innerHTML = '<div class="card-ui ia-res text-danger">' + esc(x.message) + '</div>'; }
  }

  function elegirGrafico(r) {
    var cols = r.columnas, f = r.filas; if (!f.length || cols.length < 2) return null;
    var g = r.grafico || {}, xi = cols.indexOf(g.x), yi = cols.indexOf(g.y);
    if (xi < 0 || yi < 0 || !isNum(f[0][yi])) { xi = cols.findIndex(function (c, i) { return !isNum(f[0][i]); }); yi = cols.findIndex(function (c, i) { return i !== xi && isNum(f[0][i]); }); if (xi < 0) xi = 0; }
    var ok = f.find(function (x) { return x[yi] !== null; }); if (yi < 0 || !ok || !isNum(ok[yi])) return null;
    var tipo = g.tipo && g.tipo !== 'none' ? g.tipo : (isDate(f[0][xi]) ? 'line' : (f.length <= 40 ? 'bar' : 'none'));
    if (g.tipo === 'none' && !isDate(f[0][xi]) && f.length > 1 && f.length <= 15) tipo = 'bar';
    if (tipo === 'none' || f.length < 2) return null;
    if (tipo === 'pie' && f.length > 8) tipo = 'bar';
    return { xi: xi, yi: yi, tipo: tipo };
  }
  function dibujar(r, cfg, tipo) {
    if (chart) { chart.destroy(); chart = null; }
    var cv = $('#iaCv'); if (!cv || !cfg) return;
    var lab = r.filas.map(function (x) { return isDate(x[cfg.xi]) ? String(x[cfg.xi]).slice(0, 10) : String(x[cfg.xi] == null ? '—' : x[cfg.xi]); }), val = r.filas.map(function (x) { return x[cfg.yi]; });
    var pie = tipo === 'pie', line = tipo === 'line', yn = r.columnas[cfg.yi];
    Chart.defaults.font.family = "'Inter',sans-serif"; Chart.defaults.color = (getComputedStyle(document.documentElement).getPropertyValue('--muted') || '#64748b').trim();
    chart = new Chart(cv, { type: pie ? 'doughnut' : tipo, data: { labels: lab, datasets: [{ label: label(yn), data: val, backgroundColor: pie ? COLORS : (line ? 'rgba(91,94,244,.15)' : '#5b5ef4'), borderColor: pie ? 'transparent' : '#5b5ef4', borderWidth: 2, borderRadius: line || pie ? 0 : 8, fill: line, tension: .3, pointRadius: line ? 3 : 0 }] },
      options: { responsive: true, maintainAspectRatio: false, indexAxis: !pie && !line && lab.length > 6 && lab.some(function (l) { return l.length > 14; }) ? 'y' : 'x', plugins: { legend: { display: pie, position: 'right' }, tooltip: { callbacks: { label: function (c) { var pp = c.parsed, v = typeof pp === 'number' ? pp : (c.chart.options.indexAxis === 'y' ? pp.x : pp.y); return ' ' + (MONEY.test(yn) ? money(v) : fmt(v, Number.isInteger(v) ? 0 : 2)); } } } }, scales: pie ? {} : { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.2)' } } } } });
  }

  function mostrar(r) {
    last = r; cur = r; var cfg = elegirGrafico(r), tipo = cfg && cfg.tipo;
    var th = r.columnas.map(function (c, i) { return '<th class="' + (r.filas.length && isNum(r.filas[0][i]) ? 'text-end' : '') + '">' + esc(label(c)) + '</th>'; }).join('');
    var tb = r.filas.map(function (f) { return '<tr>' + f.map(function (v, i) { return '<td class="' + (isNum(v) ? 'text-end' : '') + '">' + cell(v, r.columnas[i]) + '</td>'; }).join('') + '</tr>'; }).join('') || '<tr><td colspan="' + r.columnas.length + '"><div class="empty-row"><i class="bi bi-inbox"></i>La consulta no devolvió resultados</div></td></tr>';
    $('#iaOut').innerHTML = '<div class="card-ui ia-res">' +
      '<div class="d-flex justify-content-between align-items-start gap-2 flex-wrap"><div><h5>' + esc(r.titulo) + '</h5><div class="ia-meta">' + r.filas.length + ' fila(s)' + (r.truncado ? ' (límite de 500 alcanzado)' : '') + ' · ' + r.ms + ' ms · «' + esc(r.pregunta || '') + '»</div></div>' +
      '<div class="d-flex gap-2"><button class="btn btn-soft btn-sm" id="iaStar" title="Favorita"><i class="bi bi-star' + (r.favorito ? '-fill text-warning' : '') + '"></i></button><a class="btn btn-soft btn-sm" href="' + IA.base + '/asistente/csv?id=' + r.id + '"><i class="bi bi-download"></i> CSV</a><button class="btn btn-soft btn-sm" id="iaSqlB"><i class="bi bi-code-slash"></i> SQL</button></div></div>' +
      '<div id="iaSum"></div>' +
      (cfg ? '<div class="d-flex gap-1 justify-content-end"><div class="seg" id="iaSeg"><button data-t="bar">Barras</button><button data-t="line">Líneas</button><button data-t="pie">Torta</button></div></div><div class="ia-chart"><canvas id="iaCv"></canvas></div>' : '') +
      '<div class="ia-tbl"><table class="table-ui"><thead><tr>' + th + '</tr></thead><tbody>' + tb + '</tbody></table></div>' +
      '<pre class="ia-sql" id="iaSql" style="display:none">' + esc(r.sql) + '</pre></div>';
    if (cfg) { dibujar(r, cfg, tipo); var seg = $('#iaSeg'); var on = seg.querySelector('[data-t=' + tipo + ']'); if (on) on.classList.add('on'); seg.onclick = function (e) { var b = e.target.closest('[data-t]'); if (!b) return; seg.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); }); dibujar(r, cfg, b.dataset.t); }; }
    $('#iaSqlB').onclick = function () { var p = $('#iaSql'); p.style.display = p.style.display === 'none' ? '' : 'none'; };
    $('#iaStar').onclick = async function () { try { var v = cur.favorito ? 0 : 1; await api('asistente/favorito', { id: cur.id, valor: v }); cur.favorito = v; $('#iaStar').innerHTML = '<i class="bi bi-star' + (v ? '-fill text-warning' : '') + '"></i>'; estado(); if (v) toast('Guardada en favoritas'); } catch (x) { toast(x.message, 'danger'); } };
    var rs = document.querySelector('.ia-res'); if (rs) rs.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
  async function resumir(id) {
    if (!ST.cfg.resumen) return; var box = $('#iaSum'); if (!box) return;
    box.innerHTML = '<div class="ia-sum"><i class="bi bi-stars"></i><div class="ia-dots mt-1"><span></span><span></span><span></span></div></div>';
    try { var r = await api('asistente/resumir', { id: id }); if (cur && cur.id === id && $('#iaSum')) $('#iaSum').innerHTML = '<div class="ia-sum"><i class="bi bi-stars"></i><div>' + esc(r.resumen) + '</div></div>'; }
    catch (x) { if ($('#iaSum')) $('#iaSum').innerHTML = ''; }
  }

  // ---- Configuración ----
  function cargarCfg() {
    var c = ST.cfg, P = ST.presets;
    $('#cProv').innerHTML = Object.keys(P).map(function (k) { return '<option value="' + k + '">' + esc(P[k].nombre) + '</option>'; }).join(''); $('#cProv').value = c.proveedor;
    $('#cUrl').value = c.url; $('#cMod').value = c.modelo; $('#cKey').value = ''; $('#cRes').checked = !!c.resumen; $('#cErr').textContent = ''; ayuda();
  }
  function ayuda() { var p = ST.presets[$('#cProv').value]; $('#cAyuda').textContent = p.ayuda; $('#cKey').disabled = !p.clave; $('#cKeyH').textContent = !p.clave ? 'Este proveedor no necesita clave.' : (ST.cfg.clave_puesta && $('#cProv').value === ST.cfg.proveedor ? 'Hay una clave guardada. Déjalo vacío para conservarla.' : 'Pega aquí tu clave. Se guarda cifrada y no se vuelve a mostrar.'); }
  $('#cProv').onchange = function () { var p = ST.presets[$('#cProv').value]; if (p.url) { $('#cUrl').value = p.url; $('#cMod').value = p.modelo; } ayuda(); };
  $('#btnCfg').onclick = function () { cargarCfg(); mdlCfg.show(); };
  var datosCfg = function () { return { proveedor: $('#cProv').value, url: $('#cUrl').value, modelo: $('#cMod').value, clave: $('#cKey').value, resumen: $('#cRes').checked ? 1 : 0 }; };
  $('#frmCfg').onsubmit = async function (e) { e.preventDefault(); $('#cErr').textContent = ''; try { await api('asistente/guardar_config', datosCfg()); await estado(); mdlCfg.hide(); toast('Configuración guardada'); } catch (x) { $('#cErr').className = 'col-12 text-danger small'; $('#cErr').textContent = x.message; } };
  $('#cTest').onclick = async function () { $('#cErr').textContent = ''; var b = $('#cTest'); b.disabled = true; b.innerHTML = '<i class="bi bi-hourglass-split"></i> Probando…';
    try { await api('asistente/guardar_config', datosCfg()); await estado(); var r = await api('asistente/probar', {}); $('#cErr').className = 'col-12 text-success small'; $('#cErr').textContent = '✔ Conexión correcta (respuesta: ' + r.respuesta + ')'; }
    catch (x) { $('#cErr').className = 'col-12 text-danger small'; $('#cErr').textContent = x.message; }
    b.disabled = false; b.innerHTML = '<i class="bi bi-plug"></i> Probar conexión'; };
  $('#btnEsq').onclick = async function () { mdlEsq.show(); $('#esqTxt').textContent = 'Cargando…'; $('#esqTxt').textContent = await (await fetch(IA.base + '/asistente/esquema', { credentials: 'same-origin' })).text(); };

  $('#frmQuick').onsubmit = async function (e) { e.preventDefault(); var k = $('#qKey').value.trim(), b = $('#qGo'); $('#qErr').textContent = ''; if (k.length < 8) { $('#qErr').textContent = 'Pega la clave completa.'; return; }
    b.disabled = true; b.textContent = 'Probando…';
    try { await iaActivar(k); toast('IA activada', 'success'); await estado(); } catch (x) { $('#qErr').textContent = x.message; } b.disabled = false; b.textContent = 'Activar'; };
  estado();
})();

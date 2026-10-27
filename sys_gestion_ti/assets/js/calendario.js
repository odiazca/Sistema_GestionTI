(function () {
  var grid = document.getElementById('calGrid'); if (!grid) return;
  var hoy = new Date(), cur = new Date(hoy.getFullYear(), hoy.getMonth(), 1), sel = iso(hoy), eventos = [], ocultas = {};
  var MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'], DIAS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
  function iso(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
  function rango() { var ini = new Date(cur), off = (ini.getDay() + 6) % 7; ini.setDate(1 - off); var fin = new Date(ini); fin.setDate(ini.getDate() + 41); return [ini, fin]; }
  function cargar() {
    var r = rango(); document.getElementById('calTitulo').textContent = MESES[cur.getMonth()] + ' ' + cur.getFullYear();
    fetch(CAL_URL + '?desde=' + iso(r[0]) + '&hasta=' + iso(r[1]), { credentials: 'same-origin' }).then(function (x) { return x.json(); }).then(function (d) { eventos = d; pintar(); }).catch(function () { eventos = []; pintar(); });
  }
  function visibles() { return eventos.filter(function (e) { return !ocultas[e.tipo]; }); }
  function pintar() {
    var r = rango(), ev = visibles(), por = {}, h = DIAS.map(function (d) { return '<div class="cal-h">' + d + '</div>'; }).join('');
    ev.forEach(function (e) { (por[e.fecha] = por[e.fecha] || []).push(e); });
    for (var i = 0; i < 42; i++) {
      var d = new Date(r[0]); d.setDate(r[0].getDate() + i); var k = iso(d), l = por[k] || [], fuera = d.getMonth() !== cur.getMonth();
      h += '<div class="cal-c' + (fuera ? ' out' : '') + (k === iso(hoy) ? ' today' : '') + (k === sel ? ' sel' : '') + '" data-d="' + k + '"><span class="n">' + d.getDate() + '</span>';
      l.slice(0, 3).forEach(function (e) { h += '<span class="cal-e' + (e.hecho ? ' done' : '') + '" style="--c:' + e.color + '" title="' + esc(e.titulo) + '">' + (e.hora ? e.hora + ' ' : '') + esc(e.titulo) + '</span>'; });
      if (l.length > 3) h += '<span class="cal-more">+' + (l.length - 3) + ' más</span>';
      h += '</div>';
    }
    grid.innerHTML = h; dia();
  }
  function dia() {
    var l = visibles().filter(function (e) { return e.fecha === sel; }), p = sel.split('-');
    document.getElementById('calDiaTitulo').textContent = parseInt(p[2], 10) + ' de ' + MESES[parseInt(p[1], 10) - 1];
    document.getElementById('calDia').innerHTML = l.length ? l.map(function (e) {
      return '<a class="cal-item' + (e.hecho ? ' done' : '') + '" href="' + esc(e.url) + '" style="--c:' + e.color + '"><b>' + (e.hora ? '<em>' + e.hora + '</em> ' : '') + esc(e.titulo) + '</b><small>' + esc(e.sub) + '</small></a>';
    }).join('') : '<div class="text-muted small text-center py-4"><i class="bi bi-calendar2-x fs-3 d-block mb-1"></i>Sin eventos este día</div>';
  }
  grid.addEventListener('click', function (e) { var c = e.target.closest('.cal-c'); if (!c) return; sel = c.dataset.d; grid.querySelectorAll('.cal-c.sel').forEach(function (x) { x.classList.remove('sel'); }); c.classList.add('sel'); dia(); });
  document.getElementById('calPrev').onclick = function () { cur.setMonth(cur.getMonth() - 1); cargar(); };
  document.getElementById('calNext').onclick = function () { cur.setMonth(cur.getMonth() + 1); cargar(); };
  document.getElementById('calHoy').onclick = function () { cur = new Date(hoy.getFullYear(), hoy.getMonth(), 1); sel = iso(hoy); cargar(); };
  document.getElementById('calCapas').addEventListener('click', function (e) { var b = e.target.closest('.cal-capa'); if (!b) return; b.classList.toggle('active'); ocultas[b.dataset.k] = !b.classList.contains('active'); pintar(); });
  cargar();
})();

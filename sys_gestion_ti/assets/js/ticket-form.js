(function () {
  var cli = document.getElementById('cliente_id'), act = document.getElementById('activo_id'), pr = document.getElementById('prioridad');
  var time = document.getElementById('slaTime'), box = document.getElementById('slaPrev'), note = document.getElementById('slaNote');
  var base = window.TK.base;

  function fmt(h) {
    if (h >= 24) { var d = Math.floor(h / 24), r = Math.round(h % 24); return d + ' d' + (r ? ' ' + r + ' h' : ''); }
    return (Math.round(h * 10) / 10) + ' h';
  }
  function preview() {
    if (!time || !pr) return;
    if (!cli.value) { time.textContent = '—'; return; }
    var h = Math.max(2, base * (window.TK.factor[pr.value] || 1));
    time.textContent = fmt(h);
    var d = new Date(Date.now() + h * 3600000);
    note.textContent = 'Vence aprox. el ' + d.toLocaleDateString('es-PE') + ' ' + d.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    box.className = 'sla-box ' + (pr.value === 'critica' ? 'bad' : pr.value === 'alta' ? 'warn' : 'ok');
  }
  cli.addEventListener('change', function () {
    act.innerHTML = '<option value="">— Ninguno / no aplica —</option>';
    if (!cli.value) { preview(); return; }
    fetch(window.TK.urlActivos + cli.value, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
      base = d.sla_base;
      d.activos.forEach(function (a) {
        var o = document.createElement('option'); o.value = a.id;
        o.textContent = a.codigo + ' · ' + a.tipo + ' ' + (a.marca || '') + ' ' + (a.modelo || '');
        act.appendChild(o);
      });
      preview();
    });
  });
  if (pr) pr.addEventListener('change', preview);
  preview();
})();

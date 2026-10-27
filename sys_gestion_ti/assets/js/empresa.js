(function () {
  function fmt(n, dec, sd, sm) { var p = Math.abs(n).toFixed(dec).split('.'); var ent = p[0]; if (sm) ent = ent.replace(/\B(?=(\d{3})+(?!\d))/g, sm); return (n < 0 ? '-' : '') + ent + (dec > 0 ? sd + p[1] : ''); }
  var f = document.getElementById('fMon');
  if (f) {
    var $ = function (i) { return document.getElementById(i); };
    var miles = function () { var v = $('mSm').value; return v === 'space' ? ' ' : v === 'none' ? '' : v; };
    var money = function (n) { var v = fmt(n, +$('mDec').value, $('mSd').value, miles()), sp = $('mEsp').checked ? ' ' : '', s = $('mSim').value; return $('mPos').value === 'despues' ? v + sp + s : s + sp + v; };
    var pv = function () { $('pv1').textContent = money(1234567.891); $('pv2').textContent = money(98765432.1); $('pv3').textContent = fmt(1500.5, +$('mDec').value, $('mSd').value, miles()); var m = $('pvMoney'); if (m) m.textContent = money(1234567.891); };
    $('mCod').addEventListener('change', function () { var d = MON_DEF[this.value]; if (!d) return; $('mSim').value = d.s; $('mDec').value = d.d; $('mSd').value = d.sd; $('mSm').value = d.sm; $('mPos').value = d.p; pv(); });
    f.addEventListener('input', pv); f.addEventListener('change', pv); pv();
  }
  var n = document.querySelector('[data-pv=nombre]'), e = document.querySelector('[data-pv=eslogan]');
  if (n) n.addEventListener('input', function () { document.getElementById('pvNombre').textContent = n.value || 'Tu empresa'; });
  if (e) e.addEventListener('input', function () { document.getElementById('pvEslogan').textContent = e.value; });
  var fe = document.getElementById('rFec');
  if (fe) fe.addEventListener('change', function () { var d = new Date(2026, 11, 31), p = function (x) { return String(x).padStart(2, '0'); }, m = { d: p(31), m: 12, Y: 2026 }; document.getElementById('pvFecha').textContent = fe.value.replace(/[dmY]/g, function (c) { return m[c]; }); });
})();

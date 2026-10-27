// Pad de firma sobre <canvas>: mouse, lápiz y táctil (Pointer Events)
(function () {
  var c = document.getElementById('firmaCanvas'), modal = document.getElementById('mFirma'); if (!c || !modal) return;
  var ctx, dibujando = false, hay = false, ult = null;
  function ajustar() {
    var r = c.getBoundingClientRect(), k = window.devicePixelRatio || 1; c.width = r.width * k; c.height = 190 * k; c.style.height = '190px';
    ctx = c.getContext('2d'); ctx.scale(k, k); ctx.lineWidth = 2.4; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#0f172a'; hay = false;
    modal.querySelector('.firma-hint').style.display = '';
  }
  function pos(e) { var r = c.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }
  c.addEventListener('pointerdown', function (e) { e.preventDefault(); c.setPointerCapture(e.pointerId); dibujando = true; ult = pos(e); ctx.beginPath(); ctx.arc(ult.x, ult.y, 1.2, 0, 7); ctx.fill(); hay = true; modal.querySelector('.firma-hint').style.display = 'none'; });
  c.addEventListener('pointermove', function (e) { if (!dibujando) return; var p = pos(e); ctx.beginPath(); ctx.moveTo(ult.x, ult.y); ctx.lineTo(p.x, p.y); ctx.stroke(); ult = p; });
  ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (n) { c.addEventListener(n, function () { dibujando = false; }); });
  document.getElementById('firmaLimpiar').addEventListener('click', ajustar);
  modal.addEventListener('shown.bs.modal', ajustar);
  document.getElementById('fFirma').addEventListener('submit', function (e) {
    if (!hay) { e.preventDefault(); alert('Dibuja la firma antes de guardar.'); return; }
    // fondo blanco para que sea legible al imprimir
    var t = document.createElement('canvas'); t.width = c.width; t.height = c.height; var x = t.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, t.width, t.height); x.drawImage(c, 0, 0);
    document.getElementById('firmaData').value = t.toDataURL('image/png');
  });
})();

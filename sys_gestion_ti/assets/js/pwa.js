// Registro del service worker (PWA). Silencioso si el navegador no lo soporta.
(function () {
  if (!('serviceWorker' in navigator)) return;
  var base = (document.querySelector('meta[name="base-url"]') || {}).content || '';
  window.addEventListener('load', function () { navigator.serviceWorker.register(base + '/sw.js').catch(function () {}); });
  document.addEventListener('click', function (e) { var a = e.target.closest('a[href$="/logout"]'); if (a && navigator.serviceWorker.controller) navigator.serviceWorker.controller.postMessage('limpiar'); });
  var ev; window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); ev = e; var b = document.getElementById('btnInstalar'); if (b) b.classList.remove('d-none'); });
  document.addEventListener('click', function (e) { var b = e.target.closest('#btnInstalar'); if (b && ev) { ev.prompt(); ev = null; b.classList.add('d-none'); } });
})();

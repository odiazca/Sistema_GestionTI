/* Utilidades compartidas por el Asistente IA y el Chat IA */
(function () {
  var W = window;
  W.esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  W.fmt = function (n, d) { return Number(n).toLocaleString('es-PE', { minimumFractionDigits: d || 0, maximumFractionDigits: d == null ? 2 : d }); };
  if (!W.FMT) W.money = function (n) { return 'S/ ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };   // con Empresa configurada, money() viene del layout

  W.toast = function (msg, type) {
    type = type || 'success';
    var ic = { success: 'bi-check-circle-fill', danger: 'bi-exclamation-octagon-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' }[type] || 'bi-info-circle-fill';
    var st = document.querySelector('.toast-stack'); if (!st) return;
    var d = document.createElement('div'); d.className = 'toast-ui toast-' + type; d.setAttribute('role', 'alert');
    d.innerHTML = '<i class="bi ' + ic + '"></i><span>' + esc(msg) + '</span><button type="button" onclick="this.parentNode.remove()">&times;</button>';
    st.appendChild(d); setTimeout(function () { d.classList.add('out'); setTimeout(function () { d.remove(); }, 400); }, 4000);
  };

  /** api('asistente/estado') -> GET ; api('asistente/preguntar', {...}) -> POST JSON con CSRF */
  W.api = async function (path, body) {
    var opt = { credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (body !== undefined) { opt.method = 'POST'; opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(Object.assign({ csrf: W.IA.csrf }, body)); }
    var res, j;
    try { res = await fetch(W.IA.base + '/' + path, opt); } catch (e) { throw new Error('No se pudo conectar con el servidor.'); }
    var txt = await res.text();
    try { j = JSON.parse(txt); } catch (e) { throw new Error('Respuesta inesperada del servidor (' + res.status + ').'); }
    if (!j.ok) throw new Error(j.error || 'Ocurrió un error.');
    return j;
  };

  /** Activa Gemini con solo la clave (valores gratuitos por defecto) y prueba la conexión */
  W.iaActivar = async function (clave) {
    await api('asistente/guardar_config', { proveedor: 'gemini', url: 'https://generativelanguage.googleapis.com/v1beta/openai', modelo: 'gemini-3.5-flash-lite', clave: clave, resumen: 1 });
    await api('asistente/probar', {});
  };
})();

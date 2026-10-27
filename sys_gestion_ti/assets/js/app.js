(function () {
  var root = document.documentElement;
  var sb = document.getElementById('sidebar');
  var bd = document.getElementById('sbBackdrop');
  var btn = document.getElementById('btnSidebar');
  var mobile = function () { return window.matchMedia('(max-width: 991.98px)').matches; };

  if (btn) btn.addEventListener('click', function () {
    if (mobile()) { sb.classList.toggle('open'); bd.classList.toggle('show'); }
    else {
      root.classList.toggle('sb-collapsed');
      try { localStorage.setItem('td_collapsed', root.classList.contains('sb-collapsed') ? '1' : '0'); } catch (e) {}
      setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 320);
    }
  });
  if (bd) bd.addEventListener('click', function () { sb.classList.remove('open'); bd.classList.remove('show'); });

  var th = document.getElementById('btnTheme');
  function icon() { if (th) th.innerHTML = '<i class="bi ' + (root.getAttribute('data-theme') === 'dark' ? 'bi-sun' : 'bi-moon-stars') + '"></i>'; }
  icon();
  if (th) th.addEventListener('click', function () {
    var dark = root.getAttribute('data-theme') === 'dark';
    if (dark) root.removeAttribute('data-theme'); else root.setAttribute('data-theme', 'dark');
    try { localStorage.setItem('td_theme', dark ? 'light' : 'dark'); } catch (e) {}
    icon();
    if (window.TD_redraw) window.TD_redraw();
  });

  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault(); var i = document.querySelector('.search-ui input'); if (i) i.focus();
    }
  });
})();

// Toasts: se cierran solos
document.querySelectorAll('.toast-ui').forEach(function (t, i) {
  setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 400); }, 4500 + i * 400);
});
// Modal de confirmación de eliminación (genérico)
document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-delete]');
  if (!b) return;
  var m = document.getElementById('delModal'); if (!m) return;
  m.querySelector('form').action = b.getAttribute('data-delete');
  m.querySelector('.del-name').textContent = b.getAttribute('data-name') || 'este registro';
  bootstrap.Modal.getOrCreateInstance(m).show();
});

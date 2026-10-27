/* Service worker TechDesk Pro: archivos estáticos en caché y «Mi jornada» disponible sin conexión */
const V = 'techdesk-v9', STATIC = V + '-static', PAGES = V + '-pages';
self.addEventListener('install', e => { self.skipWaiting(); e.waitUntil(caches.open(STATIC).then(c => c.addAll(['assets/img/icon-192.png']).catch(() => {}))); });
self.addEventListener('activate', e => { e.waitUntil(caches.keys().then(ks => Promise.all(ks.filter(k => !k.startsWith(V)).map(k => caches.delete(k)))).then(() => self.clients.claim())); });
self.addEventListener('fetch', e => {
  const r = e.request; if (r.method !== 'GET') return; const u = new URL(r.url); if (u.origin !== location.origin) return;
  const base = new URL('./', self.registration.scope).pathname;
  if (u.pathname.startsWith(base + 'assets/')) {            // estáticos: caché primero, actualiza en segundo plano
    e.respondWith(caches.open(STATIC).then(c => c.match(r).then(h => { const f = fetch(r).then(n => { if (n.ok) c.put(r, n.clone()); return n; }).catch(() => h); return h || f; })));
  } else if (r.mode === 'navigate' && u.pathname.replace(/\/$/, '') === base.replace(/\/$/, '') + '/jornada') {   // Mi jornada: red primero, caché si no hay conexión
    e.respondWith(fetch(r).then(n => { if (n.ok) { const k = n.clone(); caches.open(PAGES).then(c => c.put('jornada', k)); } return n; }).catch(() => caches.match('jornada').then(h => h || new Response('<meta charset="utf-8"><meta name="viewport" content="width=device-width"><body style="font-family:system-ui;text-align:center;padding:3rem"><h2>Sin conexión</h2><p>Abre «Mi jornada» una vez con internet para verla aquí sin conexión.</p>', { headers: { 'Content-Type': 'text/html; charset=utf-8' } }))));
  }
});
self.addEventListener('message', e => { if (e.data === 'limpiar') caches.delete(PAGES); });

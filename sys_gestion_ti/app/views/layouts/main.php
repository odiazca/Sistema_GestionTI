<?php
$u = Auth::user();
$actual = strtolower(explode('/', trim($_GET['url'] ?? 'dashboard', '/'))[0] ?: 'dashboard');
$hora = (int)date('G');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(empresa_nombre()) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<script>try{if(localStorage.getItem('td_theme')==='dark')document.documentElement.setAttribute('data-theme','dark');if(localStorage.getItem('td_collapsed')==='1')document.documentElement.classList.add('sb-collapsed');}catch(e){}</script>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json"><meta name="theme-color" content="#0b1437"><meta name="base-url" content="<?= BASE_URL ?>"><link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="mobile-web-app-capable" content="yes">
<?php $__f = fmt_cfg(); ?>
<script>window.FMT = <?= json_encode(['s' => $__f['simbolo'], 'dec' => $__f['dec'], 'sd' => $__f['sd'], 'sm' => $__f['sm'], 'pos' => $__f['pos'], 'sp' => $__f['espacio']]) ?>;
window.fmtNum = function (n, d) { d = d == null ? FMT.dec : d; var p = Math.abs(Number(n) || 0).toFixed(d).split('.'), e = p[0]; if (FMT.sm) e = e.replace(/\B(?=(\d{3})+(?!\d))/g, FMT.sm); return ((Number(n) || 0) < 0 ? '-' : '') + e + (d > 0 ? FMT.sd + p[1] : ''); };
window.money = function (n, d) { var v = fmtNum(n, d), sp = FMT.sp ? '\u00a0' : ''; return FMT.pos === 'despues' ? v + sp + FMT.s : FMT.s + sp + v; };</script>
<?= brand_css() ?>
</head>
<body class="app-body">

<div class="sb-backdrop" id="sbBackdrop"></div>

<!-- ============ SIDEBAR ============ -->
<aside class="sidebar" id="sidebar">
  <div class="sb-head">
    <a href="<?= url('dashboard') ?>" class="brand">
      <?= brand_mark() ?>
      <span class="brand-text"><b><?= e(empresa_nombre()) ?></b><small><?= e(cfg('empresa_eslogan', 'Soporte TI')) ?></small></span>
    </a>
  </div>

  <nav class="sb-nav">
    <?php foreach ($menu as $seccion => $items):
      $items = array_filter($items, fn($it) => empty($it['roles']) || in_array($u['rol'], $it['roles'], true));
      if (!$items) continue; ?>
      <div class="sb-section"><?= e($seccion) ?></div>
      <?php foreach ($items as $it):
        $activo = $actual === $it['slug'];
        $badge = !empty($it['badge']) ? ($badges[$it['badge']] ?? 0) : 0; ?>
        <a href="<?= url($it['slug']) ?>" class="sb-link <?= $activo ? 'active' : '' ?>" title="<?= e($it['label']) ?>">
          <i class="bi <?= e($it['icon']) ?>"></i>
          <span class="sb-label"><?= e($it['label']) ?></span>
          <?php if ($badge): ?><span class="sb-badge <?= in_array($it['badge'], ['stock_bajo', 'fac_vencidas'], true) ? 'warn' : '' ?>"><?= (int)$badge ?></span>
          <?php elseif (!empty($it['tag'])): ?><span class="sb-tag"><?= e($it['tag']) ?></span>
          <?php elseif (empty($it['ready'])): ?><span class="sb-soon">pronto</span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sb-foot">
    <div class="sb-support">
      <div class="sb-support-ic"><i class="bi bi-lightning-charge-fill"></i></div>
      <div><b>Estado del servicio</b><small><span class="dot-live"></span> Todo operativo</small></div>
    </div>
  </div>
</aside>

<!-- ============ MAIN ============ -->
<div class="main-wrap">
  <header class="topbar">
    <button class="icon-btn" id="btnSidebar" aria-label="Menú"><i class="bi bi-list"></i></button>

    <div class="search-ui d-none d-md-flex">
      <i class="bi bi-search"></i>
      <input type="text" placeholder="Buscar tickets, clientes, equipos…" aria-label="Buscar">
      <kbd>Ctrl K</kbd>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2">
      <a href="<?= url('tickets') ?>" class="btn btn-brand btn-sm d-none d-sm-inline-flex align-items-center gap-1"><i class="bi bi-plus-lg"></i> Nuevo ticket</a>

      <button class="icon-btn" id="btnTheme" aria-label="Tema"><i class="bi bi-moon-stars"></i></button>

      <div class="dropdown">
        <button class="icon-btn position-relative" data-bs-toggle="dropdown" aria-label="Notificaciones">
          <i class="bi bi-bell"></i><?php if (!empty($notifs)): ?><span class="notif-dot"></span><?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end dd-ui p-0" style="width:340px">
          <div class="dd-head">Notificaciones <?php if (!empty($notifs)): ?><span class="chip chip-soft ms-auto"><?= count($notifs) ?> aviso(s)</span><?php endif; ?></div>
          <?php foreach (($notifs ?? []) as $nt): ?><a class="dd-item" href="<?= url($nt['l']) ?>"><i class="bi <?= $nt['ic'] ?> text-<?= $nt['c'] ?>"></i><div><b><?= e($nt['t']) ?></b><small><?= e($nt['d']) ?></small></div></a><?php endforeach; ?>
          <?php if (empty($notifs)): ?><div class="p-4 text-center text-muted small"><i class="bi bi-check2-circle fs-3 d-block text-success mb-1"></i>Todo en orden. No hay avisos pendientes.</div><?php endif; ?>
        </div>
      </div>

      <div class="dropdown">
        <button class="user-btn" data-bs-toggle="dropdown">
          <span class="avatar" style="background:<?= e($u['color']) ?>"><?= e(iniciales($u['nombre'])) ?></span>
          <span class="d-none d-md-block text-start lh-sm"><b><?= e($u['nombres']) ?></b><small><?= e($u['rol']) ?></small></span>
          <i class="bi bi-chevron-down d-none d-md-block small text-muted"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end dd-ui p-0" style="width:240px">
          <div class="dd-user">
            <span class="avatar avatar-lg" style="background:<?= e($u['color']) ?>"><?= e(iniciales($u['nombre'])) ?></span>
            <div><b><?= e($u['nombre']) ?></b><small><?= e($u['email']) ?></small></div>
          </div>
          <a class="dd-link" href="<?= url('configuracion?tab=seguridad') ?>"><i class="bi bi-person"></i> Mi perfil y contraseña</a>
          <?php if ($u['rol'] === 'Administrador'): ?><a class="dd-link" href="<?= url('configuracion') ?>"><i class="bi bi-gear"></i> Configuración</a><?php endif; ?>
          <a class="dd-link text-danger" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
        </div>
      </div>
    </div>
  </header>

  <main class="content">
    <?= $content ?>
  </main>

  <footer class="app-footer">
    <span>© <?= date('Y') ?> <?= e(empresa_nombre()) ?> · <?= APP_SUBTITLE ?></span>
    <span>v<?= APP_VERSION ?></span>
  </footer>
</div>

<?php $flashes = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); ?>
<div class="toast-stack">
  <?php foreach ($flashes as $f): $ic = ['success'=>'bi-check-circle-fill','danger'=>'bi-exclamation-octagon-fill','warning'=>'bi-exclamation-triangle-fill','info'=>'bi-info-circle-fill'][$f['type']] ?? 'bi-info-circle-fill'; ?>
  <div class="toast-ui toast-<?= e($f['type']) ?>" role="alert"><i class="bi <?= $ic ?>"></i><span><?= e($f['msg']) ?></span><button type="button" onclick="this.parentNode.remove()">&times;</button></div>
  <?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if ($u['rol'] === 'Administrador'): ?>
<script>window.IA = {base: <?= json_encode(BASE_URL) ?>, csrf: <?= json_encode(Auth::csrf()) ?>, empresa: <?= json_encode(cfg('empresa_nombre', APP_NAME)) ?>};</script>
<script src="<?= asset('js/ia-common.js') ?>?v=<?= APP_VERSION ?>"></script>
<script src="<?= asset('js/chat.js') ?>?v=<?= APP_VERSION ?>"></script>
<?php endif; ?>
<script src="<?= asset('js/pwa.js') ?>"></script>
<?php if (isset($scripts)) echo $scripts; ?>
<?php if (in_array($u['rol'] ?? '', ['Administrador', 'Supervisor', 'Comercial'], true)): ?><script src="<?= asset('js/crm.js') ?>"></script><?php endif; ?>
</body>
</html>

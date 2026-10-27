<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · Portal <?= e(empresa_nombre()) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<style>.pt-nav{background:linear-gradient(135deg,#0b1437,#312e81);color:#fff}.pt-nav a{color:#c7d2fe;text-decoration:none;font-weight:600;font-size:.88rem;padding:.5rem .85rem;border-radius:10px}.pt-nav a:hover,.pt-nav a.on{background:#ffffff1f;color:#fff}.pt-wrap{max-width:1100px;margin:0 auto;padding:1.5rem 1rem 3rem}.pt-hero{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:20px;padding:1.4rem 1.6rem}</style>
<script>try{if(localStorage.getItem('td_theme')==='dark')document.documentElement.setAttribute('data-theme','dark')}catch(e){}</script>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json"><meta name="theme-color" content="#0b1437"><meta name="base-url" content="<?= BASE_URL ?>"><link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="mobile-web-app-capable" content="yes">
<?= brand_css() ?>
</head><body style="background:var(--bg)">
<?php $act = strtolower(explode('/', trim($_GET['url'] ?? 'portal', '/'))[1] ?? 'index'); $nav = ['index' => ['Inicio', 'bi-house'], 'tickets' => ['Mis tickets', 'bi-ticket-perforated'], 'nuevo' => ['Nuevo ticket', 'bi-plus-circle'], 'facturas' => ['Facturas', 'bi-receipt']]; ?>
<header class="pt-nav"><div class="pt-wrap d-flex align-items-center gap-3 flex-wrap" style="padding-top:.7rem;padding-bottom:.7rem">
  <a href="<?= url('portal') ?>" class="d-flex align-items-center gap-2 text-white p-0"><?= brand_mark("width:36px;height:36px") ?><b style="font-size:1.05rem"><?= e(empresa_nombre()) ?></b><small class="opacity-75 d-none d-sm-inline">Portal del cliente</small></a>
  <nav class="d-flex gap-1 flex-wrap ms-md-3"><?php foreach ($nav as $k => $n): $href = $k === 'index' ? 'portal' : 'portal/' . $k; ?><a href="<?= url($href) ?>" class="<?= $act === $k || ($k === 'index' && $act === '') ? 'on' : '' ?>"><i class="bi <?= $n[1] ?> me-1"></i><?= $n[0] ?></a><?php endforeach; ?></nav>
  <div class="ms-auto dropdown"><button class="user-btn" data-bs-toggle="dropdown" style="background:#ffffff1a;color:#fff"><span class="avatar" style="background:<?= e($u['color']) ?>"><?= e(iniciales($u['nombre'])) ?></span><span class="d-none d-md-block text-start lh-sm"><b><?= e($u['nombre']) ?></b><small class="d-block opacity-75"><?= e($cli['razon_social']) ?></small></span></button>
    <div class="dropdown-menu dropdown-menu-end dd-ui"><a class="dd-link" href="<?= url('portal/clave') ?>"><i class="bi bi-key"></i> Cambiar contraseña</a><a class="dd-link text-danger" href="<?= url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></div></div>
</div></header>
<main class="pt-wrap"><?= $content ?></main>
<?php $flashes = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); ?>
<div class="toast-stack"><?php foreach ($flashes as $f): $ic = ['success' => 'bi-check-circle-fill', 'danger' => 'bi-exclamation-octagon-fill', 'warning' => 'bi-exclamation-triangle-fill', 'info' => 'bi-info-circle-fill'][$f['type']] ?? 'bi-info-circle-fill'; ?>
  <div class="toast-ui toast-<?= e($f['type']) ?>" role="alert"><i class="bi <?= $ic ?>"></i><span><?= e($f['msg']) ?></span><button type="button" onclick="this.parentNode.remove()">&times;</button></div><?php endforeach; ?></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/pwa.js') ?>"></script>
</body></html>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · <?= e(empresa_nombre()) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json"><meta name="theme-color" content="#0b1437"><meta name="base-url" content="<?= BASE_URL ?>"><link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="mobile-web-app-capable" content="yes">
<?= brand_css() ?>
</head>
<body class="login-body">
<div class="login-wrap">

  <!-- Panel visual -->
  <section class="login-hero">
    <div class="hero-orb orb-1"></div><div class="hero-orb orb-2"></div><div class="hero-grid"></div>
    <div class="hero-inner">
      <div class="brand brand-lg">
        <?= brand_mark() ?>
        <span class="brand-text"><b><?= e(empresa_nombre()) ?></b><small><?= e(cfg('empresa_eslogan', APP_SUBTITLE)) ?></small></span>
      </div>

      <div class="hero-copy">
        <span class="chip chip-glass"><i class="bi bi-stars me-1"></i>Plataforma integral de soporte TI</span>
        <h1>Resuelve más rápido.<br><span>Soporta con confianza.</span></h1>
        <p>Tickets, SLA, activos, visitas técnicas y contratos en un solo lugar. Todo tu servicio de soporte, bajo control.</p>
      </div>

      <div class="hero-cards">
        <div class="float-card fc-1">
          <div class="fc-icon" style="background:#ef444422;color:#fca5a5"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <div><b>TCK-01231</b><small>Servidor sin respaldo · Crítica</small></div>
          <span class="dot-live"></span>
        </div>
        <div class="float-card fc-2">
          <div class="fc-icon" style="background:#10b98122;color:#6ee7b7"><i class="bi bi-check-circle-fill"></i></div>
          <div><b>SLA cumplido</b><small>94.8% este mes</small></div>
        </div>
        <div class="float-card fc-3">
          <div class="fc-icon" style="background:#38bdf822;color:#7dd3fc"><i class="bi bi-geo-alt-fill"></i></div>
          <div><b>Visita técnica</b><small>Hoy 15:30 · Clínica San Gabriel</small></div>
        </div>
      </div>

      <div class="hero-stats">
        <div><b>24/7</b><span>Monitoreo</span></div>
        <div><b>+230</b><span>Tickets gestionados</span></div>
        <div><b>98%</b><span>Satisfacción</span></div>
      </div>
    </div>
  </section>

  <!-- Formulario -->
  <section class="login-panel">
    <div class="login-card">
      <div class="brand brand-dark d-lg-none mb-4">
        <?= brand_mark() ?>
        <span class="brand-text"><b><?= e(empresa_nombre()) ?></b><small><?= e(cfg('empresa_eslogan', APP_SUBTITLE)) ?></small></span>
      </div>
      <h2>Bienvenido de nuevo 👋</h2>
      <p class="text-muted mb-4">Ingresa tus credenciales para acceder al panel de soporte.</p>

      <?php if ($error): ?>
        <div class="alert-ui alert-ui-danger shake" role="alert"><i class="bi bi-exclamation-octagon-fill"></i><span><?= e($error) ?></span></div>
      <?php endif; ?>

      <form method="post" action="<?= url('login') ?>" id="loginForm" autocomplete="on" novalidate>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

        <label class="field-label" for="login">Usuario o correo</label>
        <div class="input-ui">
          <i class="bi bi-person"></i>
          <input type="text" id="login" name="login" value="<?= e($old) ?>" placeholder="admin" required autofocus autocomplete="username">
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <label class="field-label m-0" for="password">Contraseña</label>
          <a href="#" class="small link-brand" onclick="return false;">¿Olvidaste tu contraseña?</a>
        </div>
        <div class="input-ui mt-2">
          <i class="bi bi-lock"></i>
          <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
          <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
        </div>

        <div class="form-check mt-3">
          <input class="form-check-input" type="checkbox" id="remember">
          <label class="form-check-label small text-muted" for="remember">Mantener la sesión en este equipo</label>
        </div>

        <button type="submit" class="btn btn-brand btn-lg w-100 mt-4" id="btnLogin">
          <span class="btn-label">Ingresar al sistema <i class="bi bi-arrow-right ms-1"></i></span>
          <span class="spinner-border spinner-border-sm d-none"></span>
        </button>
      </form>

      <div class="demo-box mt-4">
        <i class="bi bi-info-circle"></i>
        <span>Demo: <b>admin</b> / <b>Admin123*</b></span>
      </div>

      <p class="text-center text-muted small mt-4 mb-0">© <?= date('Y') ?> <?= e(empresa_nombre()) ?> · v<?= APP_VERSION ?></p>
    </div>
  </section>
</div>

<script>
document.getElementById('togglePass').addEventListener('click', function () {
  var i = document.getElementById('password'), s = i.type === 'password';
  i.type = s ? 'text' : 'password';
  this.innerHTML = '<i class="bi ' + (s ? 'bi-eye-slash' : 'bi-eye') + '"></i>';
});
document.getElementById('loginForm').addEventListener('submit', function () {
  var b = document.getElementById('btnLogin');
  b.querySelector('.btn-label').classList.add('d-none');
  b.querySelector('.spinner-border').classList.remove('d-none');
  b.disabled = true;
});
</script>
</body>
</html>

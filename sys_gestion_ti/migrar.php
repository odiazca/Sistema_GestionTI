<?php
/**
 * Migración para bases ya instaladas: agrega los módulos nuevos sin borrar datos.
 * Ejecutar una vez: http://localhost/sys_gestion_ti/migrar.php
 */
require __DIR__ . '/config/config.php';
require __DIR__ . '/database/migraciones.php';
header('Content-Type: text/html; charset=utf-8');
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $log = ejecutar_migraciones($pdo);
    $ok = true;
} catch (Throwable $e) { $ok = false; $err = $e->getMessage(); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Migración · <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:linear-gradient(135deg,#0b1437,#312e81);min-height:100vh;display:grid;place-items:center;font-family:system-ui}</style></head>
<body><div class="card border-0 shadow-lg rounded-4 p-4" style="max-width:520px;width:92%">
<?php if ($ok): ?>
  <h3 class="fw-bold text-success">✔ Migración completada</h3>
  <ul class="small text-muted"><?php foreach ($log as $l) echo '<li>' . htmlspecialchars($l) . '</li>'; ?></ul>
  <a class="btn btn-primary btn-lg w-100" href="<?= BASE_URL ?>/">Ir al sistema</a>
<?php else: ?>
  <h3 class="fw-bold text-danger">Error en la migración</h3><pre class="small bg-light p-3 rounded"><?= htmlspecialchars($err) ?></pre>
  <p class="small text-muted mb-0">¿Ya ejecutaste install.php? Verifica MySQL y config/config.php.</p>
<?php endif; ?></div></body></html>

<?php
/**
 * v9: portal del cliente, notificaciones (correo/WhatsApp), firma digital y cotización → orden.
 * Idempotente. Se usa desde install.php y migrar.php.
 */
function v9_col_existe(PDO $pdo, string $tabla, string $col): bool
{
    $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?'); $s->execute([$tabla, $col]);
    return (bool)$s->fetchColumn();
}
function instalar_v9(PDO $pdo): array
{
    $log = [];
    if (!v9_col_existe($pdo, 'usuarios', 'cliente_id')) $pdo->exec('ALTER TABLE usuarios ADD COLUMN cliente_id INT UNSIGNED NULL');
    foreach (['cotizacion_id' => 'INT UNSIGNED NULL', 'firma' => 'MEDIUMTEXT NULL', 'firma_nombre' => 'VARCHAR(100) NULL', 'firma_fecha' => 'DATETIME NULL'] as $c => $def)
        if (!v9_col_existe($pdo, 'ordenes_servicio', $c)) $pdo->exec("ALTER TABLE ordenes_servicio ADD COLUMN $c $def");
    $pdo->exec("INSERT IGNORE INTO roles(nombre, descripcion) VALUES('Cliente','Acceso al portal de autoservicio del cliente')");
    $pdo->exec("CREATE TABLE IF NOT EXISTS notif_envios (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      canal ENUM('correo','whatsapp') NOT NULL DEFAULT 'correo',
      destinatario VARCHAR(160) NOT NULL,
      asunto VARCHAR(200) NOT NULL,
      cuerpo MEDIUMTEXT NULL,
      estado ENUM('enviado','simulado','error') NOT NULL DEFAULT 'simulado',
      error VARCHAR(255) NULL,
      cliente_id INT UNSIGNED NULL,
      ticket_id INT UNSIGNED NULL,
      cotizacion_id INT UNSIGNED NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_nv_fecha (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'Portal del cliente, notificaciones y firma digital listos.';

    // usuario demo del portal (solo si no existe ninguno)
    if (!(int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE cliente_id IS NOT NULL")->fetchColumn()) {
        $c = $pdo->query("SELECT id, razon_social, contacto, email FROM clientes WHERE estado=1 ORDER BY id LIMIT 1")->fetch();
        $rol = (int)$pdo->query("SELECT id FROM roles WHERE nombre='Cliente'")->fetchColumn();
        if ($c && $rol) {
            $nom = trim((string)$c['contacto']) ?: 'Contacto'; $p = preg_split('/\s+(?=\S+$)/', $nom, 2);
            $pdo->prepare('INSERT IGNORE INTO usuarios(rol_id,nombres,apellidos,usuario,email,password,cargo,avatar_color,cliente_id) VALUES(?,?,?,?,?,?,?,?,?)')
                ->execute([$rol, $p[0], $p[1] ?? '', 'cliente.demo', 'portal.demo@' . 'cliente.pe', password_hash('Cliente123*', PASSWORD_DEFAULT), 'Contacto de ' . $c['razon_social'], '#0ea5e9', $c['id']]);
            $log[] = 'Usuario demo del portal: cliente.demo / Cliente123*';
        }
    }
    return $log;
}

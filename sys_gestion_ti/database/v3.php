<?php
/**
 * Módulos v3: Órdenes de Servicio, repuestos por orden y contenido de la Base de Conocimiento.
 * Se usa desde install.php (instalación nueva) y migrar.php (base ya instalada).
 */
function instalar_v3(PDO $pdo): array
{
    $log = [];
    $pdo->exec("CREATE TABLE IF NOT EXISTS ordenes_servicio (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      codigo VARCHAR(20) NOT NULL UNIQUE,
      ticket_id INT UNSIGNED NULL,
      cliente_id INT UNSIGNED NOT NULL,
      activo_id INT UNSIGNED NULL,
      tecnico_id INT UNSIGNED NULL,
      tipo ENUM('correctiva','preventiva','instalacion','diagnostico') NOT NULL DEFAULT 'correctiva',
      estado ENUM('pendiente','en_curso','completada','cancelada') NOT NULL DEFAULT 'pendiente',
      descripcion_trabajo TEXT NULL,
      diagnostico TEXT NULL,
      solucion TEXT NULL,
      horas DECIMAL(5,2) NOT NULL DEFAULT 0,
      tarifa_hora DECIMAL(8,2) NOT NULL DEFAULT 45,
      conformidad TINYINT(1) NOT NULL DEFAULT 0,
      fecha_programada DATETIME NULL,
      fecha_inicio DATETIME NULL,
      fecha_fin DATETIME NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_os_estado (estado),
      FOREIGN KEY (cliente_id) REFERENCES clientes(id),
      FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
      FOREIGN KEY (activo_id) REFERENCES activos(id) ON DELETE SET NULL,
      FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS ordenes_repuestos (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      orden_id INT UNSIGNED NOT NULL,
      repuesto_id INT UNSIGNED NOT NULL,
      cantidad INT NOT NULL DEFAULT 1,
      precio_unit DECIMAL(10,2) NOT NULL DEFAULT 0,
      FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
      FOREIGN KEY (repuesto_id) REFERENCES repuestos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = 'Tablas de órdenes de servicio listas.';

    // ---- Artículos de conocimiento con contenido real
    $kb = [
      'Cómo restablecer contraseña de Active Directory' => "1. Abre «Usuarios y equipos de Active Directory» con una cuenta de administrador.\n2. Busca al usuario y haz clic derecho > Restablecer contraseña.\n3. Define una contraseña temporal y marca «El usuario debe cambiar la contraseña en el siguiente inicio de sesión».\n4. Si la cuenta aparece bloqueada, desmarca «Desbloquear cuenta».\n5. Confirma con el usuario que puede ingresar y cierra el ticket.\n\nNota: nunca envíes la contraseña por correo; comunícala por teléfono.",
      'Configuración de VPN con FortiClient' => "1. Instala FortiClient VPN (versión gratuita) desde el portal de descargas.\n2. Nueva conexión VPN > SSL-VPN.\n3. Gateway remoto: el dominio entregado por el área de TI. Puerto 443.\n4. Autenticación: solicitar usuario y contraseña; si hay token, habilitar doble factor.\n5. Prueba acceso a un recurso interno (ping al servidor de archivos).\n\nProblemas frecuentes: certificado no confiable (importar CA), puerto 443 bloqueado por el proveedor.",
      'Solución a impresora offline en red' => "1. Verifica que la impresora tenga IP y responda al ping.\n2. En el equipo: Panel de control > Dispositivos e impresoras > desmarca «Usar impresora sin conexión».\n3. Revisa que el puerto TCP/IP apunte a la IP correcta (reserva DHCP recomendada).\n4. Reinicia el servicio «Cola de impresión» (spooler).\n5. Si persiste, elimina y vuelve a instalar el controlador oficial.",
      'Checklist de mantenimiento preventivo' => "Equipos de escritorio y laptops:\n- Limpieza interna y externa, pasta térmica si aplica.\n- Actualizaciones de Windows y antivirus.\n- Estado de disco (SMART) y espacio libre.\n- Revisión de copias de seguridad.\n\nRedes: revisión de switches, etiquetado de patch panel y respaldo de configuración.\nServidores: logs, temperatura, RAID y pruebas de restauración.",
      'Respaldo con Veeam: buenas prácticas' => "- Regla 3-2-1: 3 copias, 2 medios distintos, 1 fuera de sitio.\n- Programar trabajos fuera del horario laboral.\n- Verificar el resultado cada mañana (correo de reporte).\n- Probar la restauración completa cada trimestre.\n- Cifrar los respaldos y proteger las credenciales del repositorio.",
    ];
    $up = $pdo->prepare('UPDATE kb_articulos SET contenido=? WHERE titulo=? AND contenido LIKE ?');
    foreach ($kb as $t => $c) $up->execute([$c, $t, 'Contenido del art%']);

    // ---- Datos de demostración (solo si no hay órdenes)
    if ((int)$pdo->query('SELECT COUNT(*) FROM ordenes_servicio')->fetchColumn() === 0) {
        mt_srand(77);
        $tecnicos = array_column($pdo->query("SELECT u.id FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE r.nombre='Técnico' ORDER BY u.id")->fetchAll(), 'id');
        $clientes = array_column($pdo->query('SELECT id FROM clientes ORDER BY id LIMIT 8')->fetchAll(), 'id');
        $reps = $pdo->query('SELECT id, precio FROM repuestos ORDER BY id')->fetchAll();
        $tipos = ['correctiva', 'preventiva', 'instalacion', 'diagnostico'];
        $trabajos = ['Reemplazo de disco duro y reinstalación del sistema', 'Mantenimiento preventivo de equipos de oficina', 'Instalación y configuración de switch PoE', 'Diagnóstico de lentitud en servidor de archivos',
                     'Cambio de fuente de poder en estación de trabajo', 'Configuración de punto de acceso WiFi', 'Limpieza y mantenimiento de impresoras', 'Reubicación de puntos de red'];
        $estados = ['completada', 'completada', 'completada', 'completada', 'completada', 'completada', 'en_curso', 'en_curso', 'pendiente', 'pendiente', 'pendiente', 'cancelada'];
        $insO = $pdo->prepare('INSERT INTO ordenes_servicio(codigo,cliente_id,tecnico_id,tipo,estado,descripcion_trabajo,diagnostico,solucion,horas,tarifa_hora,conformidad,fecha_programada,fecha_inicio,fecha_fin) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $insR = $pdo->prepare('INSERT INTO ordenes_repuestos(orden_id,repuesto_id,cantidad,precio_unit) VALUES(?,?,?,?)');
        foreach ($estados as $i => $es) {
            $prog = strtotime('-' . ($es === 'completada' ? mt_rand(3, 60) : ($es === 'pendiente' ? -mt_rand(0, 5) : mt_rand(0, 1))) . ' days 9:00');
            $ini = in_array($es, ['en_curso', 'completada']) ? date('Y-m-d H:i:s', $prog + 1800) : null;
            $h = $es === 'completada' ? mt_rand(15, 70) / 10 : 0;
            $fin = $es === 'completada' ? date('Y-m-d H:i:s', $prog + 1800 + (int)($h * 3600)) : null;
            $insO->execute([sprintf('OS-2026-%03d', $i + 1), $clientes[$i % count($clientes)], $tecnicos[$i % max(1, count($tecnicos))] ?? null, $tipos[$i % 4], $es,
                $trabajos[$i % count($trabajos)], $es === 'completada' ? 'Se identificó el origen de la falla tras las pruebas en sitio.' : null,
                $es === 'completada' ? 'Trabajo ejecutado y probado con el usuario. Equipo operativo.' : null, $h, 45, $es === 'completada' ? 1 : 0, date('Y-m-d H:i:s', $prog), $ini, $fin]);
            $oid = (int)$pdo->lastInsertId();
            if ($es === 'completada' && mt_rand(0, 1)) { $r = $reps[mt_rand(0, count($reps) - 1)]; $insR->execute([$oid, $r['id'], mt_rand(1, 2), $r['precio']]); }
        }
        $log[] = 'Datos de demostración de órdenes de servicio creados.';
    }
    return $log;
}

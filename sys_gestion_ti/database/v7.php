<?php
/**
 * Módulo CRM: leads, contactos, pipeline de oportunidades, actividades y cotizaciones.
 * Idempotente. Se usa desde install.php (instalación nueva) y migrar.php (base ya instalada).
 */
function instalar_crm(PDO $pdo): array
{
    $log = [];
    $t = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_etapas (
      id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(40) NOT NULL,
      orden TINYINT NOT NULL DEFAULT 0,
      probabilidad TINYINT NOT NULL DEFAULT 0,
      color VARCHAR(9) NOT NULL DEFAULT '#4f46e5',
      tipo ENUM('abierta','ganada','perdida') NOT NULL DEFAULT 'abierta'
    ) $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_leads (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      codigo VARCHAR(20) NOT NULL UNIQUE,
      empresa VARCHAR(150) NOT NULL,
      contacto VARCHAR(100) NULL,
      cargo VARCHAR(80) NULL,
      email VARCHAR(120) NULL,
      telefono VARCHAR(25) NULL,
      rubro VARCHAR(80) NULL,
      origen ENUM('web','referido','llamada','evento','redes','correo','otro') NOT NULL DEFAULT 'web',
      interes VARCHAR(200) NULL,
      estado ENUM('nuevo','contactado','calificado','descartado','convertido') NOT NULL DEFAULT 'nuevo',
      score TINYINT UNSIGNED NOT NULL DEFAULT 0,
      responsable_id INT UNSIGNED NULL,
      notas TEXT NULL,
      motivo_descarte VARCHAR(200) NULL,
      cliente_id INT UNSIGNED NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_lead_estado (estado), INDEX idx_lead_resp (responsable_id)
    ) $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_contactos (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      cliente_id INT UNSIGNED NOT NULL,
      nombre VARCHAR(100) NOT NULL,
      cargo VARCHAR(80) NULL,
      email VARCHAR(120) NULL,
      telefono VARCHAR(25) NULL,
      principal TINYINT(1) NOT NULL DEFAULT 0,
      notas VARCHAR(255) NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_cc_cliente (cliente_id),
      FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
    ) $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_oportunidades (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      codigo VARCHAR(20) NOT NULL UNIQUE,
      titulo VARCHAR(180) NOT NULL,
      cliente_id INT UNSIGNED NOT NULL,
      contacto_id INT UNSIGNED NULL,
      lead_id INT UNSIGNED NULL,
      etapa_id TINYINT UNSIGNED NOT NULL,
      tipo ENUM('contrato','renovacion','proyecto','equipos','servicio') NOT NULL DEFAULT 'contrato',
      valor DECIMAL(12,2) NOT NULL DEFAULT 0,
      probabilidad TINYINT UNSIGNED NOT NULL DEFAULT 10,
      fecha_cierre_estimada DATE NULL,
      fecha_cierre_real DATE NULL,
      responsable_id INT UNSIGNED NULL,
      origen VARCHAR(20) NULL,
      estado ENUM('abierta','ganada','perdida') NOT NULL DEFAULT 'abierta',
      motivo_perdida VARCHAR(200) NULL,
      contrato_id INT UNSIGNED NULL,
      descripcion TEXT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_op_estado (estado), INDEX idx_op_etapa (etapa_id), INDEX idx_op_resp (responsable_id),
      FOREIGN KEY (cliente_id) REFERENCES clientes(id),
      FOREIGN KEY (etapa_id) REFERENCES crm_etapas(id)
    ) $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_actividades (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      tipo ENUM('llamada','reunion','correo','tarea','visita','nota') NOT NULL DEFAULT 'tarea',
      asunto VARCHAR(180) NOT NULL,
      detalle TEXT NULL,
      fecha DATETIME NULL,
      completada TINYINT(1) NOT NULL DEFAULT 0,
      completada_at DATETIME NULL,
      resultado TEXT NULL,
      auto TINYINT(1) NOT NULL DEFAULT 0,
      cliente_id INT UNSIGNED NULL,
      lead_id INT UNSIGNED NULL,
      oportunidad_id INT UNSIGNED NULL,
      responsable_id INT UNSIGNED NULL,
      creado_por INT UNSIGNED NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_act_resp (responsable_id, completada, fecha), INDEX idx_act_cli (cliente_id), INDEX idx_act_lead (lead_id), INDEX idx_act_op (oportunidad_id)
    ) $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_cotizaciones (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      codigo VARCHAR(20) NOT NULL UNIQUE,
      cliente_id INT UNSIGNED NOT NULL,
      oportunidad_id INT UNSIGNED NULL,
      contacto_id INT UNSIGNED NULL,
      fecha DATE NOT NULL,
      valida_hasta DATE NOT NULL,
      estado ENUM('borrador','enviada','aprobada','rechazada','vencida') NOT NULL DEFAULT 'borrador',
      descuento_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
      igv_pct DECIMAL(5,2) NOT NULL DEFAULT 18,
      subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
      descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
      igv DECIMAL(12,2) NOT NULL DEFAULT 0,
      total DECIMAL(12,2) NOT NULL DEFAULT 0,
      condiciones TEXT NULL,
      notas VARCHAR(255) NULL,
      creado_por INT UNSIGNED NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_cot_estado (estado),
      FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ) $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_cotizacion_items (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      cotizacion_id INT UNSIGNED NOT NULL,
      descripcion VARCHAR(255) NOT NULL,
      cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
      precio_unit DECIMAL(12,2) NOT NULL DEFAULT 0,
      FOREIGN KEY (cotizacion_id) REFERENCES crm_cotizaciones(id) ON DELETE CASCADE
    ) $t");
    $log[] = 'Tablas del CRM listas.';

    // ---- Etapas del pipeline
    if ((int)$pdo->query('SELECT COUNT(*) FROM crm_etapas')->fetchColumn() === 0) {
        $st = $pdo->prepare('INSERT INTO crm_etapas(nombre,orden,probabilidad,color,tipo) VALUES(?,?,?,?,?)');
        foreach ([['Prospección', 1, 10, '#64748b', 'abierta'], ['Calificación', 2, 25, '#0ea5e9', 'abierta'], ['Propuesta', 3, 50, '#8b5cf6', 'abierta'],
                  ['Negociación', 4, 75, '#f59e0b', 'abierta'], ['Ganada', 5, 100, '#10b981', 'ganada'], ['Perdida', 6, 0, '#ef4444', 'perdida']] as $e) $st->execute($e);
        $log[] = 'Etapas del pipeline creadas.';
    }

    // ---- Rol Comercial + usuario de demostración
    $rol = $pdo->query("SELECT id FROM roles WHERE nombre='Comercial'")->fetchColumn();
    if (!$rol) { $pdo->prepare('INSERT INTO roles(nombre,descripcion) VALUES(?,?)')->execute(['Comercial', 'Gestiona CRM, oportunidades y cotizaciones']); $rol = (int)$pdo->lastInsertId(); $log[] = 'Rol «Comercial» creado.'; }
    if (!(int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE usuario='vcastro'")->fetchColumn()) {
        $pdo->prepare('INSERT INTO usuarios(rol_id,nombres,apellidos,usuario,email,password,cargo,avatar_color) VALUES(?,?,?,?,?,?,?,?)')
            ->execute([$rol, 'Valeria', 'Castro', 'vcastro', 'valeria@techdesk.pe', password_hash('Comercial123*', PASSWORD_DEFAULT), 'Ejecutiva Comercial', '#ec4899']);
        $log[] = 'Usuario demo comercial: vcastro / Comercial123*';
    }

    // ---- Datos de demostración (solo si el CRM está vacío)
    if ((int)$pdo->query('SELECT COUNT(*) FROM crm_oportunidades')->fetchColumn() === 0 && (int)$pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn() > 0) {
        mt_srand(2026);
        $q = fn($s) => $pdo->query($s)->fetchAll(PDO::FETCH_COLUMN);
        $vend = $q("SELECT u.id FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE r.nombre IN ('Administrador','Supervisor','Comercial') AND u.estado=1 ORDER BY u.id");
        $vend = $vend ?: [1];
        $cli = $pdo->query('SELECT id, razon_social, contacto, telefono, email FROM clientes ORDER BY id')->fetchAll();
        $etapas = array_column($pdo->query('SELECT id, nombre FROM crm_etapas')->fetchAll(), 'id', 'nombre');

        // Contactos por cliente
        $cargos = ['Gerente General', 'Jefe de TI', 'Administrador', 'Gerente de Operaciones', 'Contador'];
        $nom = ['Roberto Salas', 'Patricia Luna', 'Diego Paredes', 'Carmen Rojas', 'Fernando Díaz', 'Silvia Mora', 'Héctor Vega', 'Lorena Campos'];
        $ic = $pdo->prepare('INSERT INTO crm_contactos(cliente_id,nombre,cargo,email,telefono,principal) VALUES(?,?,?,?,?,?)');
        foreach ($cli as $i => $c) {
            if ($c['contacto']) $ic->execute([$c['id'], $c['contacto'], 'Contacto principal', $c['email'], $c['telefono'], 1]);
            $n = $nom[$i % count($nom)]; $dom = strtolower(preg_replace('/[^a-z]/i', '', explode(' ', $c['razon_social'])[0]));
            $ic->execute([$c['id'], $n, $cargos[$i % count($cargos)], strtolower(str_replace(' ', '.', iconv('UTF-8', 'ASCII//TRANSLIT', $n))) . "@$dom.pe", '9' . mt_rand(10000000, 99999999), $c['contacto'] ? 0 : 1]);
        }

        // Leads
        $leads = [
          ['Clínica Santa Elena', 'Dra. Rocío Vidal', 'Directora Médica', 'rvidal@santaelena.pe', '987654321', 'Salud', 'referido', 'Soporte 24/7 y migración de historias clínicas', 'calificado', 'hace 3 days'],
          ['Distribuidora Andina SAC', 'Marco Tello', 'Gerente de Logística', 'mtello@andina.pe', '976543210', 'Comercio', 'web', 'Cotización de red WiFi para almacén', 'contactado', 'hace 5 days'],
          ['Colegio Nuevo Horizonte', 'Ana Beltrán', 'Directora', 'direccion@nuevohorizonte.edu.pe', '965432109', 'Educación', 'evento', 'Laboratorio de cómputo 30 equipos', 'nuevo', 'hace 1 days'],
          ['Transportes Rápido Sur', 'Iván Cabrera', 'Jefe de TI', 'icabrera@rapidosur.pe', '954321098', 'Transporte', 'llamada', 'Mantenimiento preventivo de flota de laptops', 'contactado', 'hace 8 days'],
          ['Hotel Mirador del Valle', 'Sonia Prado', 'Administradora', 'sprado@miradorvalle.pe', '943210987', 'Turismo', 'redes', 'Cableado estructurado y WiFi para huéspedes', 'nuevo', 'hace 0 days'],
          ['Constructora Pilar', 'Raúl Gamarra', 'Gerente General', 'rgamarra@pilar.pe', '932109876', 'Construcción', 'referido', 'Servidor de archivos y respaldo en la nube', 'calificado', 'hace 11 days'],
          ['Bodega El Roble', 'Julia Meza', 'Propietaria', 'julia@elroble.pe', '921098765', 'Retail', 'correo', 'Punto de venta y red básica', 'descartado', 'hace 20 days'],
          ['Notaría Villanueva', 'Dr. Pablo Villanueva', 'Notario', 'pvillanueva@notaria.pe', '910987654', 'Servicios', 'web', 'Soporte mensual y ciberseguridad', 'nuevo', 'hace 2 days'],
        ];
        $il = $pdo->prepare('INSERT INTO crm_leads(codigo,empresa,contacto,cargo,email,telefono,rubro,origen,interes,estado,score,responsable_id,created_at,motivo_descarte) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($leads as $i => $l) {
            $score = min(100, ['referido' => 30, 'evento' => 20, 'web' => 15, 'llamada' => 15, 'correo' => 10, 'redes' => 10][$l[6]] + 10 + 10 + (preg_match('/Gerente|Direct|Jefe|Notario|Propiet/i', $l[2]) ? 15 : 0) + 10 + ($l[8] === 'calificado' ? 20 : ($l[8] === 'contactado' ? 10 : 0)));
            $d = (int)preg_replace('/\D/', '', $l[9]);
            $il->execute([sprintf('LD-%04d', $i + 1), $l[0], $l[1], $l[2], $l[3], $l[4], $l[5], $l[6], $l[7], $l[8], $score, $vend[$i % count($vend)], date('Y-m-d H:i:s', strtotime("-$d days -" . mt_rand(1, 8) . ' hours')), $l[8] === 'descartado' ? 'Ya cuenta con proveedor de TI' : null]);
        }

        // Oportunidades
        $ops = [
          // titulo, idxCliente, etapa, tipo, valor, cierre(+d), origen
          ['Renovación contrato Premium · 12 meses', 0, 'Negociación', 'renovacion', 21600, 12, 'referido'],
          ['Migración de servidores a la nube', 1, 'Propuesta', 'proyecto', 38500, 25, 'referido'],
          ['Cableado estructurado sede nueva', 2, 'Calificación', 'proyecto', 14200, 40, 'web'],
          ['Contrato Business · soporte mensual', 3, 'Propuesta', 'contrato', 11400, 18, 'llamada'],
          ['Venta de 15 laptops corporativas', 4, 'Negociación', 'equipos', 27800, 7, 'evento'],
          ['Upgrade de seguridad perimetral', 5, 'Prospección', 'proyecto', 16900, 55, 'web'],
          ['Contrato Premium 24/7', 6, 'Calificación', 'contrato', 21600, 30, 'redes'],
          ['Renovación contrato Business', 7, 'Propuesta', 'renovacion', 11400, 10, 'referido'],
          ['Implementación de telefonía IP', 0, 'Prospección', 'proyecto', 9800, 60, 'correo'],
          ['Mantenimiento preventivo anual', 1, 'Ganada', 'servicio', 8400, -12, 'llamada'],
          ['Servidor de archivos NAS', 2, 'Ganada', 'equipos', 12600, -25, 'web'],
          ['Contrato Básico soporte remoto', 3, 'Ganada', 'contrato', 5040, -40, 'referido'],
          ['Auditoría de red y licencias', 4, 'Perdida', 'servicio', 6500, -18, 'web'],
          ['Cambio de switches core', 5, 'Perdida', 'equipos', 19800, -33, 'evento'],
        ];
        $io = $pdo->prepare('INSERT INTO crm_oportunidades(codigo,titulo,cliente_id,contacto_id,etapa_id,tipo,valor,probabilidad,fecha_cierre_estimada,fecha_cierre_real,responsable_id,origen,estado,motivo_perdida,descripcion,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $pr = $pdo->query('SELECT id, probabilidad, tipo FROM crm_etapas')->fetchAll(PDO::FETCH_UNIQUE);
        foreach ($ops as $i => $o) {
            $c = $cli[$o[1] % count($cli)]; $e = $etapas[$o[2]]; $tipo = $pr[$e]['tipo']; $cierre = date('Y-m-d', strtotime(($o[5] >= 0 ? '+' : '') . $o[5] . ' days'));
            $ct = $pdo->prepare('SELECT id FROM crm_contactos WHERE cliente_id=? ORDER BY principal DESC, id LIMIT 1'); $ct->execute([$c['id']]);
            $io->execute([sprintf('OP-%04d', $i + 1), $o[0], $c['id'], $ct->fetchColumn() ?: null, $e, $o[3], $o[4], $pr[$e]['probabilidad'], $tipo === 'abierta' ? $cierre : $cierre,
                          $tipo === 'abierta' ? null : $cierre, $vend[$i % count($vend)], $o[6], $tipo, $tipo === 'perdida' ? ($i % 2 ? 'Precio superior a la competencia' : 'Decidió postergar el proyecto') : null,
                          'Oportunidad generada a partir de conversaciones con el cliente.', date('Y-m-d H:i:s', strtotime('-' . (abs($o[5]) + mt_rand(15, 40)) . ' days'))]);
        }

        // Actividades
        $acts = [
          ['llamada', 'Llamar para confirmar interés en propuesta', 'Clínica Santa Elena', -2, 'l1'],
          ['reunion', 'Reunión de presentación de propuesta', '', 0, 'o2'],
          ['correo', 'Enviar cotización actualizada', '', 0, 'o5'],
          ['tarea', 'Preparar demo de monitoreo 24/7', '', 1, 'o7'],
          ['visita', 'Visita técnica para levantamiento de cableado', '', 2, 'o3'],
          ['llamada', 'Seguimiento a renovación de contrato', '', -1, 'o1'],
          ['reunion', 'Negociación final de condiciones', '', 3, 'o5'],
          ['tarea', 'Revisar propuesta económica con gerencia', '', 5, 'o4'],
          ['llamada', 'Primer contacto con lead del evento', '', 0, 'l3'],
          ['correo', 'Enviar brochure de servicios', '', 1, 'l5'],
          ['llamada', 'Calificar necesidades del lead', '', -3, 'l2'],
          ['reunion', 'Reunión de kick-off', '', -10, 'o10'],
        ];
        $ia = $pdo->prepare('INSERT INTO crm_actividades(tipo,asunto,fecha,completada,completada_at,resultado,cliente_id,lead_id,oportunidad_id,responsable_id,creado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($acts as $i => $a) {
            $fecha = date('Y-m-d H:i:00', strtotime(($a[3] >= 0 ? '+' : '') . $a[3] . " days " . (9 + $i % 8) . ':' . ($i % 2 ? '30' : '00')));
            $lead = $op = $cl = null;
            if ($a[4][0] === 'l') $lead = (int)substr($a[4], 1); else { $op = (int)substr($a[4], 1); $cl = $pdo->query("SELECT cliente_id FROM crm_oportunidades WHERE id=$op")->fetchColumn() ?: null; }
            $done = $a[3] < -5 ? 1 : 0;
            $ia->execute([$a[0], $a[1], $fecha, $done, $done ? $fecha : null, $done ? 'Reunión realizada. Cliente interesado, se acordó enviar propuesta.' : null, $cl, $lead, $op, $vend[$i % count($vend)], $vend[0]]);
        }
        // Historial reciente de ejemplo en oportunidades ganadas
        $pdo->exec("INSERT INTO crm_actividades(tipo,asunto,completada,completada_at,auto,cliente_id,oportunidad_id,fecha,creado_por) SELECT 'nota', CONCAT('Oportunidad ganada: ', titulo), 1, created_at, 1, cliente_id, id, NOW() - INTERVAL 10 DAY, responsable_id FROM crm_oportunidades WHERE estado='ganada'");

        // Cotizaciones con ítems
        $cots = [
          [4, 'enviada', [['Laptop corporativa i7 16GB 512GB SSD', 15, 1480], ['Licencia Microsoft 365 Business (anual)', 15, 140], ['Configuración e imagen corporativa', 15, 45]], 'o5', -3],
          [1, 'enviada', [['Migración de servidores a nube híbrida', 1, 24000], ['Licenciamiento cloud (12 meses)', 1, 9600], ['Capacitación al personal TI', 1, 1800]], 'o2', -5],
          [3, 'borrador', [['Plan Business · soporte mensual (12 meses)', 12, 950]], 'o4', 0],
          [2, 'aprobada', [['NAS 4 bahías 16 TB', 1, 7800], ['Instalación y configuración', 1, 1500], ['Backup remoto (12 meses)', 1, 2400]], 'o11', -30],
          [4, 'rechazada', [['Auditoría de red y licencias', 1, 5500]], 'o13', -20],
        ];
        $ic1 = $pdo->prepare('INSERT INTO crm_cotizaciones(codigo,cliente_id,oportunidad_id,contacto_id,fecha,valida_hasta,estado,descuento_pct,igv_pct,subtotal,descuento,igv,total,condiciones,creado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $ii = $pdo->prepare('INSERT INTO crm_cotizacion_items(cotizacion_id,descripcion,cantidad,precio_unit) VALUES(?,?,?,?)');
        $cond = "• Precios en soles (S/). Incluyen IGV.\n• Forma de pago: 50% al aceptar, 50% contra entrega.\n• Plazo de entrega/implementación: según cronograma acordado.\n• Garantía de 12 meses en equipos y 30 días en servicios.";
        foreach ($cots as $i => $c) {
            $op = (int)substr($c[3], 1); $row = $pdo->query("SELECT cliente_id, contacto_id FROM crm_oportunidades WHERE id=$op")->fetch();
            $sub = array_sum(array_map(fn($x) => $x[1] * $x[2], $c[2])); $igv = round($sub * 0.18, 2); $f = date('Y-m-d', strtotime(($c[4] >= 0 ? '+' : '') . $c[4] . ' days'));
            $ic1->execute([sprintf('COT-%04d', $i + 1), $row['cliente_id'], $op, $row['contacto_id'], $f, date('Y-m-d', strtotime("$f +15 days")), $c[1], 0, 18, $sub, 0, $igv, $sub + $igv, $cond, $vend[0]]);
            $cid = (int)$pdo->lastInsertId(); foreach ($c[2] as $it) $ii->execute([$cid, $it[0], $it[1], $it[2]]);
        }
        $log[] = 'Datos de demostración del CRM: 8 leads, 14 oportunidades, 12 actividades, 5 cotizaciones.';
    }
    return $log;
}

<?php
/**
 * Instalador: crea la base de datos sys_gestion_ti, las tablas y datos de demostración.
 * Ejecutar una vez desde:  http://localhost/sys_gestion_ti/install.php
 * (borra o renombra este archivo en producción)
 */
require __DIR__ . '/config/config.php';
require __DIR__ . '/database/v3.php';
require __DIR__ . '/database/v7.php';
require __DIR__ . '/database/v8.php';
require __DIR__ . '/database/v9.php';
require __DIR__ . '/database/v10.php';
mt_srand(2026);
header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . DB_NAME . '`');
    $pdo->exec(file_get_contents(__DIR__ . '/database/schema.sql'));

    // ---------- Roles ----------
    foreach ([['Administrador','Acceso total'],['Supervisor','Gestiona equipo y reportes'],['Técnico','Atiende tickets y visitas'],['Recepción','Registra tickets y clientes']] as $r)
        $pdo->prepare('INSERT INTO roles(nombre,descripcion) VALUES(?,?)')->execute($r);

    // ---------- Usuarios ----------
    $users = [
      [1,'Victor','Ramírez','admin','admin@techdesk.pe','Admin123*','Jefe de Soporte TI','#4f46e5'],
      [2,'Lucía','Fernández','lfernandez','lucia@techdesk.pe','Tecnico123*','Supervisora de Servicio','#0ea5e9'],
      [3,'Carlos','Mendoza','cmendoza','carlos@techdesk.pe','Tecnico123*','Técnico Sr. Redes','#10b981'],
      [3,'Andrea','Quispe','aquispe','andrea@techdesk.pe','Tecnico123*','Técnico de Campo','#f59e0b'],
      [3,'Jorge','Salazar','jsalazar','jorge@techdesk.pe','Tecnico123*','Técnico Servidores','#ef4444'],
      [3,'María','Torres','mtorres','maria@techdesk.pe','Tecnico123*','Técnico Help Desk','#8b5cf6'],
    ];
    $st = $pdo->prepare('INSERT INTO usuarios(rol_id,nombres,apellidos,usuario,email,password,cargo,avatar_color) VALUES(?,?,?,?,?,?,?,?)');
    foreach ($users as $u) { $u[5] = password_hash($u[5], PASSWORD_DEFAULT); $st->execute([$u[0],$u[1],$u[2],$u[3],$u[4],$u[5],$u[6],$u[7]]); }
    $tecnicos = [3,4,5,6];

    // ---------- Categorías ----------
    $cats = [['Hardware','bi-cpu','#6366f1'],['Software','bi-window-stack','#0ea5e9'],['Redes y Conectividad','bi-wifi','#10b981'],
             ['Servidores','bi-hdd-rack','#f59e0b'],['Correo y Cuentas','bi-envelope-at','#ec4899'],['Impresoras','bi-printer','#8b5cf6'],
             ['Seguridad','bi-shield-lock','#ef4444'],['Telefonía IP','bi-telephone','#14b8a6']];
    foreach ($cats as $c) $pdo->prepare('INSERT INTO categorias(nombre,icono,color) VALUES(?,?,?)')->execute($c);

    // ---------- Clientes ----------
    $clientes = [
      ['Clínica San Gabriel','20512345671','Salud','Av. Javier Prado 1420, San Isidro','Dr. Paredes'],
      ['Distribuidora Andina S.A.C.','20498765432','Comercio','Jr. Cusco 355, Cercado de Lima','Ing. Rojas'],
      ['Colegio Santa Rosa','20456781239','Educación','Av. La Marina 2210, San Miguel','Prof. Linares'],
      ['Estudio Contable Valdivia','20123459876','Servicios','Calle Los Pinos 128, Miraflores','CPC Valdivia'],
      ['Constructora Pacífico','20567891230','Construcción','Av. Argentina 4580, Callao','Ing. Huamán'],
      ['Hotel Mirador del Sol','20345678901','Turismo','Malecón Cisneros 810, Miraflores','Sr. Bendezú'],
      ['Transportes Inca Express','20678912345','Transporte','Av. Venezuela 1890, Breña','Sra. Cárdenas'],
      ['Farmacias Vida Plus','20789123456','Retail','Av. Brasil 1250, Jesús María','Q.F. Medina'],
    ];
    foreach ($clientes as $i => $c)
        $pdo->prepare('INSERT INTO clientes(razon_social,ruc,rubro,direccion,contacto,telefono,email) VALUES(?,?,?,?,?,?,?)')
            ->execute([$c[0],$c[1],$c[2],$c[3],$c[4],'01 '.mt_rand(2000000,7999999),'ti@cliente'.($i+1).'.pe']);

    // ---------- Contratos ----------
    $planes = ['Premium'=>[2,24,1800],'Business'=>[4,36,950],'Básico'=>[8,72,420]];
    foreach ($clientes as $i => $c) {
        $p = array_keys($planes)[$i % 3]; $s = $planes[$p];
        $fin = date('Y-m-d', strtotime('+' . ($i < 2 ? 25 + $i * 10 : 90 + $i * 40) . ' days'));
        $estado = $i < 2 ? 'por_vencer' : 'vigente';
        $pdo->prepare('INSERT INTO contratos(cliente_id,codigo,plan,sla_respuesta_horas,sla_resolucion_horas,fecha_inicio,fecha_fin,monto_mensual,estado) VALUES(?,?,?,?,?,?,?,?,?)')
            ->execute([$i+1, sprintf('CT-2026-%03d',$i+1), $p, $s[0], $s[1], date('Y-m-d', strtotime('-300 days')), $fin, $s[2], $estado]);
    }

    // ---------- Activos ----------
    $tipos = [['Laptop','Dell','Latitude 5440'],['Laptop','HP','ProBook 450 G9'],['PC Escritorio','Lenovo','ThinkCentre M70'],['Servidor','Dell','PowerEdge R450'],
              ['Switch','Cisco','Catalyst 2960'],['Router','MikroTik','RB4011'],['Impresora','Epson','L6270'],['Access Point','Ubiquiti','UniFi U6 Pro'],['Firewall','Fortinet','FortiGate 60F']];
    $n = 1;
    foreach ($clientes as $i => $c) for ($k = 0; $k < 6; $k++) {
        $t = $tipos[mt_rand(0, count($tipos)-1)];
        $compra = date('Y-m-d', strtotime('-' . mt_rand(60, 900) . ' days'));
        $pdo->prepare('INSERT INTO activos(cliente_id,codigo,tipo,marca,modelo,serie,ubicacion,fecha_compra,garantia_hasta,estado) VALUES(?,?,?,?,?,?,?,?,?,?)')
            ->execute([$i+1, sprintf('ACT-%04d',$n++), $t[0],$t[1],$t[2], strtoupper(bin2hex(random_bytes(5))), ['Recepción','Oficina TI','Gerencia','Almacén','Sala de servidores'][mt_rand(0,4)],
                       $compra, date('Y-m-d', strtotime($compra . ' +' . (mt_rand(0,1) ? 365 : 730) . ' days')), mt_rand(1,12) === 1 ? 'en_reparacion' : 'operativo']);
    }

    // ---------- Tickets (últimos 6 meses) ----------
    $asuntos = [
      [0,'Laptop no enciende tras corte de energía'],[0,'Pantalla con líneas verticales'],[0,'Reemplazo de disco duro por SSD'],
      [1,'Excel se cierra al abrir archivos grandes'],[1,'Instalación de Office 365'],[1,'Licencia de antivirus vencida'],[1,'Sistema ERP muy lento'],
      [2,'Sin internet en el segundo piso'],[2,'Intermitencia en WiFi de recepción'],[2,'Configurar VPN para trabajo remoto'],
      [3,'Servidor de archivos sin espacio'],[3,'Respaldo nocturno falló'],[3,'Alerta de temperatura en rack'],
      [4,'No puedo recibir correos'],[4,'Alta de nuevo usuario de correo'],[4,'Restablecer contraseña de dominio'],
      [5,'Impresora no imprime en red'],[5,'Atasco constante de papel'],[5,'Cambio de tóner'],
      [6,'Posible correo de phishing recibido'],[6,'Bloqueo de cuenta por intentos fallidos'],[7,'Anexos sin tono en oficina'],[7,'Configurar central IP'],
    ];
    $estados = ['abierto','en_proceso','pendiente','resuelto','cerrado'];
    $prios = ['baja','media','media','alta','alta','critica'];
    $slaH = ['baja'=>72,'media'=>48,'alta'=>24,'critica'=>8];
    $canales = ['web','correo','telefono','whatsapp','presencial'];
    $total = 230;
    $ins = $pdo->prepare('INSERT INTO tickets(codigo,cliente_id,categoria_id,asunto,descripcion,prioridad,estado,canal,tecnico_id,creado_por,fecha_limite,resuelto_at,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
    for ($i = 1; $i <= $total; $i++) {
        // más tickets en meses recientes
        $daysAgo = (int) floor(pow(mt_rand(0, 1000) / 1000, 1.35) * 178);
        $created = strtotime("-{$daysAgo} days") - mt_rand(0, 40000);
        if ($created > time()) $created = time() - 3600;
        $a = $asuntos[mt_rand(0, count($asuntos)-1)];
        $prio = $prios[mt_rand(0, count($prios)-1)];
        $limite = $created + $slaH[$prio] * 3600;
        $old = $daysAgo > 6;
        $estado = $old ? (mt_rand(0,9) < 8 ? 'cerrado' : 'resuelto') : $estados[mt_rand(0,4)];
        if ($old && mt_rand(0,40) === 0) $estado = 'en_proceso';
        $resuelto = null;
        if (in_array($estado, ['resuelto','cerrado'])) {
            $horas = $slaH[$prio] * (mt_rand(0,100) < 88 ? mt_rand(25, 95) / 100 : mt_rand(105, 170) / 100);
            $resuelto = $created + (int)($horas * 3600);
            if ($resuelto > time()) $resuelto = time() - 600;
        }
        $ins->execute([
            sprintf('TCK-%05d', 1000 + $i), mt_rand(1,8), $a[0] + 1, $a[1], 'Incidencia reportada por el cliente. Requiere diagnóstico y atención según SLA.',
            $prio, $estado, $canales[mt_rand(0,4)], $tecnicos[mt_rand(0,3)], 1,
            date('Y-m-d H:i:s', $limite), $resuelto ? date('Y-m-d H:i:s', $resuelto) : null, date('Y-m-d H:i:s', $created),
        ]);
    }
    // Garantizar algunos tickets de hoy
    $desde = $total - 11;
    $pdo->exec("UPDATE tickets SET created_at = NOW() - INTERVAL FLOOR(RAND()*9) HOUR, estado = ELT(1+FLOOR(RAND()*3),'abierto','en_proceso','pendiente'), resuelto_at = NULL WHERE id BETWEEN $desde AND $total");
    $pdo->exec("UPDATE tickets SET fecha_limite = created_at + INTERVAL 24 HOUR WHERE id BETWEEN $desde AND $total");
    $pdo->exec("UPDATE tickets SET estado='resuelto', resuelto_at = NOW() - INTERVAL 20 MINUTE WHERE id IN (" . ($total-1) . "," . $total . ")");

    // ---------- Visitas ----------
    $vt = ['correctiva','preventiva','instalacion','emergencia'];
    for ($i = 0; $i < 9; $i++)
        $pdo->prepare('INSERT INTO visitas(cliente_id,tecnico_id,tipo,fecha_programada,estado,notas) VALUES(?,?,?,?,?,?)')
            ->execute([mt_rand(1,8), $tecnicos[mt_rand(0,3)], $vt[mt_rand(0,3)], date('Y-m-d H:i:s', strtotime('+' . mt_rand(0, 6) . ' days ' . mt_rand(9, 16) . ' hours', strtotime('today'))), 'programada', 'Visita programada de mantenimiento']);

    // ---------- Repuestos ----------
    $rep = [['Disco SSD 480GB','Almacenamiento',3,8,185],['Memoria RAM DDR4 8GB','Memoria',14,6,95],['Fuente de poder 500W','Componentes',2,5,120],
            ['Cable UTP Cat6 (caja 305m)','Redes',6,3,390],['Tóner HP 105A','Consumibles',4,6,260],['Batería laptop Dell','Baterías',9,4,210],
            ['Switch 24p PoE','Redes',2,2,1450],['Pantalla LED 15.6"','Pantallas',1,3,340],['Conector RJ45 (100u)','Redes',20,8,45],['Pasta térmica','Consumibles',12,5,22]];
    foreach ($rep as $i => $r)
        $pdo->prepare('INSERT INTO repuestos(codigo,nombre,categoria,stock,stock_minimo,precio) VALUES(?,?,?,?,?,?)')->execute([sprintf('REP-%03d',$i+1),$r[0],$r[1],$r[2],$r[3],$r[4]]);

    // ---------- Base de conocimiento ----------
    foreach ([['Cómo restablecer contraseña de Active Directory',2],['Configuración de VPN con FortiClient',3],['Solución a impresora offline en red',6],['Checklist de mantenimiento preventivo',1],['Respaldo con Veeam: buenas prácticas',4]] as $k)
        $pdo->prepare('INSERT INTO kb_articulos(titulo,categoria_id,vistas,autor_id,contenido) VALUES(?,?,?,?,?)')->execute([$k[0],$k[1],mt_rand(40,420),1,'Contenido del artículo...']);

    // ---------- Actividad reciente ----------
    $act = [
      [3,'Resolvió el ticket TCK-01228','Servidor de archivos sin espacio','bi-check-circle-fill','#10b981','-12 minutes'],
      [2,'Asignó el ticket TCK-01230','A: Andrea Quispe','bi-person-check-fill','#4f46e5','-35 minutes'],
      [6,'Creó el ticket TCK-01231','Impresora no imprime en red','bi-plus-circle-fill','#0ea5e9','-1 hours'],
      [4,'Inició visita técnica','Clínica San Gabriel','bi-geo-alt-fill','#f59e0b','-2 hours'],
      [1,'Renovó contrato CT-2026-004','Estudio Contable Valdivia','bi-file-earmark-check-fill','#8b5cf6','-3 hours'],
      [5,'Registró ingreso de repuestos','Disco SSD 480GB x 5','bi-box-seam-fill','#14b8a6','-5 hours'],
      [2,'Escaló ticket crítico','Alerta de temperatura en rack','bi-exclamation-triangle-fill','#ef4444','-7 hours'],
    ];
    foreach ($act as $a)
        $pdo->prepare('INSERT INTO actividad(usuario_id,accion,detalle,icono,color,created_at) VALUES(?,?,?,?,?,?)')->execute([$a[0],$a[1],$a[2],$a[3],$a[4],date('Y-m-d H:i:s', strtotime($a[5]))]);

    instalar_v3($pdo);
    instalar_crm($pdo);
    instalar_facturacion($pdo);
    instalar_v9($pdo);
    instalar_v10($pdo);       // Empresa y respaldos
    require_once __DIR__ . '/core/ia.php';
    ia_instalar($pdo, true);   // tablas y vistas del Asistente IA

    $ok = true;
} catch (Throwable $e) { $ok = false; $err = $e->getMessage(); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalador · <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:linear-gradient(135deg,#0b1437,#312e81);min-height:100vh;display:grid;place-items:center;font-family:system-ui}</style></head>
<body><div class="card border-0 shadow-lg rounded-4 p-4" style="max-width:520px;width:92%">
<?php if ($ok): ?>
  <h3 class="fw-bold text-success">✔ Instalación completada</h3>
  <p class="text-muted">Base de datos <b><?= DB_NAME ?></b> creada con datos de demostración.</p>
  <ul class="small"><li>Usuario: <b>admin</b></li><li>Contraseña: <b>Admin123*</b></li></ul>
  <a class="btn btn-primary btn-lg w-100" href="<?= BASE_URL ?>/">Ir al sistema</a>
<?php else: ?>
  <h3 class="fw-bold text-danger">Error en la instalación</h3>
  <pre class="small bg-light p-3 rounded"><?= htmlspecialchars($err) ?></pre>
  <p class="small text-muted mb-0">Verifica que MySQL esté iniciado y los datos en <code>config/config.php</code>.</p>
<?php endif; ?></div></body></html>

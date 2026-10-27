<?php
/**
 * Asistente de reportes + Chat con IA · TechDesk Pro (Soporte TI)
 * La IA NUNCA toca las tablas reales: solo ve vistas de lectura (v_ia_*). Su SQL se valida, se ejecuta
 * en una transacción de solo lectura, con límite de tiempo y de filas.
 */
const IA_MAX_FILAS = 500;
const IA_PRESETS = [
    'gemini'     => ['nombre' => 'Google Gemini (gratis)', 'url' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'modelo' => 'gemini-3.5-flash-lite', 'clave' => true,  'ayuda' => 'Clave gratis en aistudio.google.com/apikey'],
    'groq'       => ['nombre' => 'Groq (gratis)',          'url' => 'https://api.groq.com/openai/v1',                            'modelo' => 'llama-3.3-70b-versatile', 'clave' => true,  'ayuda' => 'Clave gratis en console.groq.com/keys'],
    'openrouter' => ['nombre' => 'OpenRouter (modelos :free)', 'url' => 'https://openrouter.ai/api/v1',                         'modelo' => 'meta-llama/llama-3.3-70b-instruct:free', 'clave' => true, 'ayuda' => 'Clave gratis en openrouter.ai/keys'],
    'ollama'     => ['nombre' => 'Ollama (local, 100 % gratis y privado)', 'url' => 'http://localhost:11434/v1',                'modelo' => 'qwen2.5-coder:7b', 'clave' => false, 'ayuda' => 'Instala Ollama en tu PC y ejecuta: ollama pull qwen2.5-coder:7b'],
    'otro'       => ['nombre' => 'Otro (compatible con OpenAI)', 'url' => '', 'modelo' => '', 'clave' => true, 'ayuda' => 'Cualquier servicio con API /chat/completions (OpenAI, LM Studio, gateways…)'],
];

function ia_db(): PDO { return Database::conn(); }

/* ---------- Vistas de solo lectura (la «capa de inteligencia») ---------- */
function ia_vistas(): array
{
    $v = ia_vistas_base();
    try { $crm = $GLOBALS['IA_CRM'] ?? ia_db()->query("SHOW TABLES LIKE 'crm_cotizaciones'")->fetchColumn(); if ($crm) $v += ia_vistas_crm(); } catch (Throwable $e) {}
    try { $fac = $GLOBALS['IA_FAC'] ?? ia_db()->query("SHOW TABLES LIKE 'fac_pagos'")->fetchColumn(); if ($fac) $v += ia_vistas_fac(); } catch (Throwable $e) {}
    return $v;
}

function ia_vistas_fac(): array
{
    return [
    'v_ia_comprobantes' => ["Comprobantes de venta (facturas, boletas, recibos) emitidos a clientes. estado_pago: pendiente, parcial, vencida, pagada o anulada; «por cobrar» = pendiente, parcial o vencida (usa saldo). concepto: mensualidad, servicio, equipos u otro. Importes en soles; total incluye IGV.",
      "SELECT f.codigo, f.tipo, c.razon_social AS cliente, f.concepto, f.fecha_emision AS fecha, DATE_FORMAT(f.fecha_emision,'%Y-%m') AS mes, f.fecha_vencimiento AS vencimiento, f.subtotal, f.igv, f.total, f.pagado AS monto_pagado, IF(f.estado='anulada',0,f.total-f.pagado) AS saldo, CASE WHEN f.estado='anulada' THEN 'anulada' WHEN f.estado='pagada' THEN 'pagada' WHEN f.fecha_vencimiento < CURDATE() THEN 'vencida' WHEN f.pagado > 0 THEN 'parcial' ELSE 'pendiente' END AS estado_pago, IF(f.estado='emitida' AND f.fecha_vencimiento < CURDATE(), DATEDIFF(CURDATE(), f.fecha_vencimiento), 0) AS dias_atraso FROM fac_comprobantes f JOIN clientes c ON c.id=f.cliente_id"],
    'v_ia_pagos' => ["Pagos (cobros) recibidos de clientes. metodo: transferencia, deposito, yape, plin, efectivo, tarjeta o cheque. Es el dinero efectivamente cobrado.",
      "SELECT p.fecha, DATE_FORMAT(p.fecha,'%Y-%m') AS mes, p.monto, p.metodo, f.codigo AS comprobante, c.razon_social AS cliente FROM fac_pagos p JOIN fac_comprobantes f ON f.id=p.comprobante_id JOIN clientes c ON c.id=f.cliente_id WHERE f.estado<>'anulada'"],
    ];
}

function ia_vistas_crm(): array
{
    return [
    'v_ia_leads' => ["Leads / prospectos comerciales del CRM. estado: nuevo, contactado, calificado, descartado o convertido; leads «abiertos» = nuevo, contactado o calificado. score es un puntaje 0-100 de calidad. origen: web, referido, llamada, evento, redes, correo, otro.",
      "SELECT l.codigo, l.empresa, l.contacto, l.cargo, l.rubro, l.origen, l.estado, l.score, l.created_at AS fecha, DATE_FORMAT(l.created_at,'%Y-%m') AS mes, CONCAT(u.nombres,' ',u.apellidos) AS vendedor, l.motivo_descarte FROM crm_leads l LEFT JOIN usuarios u ON u.id=l.responsable_id"],
    'v_ia_oportunidades' => ["Oportunidades de venta del pipeline CRM. estado: abierta, ganada o perdida; etapa: Prospección, Calificación, Propuesta, Negociación, Ganada, Perdida. valor en soles, probabilidad en %, valor_ponderado = valor*probabilidad/100 (forecast). tipo: contrato, renovacion, proyecto, equipos, servicio.",
      "SELECT o.codigo, o.titulo, c.razon_social AS cliente, e.nombre AS etapa, o.estado, o.tipo, o.valor, o.probabilidad, ROUND(o.valor*o.probabilidad/100,2) AS valor_ponderado, o.fecha_cierre_estimada AS cierre_estimado, o.fecha_cierre_real AS cierre_real, DATE_FORMAT(COALESCE(o.fecha_cierre_real,o.fecha_cierre_estimada),'%Y-%m') AS mes_cierre, o.created_at AS fecha, CONCAT(u.nombres,' ',u.apellidos) AS vendedor, o.motivo_perdida FROM crm_oportunidades o JOIN clientes c ON c.id=o.cliente_id JOIN crm_etapas e ON e.id=o.etapa_id LEFT JOIN usuarios u ON u.id=o.responsable_id"],
    'v_ia_actividades_crm' => ["Actividades comerciales (llamadas, reuniones, correos, tareas, visitas) agendadas o realizadas. realizada=1 si ya se completó; pendientes = realizada=0. vencida=1 si está pendiente y su fecha ya pasó.",
      "SELECT a.tipo, a.asunto, a.fecha, a.completada AS realizada, IF(a.completada=0 AND a.fecha < NOW(),1,0) AS vencida, c.razon_social AS cliente, l.empresa AS lead_nombre, CONCAT(u.nombres,' ',u.apellidos) AS vendedor FROM crm_actividades a LEFT JOIN clientes c ON c.id=a.cliente_id LEFT JOIN crm_leads l ON l.id=a.lead_id LEFT JOIN usuarios u ON u.id=a.responsable_id WHERE a.auto=0"],
    'v_ia_cotizaciones' => ["Cotizaciones emitidas a clientes. estado: borrador, enviada, aprobada, rechazada o vencida. Importes en soles (total incluye IGV).",
      "SELECT q.codigo, c.razon_social AS cliente, q.fecha, DATE_FORMAT(q.fecha,'%Y-%m') AS mes, q.valida_hasta, q.estado, q.subtotal, q.descuento, q.igv, q.total, o.codigo AS oportunidad FROM crm_cotizaciones q JOIN clientes c ON c.id=q.cliente_id LEFT JOIN crm_oportunidades o ON o.id=q.oportunidad_id"],
    ];
}

function ia_vistas_base(): array
{
    return [
    'v_ia_tickets' => ["Una fila por ticket de soporte. estado: abierto, en_proceso, pendiente, resuelto o cerrado; los tickets «activos/abiertos/sin resolver» son los de estado abierto, en_proceso o pendiente (columna activo=1). prioridad: baja, media, alta o critica. canal: web, correo, telefono, whatsapp o presencial. fuera_sla=1 si se venció el plazo (abiertos: ya pasó fecha_limite; resueltos: se resolvió después de fecha_limite). horas_resolucion = horas desde la creación hasta resolverse (solo resueltos).",
      "SELECT t.id AS ticket_id, t.codigo, t.created_at AS fecha, DATE(t.created_at) AS dia, DATE_FORMAT(t.created_at,'%Y-%m') AS mes, c.razon_social AS cliente, cat.nombre AS categoria, t.asunto, t.prioridad, t.estado, (t.estado IN ('abierto','en_proceso','pendiente')) AS activo, t.canal, CONCAT(u.nombres,' ',u.apellidos) AS tecnico, a.codigo AS equipo, t.fecha_limite, t.resuelto_at, ROUND(TIMESTAMPDIFF(MINUTE,t.created_at,t.resuelto_at)/60,1) AS horas_resolucion, CASE WHEN t.fecha_limite IS NULL THEN NULL WHEN t.estado IN ('resuelto','cerrado') THEN (COALESCE(t.resuelto_at,t.fecha_limite) > t.fecha_limite) ELSE (NOW() > t.fecha_limite) END AS fuera_sla FROM tickets t JOIN clientes c ON c.id=t.cliente_id JOIN categorias cat ON cat.id=t.categoria_id LEFT JOIN usuarios u ON u.id=t.tecnico_id LEFT JOIN activos a ON a.id=t.activo_id"],
    'v_ia_clientes' => ["Clientes (empresas atendidas) con su resumen de tickets, equipos y contrato vigente. activo=1 son clientes activos. monto_mensual = mensualidad del contrato vigente (0 si no tiene).",
      "SELECT c.id AS cliente_id, c.razon_social AS cliente, c.ruc, c.rubro, c.contacto, c.telefono, c.email, c.estado AS activo, c.created_at AS registrado, (SELECT COUNT(*) FROM tickets t WHERE t.cliente_id=c.id) AS num_tickets, (SELECT COUNT(*) FROM tickets t WHERE t.cliente_id=c.id AND t.estado IN ('abierto','en_proceso','pendiente')) AS tickets_activos, (SELECT COUNT(*) FROM activos a WHERE a.cliente_id=c.id) AS num_equipos, (SELECT k.plan FROM contratos k WHERE k.cliente_id=c.id AND k.estado<>'vencido' ORDER BY k.fecha_fin DESC LIMIT 1) AS plan, (SELECT COALESCE(SUM(k.monto_mensual),0) FROM contratos k WHERE k.cliente_id=c.id AND k.estado<>'vencido') AS monto_mensual FROM clientes c"],
    'v_ia_contratos' => ["Contratos de soporte y SLA. plan: Básico, Business o Premium. estado: vigente, por_vencer o vencido. monto_mensual = ingreso mensual recurrente. dias_para_vencer negativo = ya venció.",
      "SELECT k.id AS contrato_id, k.codigo, c.razon_social AS cliente, k.plan, k.sla_respuesta_horas, k.sla_resolucion_horas, k.fecha_inicio, k.fecha_fin, DATEDIFF(k.fecha_fin, CURDATE()) AS dias_para_vencer, k.monto_mensual, k.estado FROM contratos k JOIN clientes c ON c.id=k.cliente_id"],
    'v_ia_activos' => ["Inventario de equipos de los clientes. estado: operativo, en_reparacion o baja. garantia_vigente=1 si la garantía aún no vence.",
      "SELECT a.id AS equipo_id, a.codigo, c.razon_social AS cliente, a.tipo, a.marca, a.modelo, a.serie, a.ubicacion, a.fecha_compra, a.garantia_hasta, (a.garantia_hasta IS NOT NULL AND a.garantia_hasta >= CURDATE()) AS garantia_vigente, a.estado, (SELECT COUNT(*) FROM tickets t WHERE t.activo_id=a.id) AS num_tickets FROM activos a JOIN clientes c ON c.id=a.cliente_id"],
    'v_ia_tecnicos' => ["Personal de soporte (técnicos, supervisores, administradores) con su carga de trabajo y desempeño. tickets_activos = asignados sin resolver. horas_promedio_resolucion = promedio de horas en resolver tickets.",
      "SELECT u.id AS usuario_id, CONCAT(u.nombres,' ',u.apellidos) AS tecnico, r.nombre AS rol, u.cargo, u.estado AS activo, u.ultimo_acceso, (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id) AS tickets_asignados, (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id AND t.estado IN ('abierto','en_proceso','pendiente')) AS tickets_activos, (SELECT COUNT(*) FROM tickets t WHERE t.tecnico_id=u.id AND t.estado IN ('resuelto','cerrado')) AS tickets_resueltos, (SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE,t.created_at,t.resuelto_at))/60,1) FROM tickets t WHERE t.tecnico_id=u.id AND t.resuelto_at IS NOT NULL) AS horas_promedio_resolucion, (SELECT COUNT(*) FROM ordenes_servicio o WHERE o.tecnico_id=u.id AND o.estado='completada') AS ordenes_completadas FROM usuarios u JOIN roles r ON r.id=u.rol_id"],
    'v_ia_ordenes' => ["Órdenes de servicio (trabajos técnicos). tipo: correctiva, preventiva, instalacion o diagnostico. estado: pendiente, en_curso, completada o cancelada. mano_obra = horas × tarifa_hora; total_repuestos = costo de repuestos usados; total = mano_obra + total_repuestos. Los ingresos por órdenes son las completadas.",
      "SELECT o.id AS orden_id, o.codigo, c.razon_social AS cliente, CONCAT(u.nombres,' ',u.apellidos) AS tecnico, t.codigo AS ticket, o.tipo, o.estado, o.horas, o.tarifa_hora, ROUND(o.horas*o.tarifa_hora,2) AS mano_obra, COALESCE(rp.total,0) AS total_repuestos, ROUND(o.horas*o.tarifa_hora + COALESCE(rp.total,0),2) AS total, o.conformidad, o.fecha_programada, o.fecha_fin, o.created_at AS fecha FROM ordenes_servicio o JOIN clientes c ON c.id=o.cliente_id LEFT JOIN usuarios u ON u.id=o.tecnico_id LEFT JOIN tickets t ON t.id=o.ticket_id LEFT JOIN (SELECT orden_id, SUM(cantidad*precio_unit) AS total FROM ordenes_repuestos GROUP BY orden_id) rp ON rp.orden_id=o.id"],
    'v_ia_visitas' => ["Visitas técnicas agendadas. tipo: correctiva, preventiva, instalacion o emergencia. estado: programada, en_curso, completada o cancelada.",
      "SELECT v.id AS visita_id, v.fecha_programada, DATE(v.fecha_programada) AS dia, c.razon_social AS cliente, CONCAT(u.nombres,' ',u.apellidos) AS tecnico, t.codigo AS ticket, v.tipo, v.estado, v.notas FROM visitas v JOIN clientes c ON c.id=v.cliente_id LEFT JOIN usuarios u ON u.id=v.tecnico_id LEFT JOIN tickets t ON t.id=v.ticket_id"],
    'v_ia_repuestos' => ["Inventario de repuestos y consumibles. stock_bajo=1 si stock <= stock_minimo. valor_stock = stock × precio.",
      "SELECT r.id AS repuesto_id, r.codigo, r.nombre AS repuesto, r.categoria, r.stock, r.stock_minimo, (r.stock <= r.stock_minimo) AS stock_bajo, r.precio, ROUND(r.stock*r.precio,2) AS valor_stock, r.ubicacion FROM repuestos r"],
    'v_ia_uso_repuestos' => ["Repuestos consumidos en órdenes de servicio (una fila por repuesto usado en una orden).",
      "SELECT o.codigo AS orden, o.created_at AS fecha, DATE(o.created_at) AS dia, c.razon_social AS cliente, r.nombre AS repuesto, r.categoria, orp.cantidad, orp.precio_unit, ROUND(orp.cantidad*orp.precio_unit,2) AS importe, o.estado AS estado_orden FROM ordenes_repuestos orp JOIN ordenes_servicio o ON o.id=orp.orden_id JOIN repuestos r ON r.id=orp.repuesto_id JOIN clientes c ON c.id=o.cliente_id"],
    'v_ia_conocimiento' => ["Artículos de la base de conocimiento (soluciones documentadas) y cuántas veces se consultaron (vistas_lectura).",
      "SELECT k.id AS articulo_id, k.titulo, cat.nombre AS categoria, k.vistas AS vistas_lectura, CONCAT(u.nombres,' ',u.apellidos) AS autor, k.created_at AS fecha FROM kb_articulos k LEFT JOIN categorias cat ON cat.id=k.categoria_id LEFT JOIN usuarios u ON u.id=k.autor_id"],
    ];
}

/** Crea tablas y vistas (idempotente). Si $force es false y todo existe, no hace nada. */
function ia_instalar(?PDO $pdo = null, bool $force = false): void
{
    $pdo = $pdo ?: ia_db();
    try { $GLOBALS['IA_FAC'] = (bool)$pdo->query("SHOW TABLES LIKE 'fac_pagos'")->fetchColumn(); } catch (Throwable $e) { $GLOBALS['IA_FAC'] = false; }
    try { $GLOBALS['IA_CRM'] = (bool)$pdo->query("SHOW TABLES LIKE 'crm_cotizaciones'")->fetchColumn(); } catch (Throwable $e) { $GLOBALS['IA_CRM'] = false; }
    $pdo->exec("CREATE TABLE IF NOT EXISTS ia_consultas (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, pregunta VARCHAR(500) NOT NULL, titulo VARCHAR(160) NULL, sql_texto TEXT NOT NULL, grafico VARCHAR(255) NULL, favorito TINYINT(1) NOT NULL DEFAULT 0, veces INT NOT NULL DEFAULT 1, creado_en DATETIME DEFAULT CURRENT_TIMESTAMP, ultimo_uso DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX (usuario_id, favorito)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ok = !$force;
    if ($ok) { try { foreach (array_keys(ia_vistas()) as $v) $pdo->query("SELECT 1 FROM `$v` LIMIT 1")->fetchAll(); } catch (Throwable $e) { $ok = false; } }
    if ($ok) return;
    foreach (ia_vistas() as $v => [, $sql]) $pdo->exec("CREATE OR REPLACE VIEW `$v` AS $sql");
}

function ia_esquema_texto(): string
{
    $t = '';
    foreach (ia_vistas() as $v => [$desc]) {
        $cols = [];
        foreach (ia_db()->query("SHOW COLUMNS FROM `$v`")->fetchAll() as $c) $cols[] = $c['Field'] . ' ' . strtolower(preg_replace('/\(.*/', '', $c['Type']));
        $t .= "$v: $desc\n  columnas: " . implode(', ', $cols) . "\n";
    }
    return $t;
}

/* ---------- Configuración y clave cifrada ---------- */
function ia_conf_get(string $k, string $d = ''): string
{
    $s = ia_db()->prepare('SELECT valor FROM configuracion WHERE clave=?'); $s->execute([$k]); $v = $s->fetchColumn();
    return ($v === false || $v === null || $v === '') ? $d : (string)$v;
}
function ia_conf_set(string $k, string $v): void
{
    ia_db()->prepare('INSERT INTO configuracion(clave,valor) VALUES(?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)')->execute([$k, $v]);
}
function ia_secret(): string
{
    $f = ROOT_PATH . '/config/secret.key';
    if (!is_file($f)) @file_put_contents($f, base64_encode(random_bytes(32)));
    $k = base64_decode((string)@file_get_contents($f));
    return $k !== '' && $k !== false ? $k : hash('sha256', DB_NAME . DB_PASS . ROOT_PATH, true);
}
function ia_cifrar(string $s): string
{
    if ($s === '') return '';
    if (!function_exists('openssl_encrypt')) return 'p:' . base64_encode($s);
    $iv = random_bytes(12); $tag = '';
    $c = openssl_encrypt($s, 'aes-256-gcm', ia_secret(), OPENSSL_RAW_DATA, $iv, $tag);
    return 'g:' . base64_encode($iv . $tag . $c);
}
function ia_descifrar(string $s): string
{
    if ($s === '') return '';
    if (str_starts_with($s, 'p:')) return (string)base64_decode(substr($s, 2));
    if (!str_starts_with($s, 'g:') || !function_exists('openssl_decrypt')) return '';
    $b = base64_decode(substr($s, 2)); if (strlen($b) < 29) return '';
    return (string)openssl_decrypt(substr($b, 28), 'aes-256-gcm', ia_secret(), OPENSSL_RAW_DATA, substr($b, 0, 12), substr($b, 12, 16));
}
function ia_cfg(): array
{
    $p = ia_conf_get('ia_proveedor', 'gemini'); if (!isset(IA_PRESETS[$p])) $p = 'gemini';
    return ['proveedor' => $p, 'url' => ia_conf_get('ia_url', IA_PRESETS[$p]['url']), 'modelo' => ia_conf_get('ia_modelo', IA_PRESETS[$p]['modelo']),
            'clave' => ia_descifrar(ia_conf_get('ia_clave', '')), 'resumen' => ia_conf_get('ia_resumen', '1') === '1'];
}
function ia_listo(array $c): bool { return $c['url'] !== '' && $c['modelo'] !== '' && (!IA_PRESETS[$c['proveedor']]['clave'] || $c['clave'] !== ''); }

/* ---------- Llamada al modelo (API compatible con OpenAI) ---------- */
function ia_llamar(array $mensajes, int $maxTokens = 900): string
{
    $c = ia_cfg();
    if ($c['url'] === '' || $c['modelo'] === '') throw new RuntimeException('Configura el proveedor de IA (URL y modelo).');
    if (IA_PRESETS[$c['proveedor']]['clave'] && $c['clave'] === '') throw new RuntimeException('Falta la clave (API key) del proveedor de IA. Configúrala con el botón «Configurar IA».');
    $url = rtrim($c['url'], '/') . '/chat/completions';
    if (!preg_match('#^https?://#i', $url)) throw new RuntimeException('La URL del proveedor debe empezar por http:// o https://');
    $payload = json_encode(['model' => $c['modelo'], 'messages' => $mensajes, 'temperature' => 0, 'max_tokens' => $maxTokens], JSON_UNESCAPED_UNICODE);
    $h = ['Content-Type: application/json', 'Accept: application/json']; if ($c['clave'] !== '') $h[] = 'Authorization: Bearer ' . $c['clave'];
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS]);
        $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $h), 'content' => $payload, 'timeout' => 60, 'ignore_errors' => true]]);
        $res = @file_get_contents($url, false, $ctx); $err = $res === false ? 'sin respuesta' : ''; $code = 0;
        foreach ($http_response_header ?? [] as $l) if (preg_match('#^HTTP/\S+\s+(\d{3})#', $l, $m)) $code = (int)$m[1];
    }
    if ($res === false || $res === null) throw new RuntimeException('No se pudo conectar con el proveedor de IA (' . $err . '). Revisa tu internet y la URL.');
    $j = json_decode((string)$res, true);
    if ($code === 401 || $code === 403) throw new RuntimeException('El proveedor rechazó la clave (API key). Revisa que sea correcta.');
    if ($code === 429) throw new RuntimeException('Se alcanzó el límite del plan gratuito. Espera un minuto e inténtalo de nuevo.');
    if ($code === 404) throw new RuntimeException('Modelo o URL no encontrados (' . $c['modelo'] . '). Google renueva sus modelos: prueba con gemini-3.5-flash-lite o gemini-3.5-flash.');
    if ($code >= 400 || !is_array($j)) { $m = is_array($j) ? ($j['error']['message'] ?? ($j[0]['error']['message'] ?? '')) : ''; throw new RuntimeException('Error del proveedor de IA (' . $code . ')' . ($m ? ': ' . mb_substr($m, 0, 200) : '.')); }
    $txt = $j['choices'][0]['message']['content'] ?? '';
    if (is_array($txt)) $txt = implode('', array_map(fn($p) => $p['text'] ?? '', $txt));
    if (trim((string)$txt) === '') throw new RuntimeException('La IA no devolvió respuesta. Intenta reformular la pregunta.');
    return (string)$txt;
}

function ia_extraer_json(string $t): ?array
{
    $t = trim($t);
    if (preg_match('/```(?:json)?\s*(.*?)```/is', $t, $m)) $t = trim($m[1]);
    $j = json_decode($t, true); if (is_array($j)) return $j;
    $a = strpos($t, '{'); $b = strrpos($t, '}');
    if ($a !== false && $b > $a) { $j = json_decode(substr($t, $a, $b - $a + 1), true); if (is_array($j)) return $j; }
    return null;
}

/* ---------- Validación del SQL (defensa en profundidad) ---------- */
function ia_validar_sql(string $sql): string
{
    $sql = trim($sql); $sql = rtrim($sql, "; \t\r\n");
    if ($sql === '' || strlen($sql) > 4000) throw new InvalidArgumentException('La consulta está vacía o es demasiado larga.');
    // Quita literales de texto para analizar solo la estructura
    $s = preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'/s", ' 0 ', $sql);
    $s = preg_replace('/"(?:[^"\\\\]|\\\\.|"")*"/s', ' 0 ', $s);
    if (strpbrk($s, "'\"") !== false) throw new InvalidArgumentException('Comillas sin cerrar en la consulta.');
    if (strpos($s, ';') !== false) throw new InvalidArgumentException('Solo se permite una consulta.');
    if (preg_match('/--|\/\*|\*\/|#|@/', $s)) throw new InvalidArgumentException('No se permiten comentarios ni variables.');
    $l = strtolower(str_replace('`', ' ', $s));
    if (!preg_match('/^\s*(select|with)\b/', $l)) throw new InvalidArgumentException('Solo se permiten consultas SELECT.');
    $prohib = 'insert|update|delete|drop|alter|create|truncate|grant|revoke|call|execute|prepare|handler|load|outfile|dumpfile|into|lock|unlock|set|rename|use|show|describe|explain|optimize|analyze|sleep|benchmark|load_file|get_lock|release_lock|information_schema|performance_schema|mysql|sys|user|current_user|session_user|system_user|database|schema|version|connection_id|row_count|found_rows|last_insert_id|uuid|procedure|kill|shutdown|flush|reset|purge|install|uninstall|import|binlog|xa|savepoint|commit|rollback|begin|start|recursive';
    if (preg_match('/\b(' . $prohib . ')\b/', $l, $m)) throw new InvalidArgumentException('Palabra no permitida en la consulta: ' . strtoupper($m[1]));
    if (preg_match('/for\s+update|lock\s+in\s+share/', $l)) throw new InvalidArgumentException('Bloqueos no permitidos.');
    // Ninguna tabla real: solo vistas v_ia_*
    static $reales = null;
    if ($reales === null) { $reales = []; foreach (ia_db()->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $r) if (strpos($r[0], 'v_ia_') !== 0) $reales[strtolower($r[0])] = true; $reales[strtolower(DB_NAME)] = true; }
    if (preg_match_all('/(?<![\w.])(?:(as)\s+)?([a-z_][a-z0-9_]*)\b/', $l, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $t) if (isset($reales[$t[2]]) && $t[1] === '') throw new InvalidArgumentException('Solo puedes consultar las vistas v_ia_* (no la tabla «' . $t[2] . '»). Si es un alias, usa otro nombre.');
    }
    if (preg_match('/\b[a-z_][a-z0-9_]*\s*\.\s*[a-z_][a-z0-9_]*\s*\./', $l)) throw new InvalidArgumentException('No se permiten nombres con base de datos.');
    return $sql;
}

/** Ejecuta en solo lectura con límite de tiempo y de filas. Devuelve [columnas, filas, truncado, ms] */
function ia_ejecutar(string $sql): array
{
    $sql = ia_validar_sql($sql); $pdo = ia_db(); $t0 = microtime(true);
    foreach (["SET SESSION MAX_EXECUTION_TIME=8000", "SET SESSION max_statement_time=8"] as $x) { try { $pdo->exec($x); } catch (Throwable $e) {} }
    $buf = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
    try {
        $pdo->exec('START TRANSACTION READ ONLY');
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $st = $pdo->query($sql);
        $n = $st->columnCount(); $cols = $tipos = [];
        for ($i = 0; $i < $n; $i++) { $m = $st->getColumnMeta($i); $cols[] = $m['name'] ?? ('col' . $i); $tipos[] = strtoupper((string)($m['native_type'] ?? '')); }
        $filas = []; $trunc = false;
        while ($r = $st->fetch(PDO::FETCH_NUM)) {
            if (count($filas) >= IA_MAX_FILAS) { $trunc = true; break; }
            foreach ($r as $i => $v) if ($v !== null && is_string($v) && in_array($tipos[$i], ['NEWDECIMAL', 'DECIMAL', 'LONG', 'LONGLONG', 'SHORT', 'TINY', 'INT24', 'FLOAT', 'DOUBLE'], true) && is_numeric($v)) $r[$i] = $v + 0;
            $filas[] = $r;
        }
        $st->closeCursor();
    } catch (PDOException $e) {
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buf); try { $pdo->exec('ROLLBACK'); } catch (Throwable $x) {}
        throw new RuntimeException('Error SQL: ' . preg_replace('/^SQLSTATE\[[^\]]*\]:?\s*/', '', $e->getMessage()));
    }
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buf); try { $pdo->exec('ROLLBACK'); } catch (Throwable $x) {}
    return ['columnas' => $cols, 'filas' => $filas, 'truncado' => $trunc, 'ms' => (int)round((microtime(true) - $t0) * 1000), 'sql' => $sql];
}

/** Reglas de negocio compartidas por el Asistente de reportes y el Chat */
function ia_reglas(): string
{
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    return "REGLAS:\n- Genera un único SELECT (o WITH ... SELECT) usando SOLO las vistas v_ia_*. Sin punto y coma, sin comentarios, sin variables.\n"
    . "- Hoy es " . $dias[(int)date('w')] . " " . date('Y-m-d') . " (usa CURDATE()/NOW() para fechas relativas). La semana empieza el lunes. «Este mes» = desde el día 1 del mes actual hasta hoy; «mes pasado» = mes calendario anterior.\n"
    . "- Tickets «abiertos/pendientes de atender/sin resolver» = activo=1 (estado abierto, en_proceso o pendiente). Tickets «resueltos» = estado IN ('resuelto','cerrado'). «Fuera de SLA / vencidos» = fuera_sla=1. «Cumplimiento de SLA» = porcentaje de tickets con fuera_sla=0 sobre los que tienen fecha_limite.\n"
    . "- Prioridades: baja, media, alta, critica (minúsculas, sin tilde). Estados y tipos van en minúsculas tal como se describen en cada vista.\n"
    . "- Ingresos: mensualidades = monto_mensual de contratos vigentes (estado<>'vencido'); servicios = total de v_ia_ordenes con estado='completada'.\n"
    . "- Para buscar por nombre usa LIKE '%texto%' sobre LOWER(columna), con el texto en minúsculas y sin tildes si dudas.\n"
    . "- CRM: oportunidades «abiertas/en pipeline» = estado='abierta'; «ganadas» estado='ganada'; forecast = SUM(valor_ponderado) de abiertas; tasa de conversión = ganadas/(ganadas+perdidas). Leads «abiertos» = estado IN ('nuevo','contactado','calificado'). Usa v_ia_oportunidades, v_ia_leads, v_ia_actividades_crm y v_ia_cotizaciones para temas comerciales.\n"
    . "- Facturación: «ingresos cobrados» = SUM(monto) de v_ia_pagos; «facturado» = SUM(total) de v_ia_comprobantes con estado_pago<>'anulada'; «por cobrar» = SUM(saldo) con estado_pago IN ('pendiente','parcial','vencida'); «morosos/deuda vencida» = estado_pago='vencida'.\n"
    . "- Moneda: " . (cfg('moneda_simbolo', 'S/')) . " (" . cfg('moneda_codigo', 'PEN') . "). Redondea importes con ROUND(x,2). Pon alias de columnas legibles en español (sin espacios: usa guion bajo), por ejemplo cliente, total_tickets, horas_promedio. Los alias de conteos deben empezar por cantidad_, num_ o total_<cosa> (total_tickets); el alias «total» o «importe», «ingreso_…» o «monto_…» se reservan para importes en soles.\n"
    . "- Si piden un ranking sin cantidad, usa LIMIT 10; en cualquier caso nunca más de " . IA_MAX_FILAS . " filas. Ordena de forma útil.\n"
    . "- No uses como alias el nombre de una tabla real (clientes, tickets, activos, contratos, repuestos, usuarios, visitas, categorias…). Usa otros alias.\n"
    . "- Si la pregunta no se puede responder con estas vistas, no inventes.";
}

/* ---------- Pregunta en lenguaje natural → SQL ---------- */
function ia_prompt_sistema(): string
{
    return "Eres el analista de datos de una empresa de soporte técnico de TI (mesa de ayuda, tickets, SLA, contratos, órdenes de servicio, visitas, inventario de equipos y repuestos). Conviertes preguntas en español en UNA consulta SQL (MySQL/MariaDB) de solo lectura.\n\n"
    . "VISTAS DISPONIBLES (no existe nada más):\n" . ia_esquema_texto() . "\n"
    . ia_reglas() . " Si no se puede responder, devuelve sql null y explica en «mensaje».\n\n"
    . "Responde SOLO con un objeto JSON, sin texto adicional ni markdown:\n"
    . '{"titulo":"título corto del reporte","sql":"SELECT ...","grafico":{"tipo":"bar|line|pie|none","x":"alias_columna_etiquetas","y":"alias_columna_numerica"},"mensaje":null}' . "\n"
    . "grafico.tipo: line para series por fecha, bar para rankings/comparaciones, pie para participación (máx. 8 categorías), none si no tiene sentido graficar.";
}

function ia_preguntar(string $pregunta, ?array $previo = null): array
{
    $m = [['role' => 'system', 'content' => ia_prompt_sistema()]];
    if ($previo && !empty($previo['pregunta']) && !empty($previo['sql'])) {
        $m[] = ['role' => 'user', 'content' => mb_substr((string)$previo['pregunta'], 0, 300)];
        $m[] = ['role' => 'assistant', 'content' => json_encode(['titulo' => '', 'sql' => mb_substr((string)$previo['sql'], 0, 1500), 'grafico' => ['tipo' => 'none'], 'mensaje' => null], JSON_UNESCAPED_UNICODE)];
    }
    $m[] = ['role' => 'user', 'content' => $pregunta];
    $ultimoError = '';
    for ($intento = 0; $intento < 2; $intento++) {
        $raw = ia_llamar($m);
        $j = ia_extraer_json($raw);
        if (!$j) { $m[] = ['role' => 'assistant', 'content' => mb_substr($raw, 0, 800)]; $m[] = ['role' => 'user', 'content' => 'Tu respuesta no era JSON válido. Responde SOLO el objeto JSON indicado.']; $ultimoError = 'La IA respondió en un formato inesperado.'; continue; }
        if (empty($j['sql'])) return ['mensaje' => (string)(($j['mensaje'] ?? '') ?: 'No puedo responder esa pregunta con los datos disponibles. Prueba con tickets, SLA, clientes, contratos, órdenes, visitas, equipos o repuestos.')];
        try {
            $r = ia_ejecutar((string)$j['sql']);
            $g = is_array($j['grafico'] ?? null) ? $j['grafico'] : [];
            return $r + ['titulo' => mb_substr(trim((string)($j['titulo'] ?? '')) ?: $pregunta, 0, 160), 'grafico' => ['tipo' => in_array($g['tipo'] ?? '', ['bar', 'line', 'pie', 'none'], true) ? $g['tipo'] : 'none', 'x' => (string)($g['x'] ?? ''), 'y' => (string)($g['y'] ?? '')]];
        } catch (Throwable $e) {
            $ultimoError = $e->getMessage();
            $m[] = ['role' => 'assistant', 'content' => $raw];
            $m[] = ['role' => 'user', 'content' => 'Esa consulta falló: ' . $ultimoError . '. Corrígela usando solo las vistas y columnas listadas y responde de nuevo SOLO el JSON.'];
        }
    }
    throw new RuntimeException('No pude generar una consulta válida para esa pregunta. Reformúlala con más detalle. (' . $ultimoError . ')');
}

function ia_resumir(string $pregunta, array $cols, array $filas): string
{
    $muestra = array_slice($filas, 0, 25);
    $txt = ia_llamar([
        ['role' => 'system', 'content' => 'Eres un analista de una empresa de soporte TI. Responde en español, en 1 o 2 frases claras, mencionando las cifras más relevantes. No uses listas, ni markdown, ni inventes datos que no estén en la tabla. Moneda: S/ (soles).'],
        ['role' => 'user', 'content' => "Pregunta: $pregunta\nColumnas: " . implode(', ', $cols) . "\nFilas (" . count($filas) . " en total, se muestran " . count($muestra) . "):\n" . json_encode($muestra, JSON_UNESCAPED_UNICODE)],
    ], 250);
    return trim(strip_tags($txt));
}

/* ---------- Chat conversacional ---------- */
const IA_GUIA = "Dashboard: indicadores, gráficos y carga del equipo. Tickets: registrar incidencias (cliente, categoría, prioridad, canal), asignar técnico, comentar (internos o públicos), cambiar estado y ver el SLA; el plazo se calcula con el contrato del cliente y la prioridad. Órdenes de Servicio: crear desde un ticket o activo, registrar diagnóstico, solución, horas y repuestos usados, y marcar conformidad. Visitas Técnicas: calendario por técnico, se valida que no se crucen en el mismo horario. Base de Conocimiento: artículos de soluciones por categoría. Clientes: ficha con contactos, tickets, equipos y contrato. Contratos y SLA: planes Básico/Business/Premium y tiempos de respuesta y resolución. Inventario de Activos: equipos por cliente con garantía. Repuestos y Almacén: stock, stock mínimo y ajustes de ingreso/salida. Equipo Técnico: carga y productividad. Reportes: tickets, SLA, técnicos, clientes, ingresos e inventario (exportables). Usuarios y Roles, Auditoría y Configuración (empresa, SLA, categorías, seguridad, respaldo) son solo para administración. Cotizaciones y Facturación aún no están disponibles. CRM Comercial (roles Administrador, Supervisor y Comercial): Leads (registrar prospectos, el score se calcula solo, convertir en cliente), Pipeline (tablero Kanban: arrastra oportunidades entre etapas; al marcar Ganada se puede crear el contrato automáticamente), Actividades (agenda de llamadas y seguimientos), Cotizaciones (con IGV, versiones y vista imprimible) y Panel comercial (embudo, forecast, renovaciones y clientes en riesgo). La ficha del cliente tiene pestañas de contactos, oportunidades y línea de tiempo. Facturación (Administrador y Supervisor): comprobantes (factura, boleta, recibo) con IGV, registro de pagos totales o parciales, anulación con motivo, mensualidades recurrentes de contratos (emitir todo el mes de una vez), cuentas por cobrar con antigüedad de deuda, reporte de ingresos y botón Facturar en órdenes completadas y cotizaciones aprobadas.";

function ia_prompt_chat(): string
{
    return "Eres el chatbot de «" . cfg('empresa_nombre', APP_NAME) . "», un sistema de gestión de soporte técnico de TI. Conversas en español con el administrador, con tono cercano y breve.\n"
    . "Puedes: (1) consultar datos del negocio (tickets, SLA, clientes, contratos, equipos, técnicos, órdenes de servicio, visitas, repuestos, base de conocimiento); (2) explicar cómo usar el sistema; (3) saludar o aclarar. NO puedes registrar, modificar ni borrar nada: si te lo piden, explica en qué módulo se hace.\n\n"
    . "VISTAS DISPONIBLES (no existe nada más):\n" . ia_esquema_texto() . "\n" . ia_reglas() . "\n"
    . "- Para datos de un cliente, ticket o equipo concretos devuelve pocas columnas útiles y LIMIT 20.\n"
    . "- Si el mensaje es una continuación («y de la semana pasada?», «¿y de Juan?»), reutiliza y adapta la consulta anterior.\n\n"
    . "GUÍA DEL SISTEMA: " . IA_GUIA . "\n\n"
    . "Responde SOLO con un objeto JSON, sin texto adicional ni markdown, en una de dos formas:\n"
    . '{"accion":"consulta","sql":"SELECT ..."}  → cuando necesites datos.' . "\n"
    . '{"accion":"respuesta","texto":"..."}  → cuando no necesites datos (saludo, ayuda de uso, aclaración, o si algo no es posible).';
}

/** Un turno de chat. $hist = [['q'=>..., 'sql'=>?, 'a'=>...], ...]. Devuelve ['texto'=>..., 'tabla'=>?, 'sql'=>?] */
function ia_chat(string $msg, array $hist): array
{
    $m = [['role' => 'system', 'content' => ia_prompt_chat()]];
    foreach (array_slice($hist, -6) as $h) {
        $m[] = ['role' => 'user', 'content' => (string)$h['q']];
        $m[] = ['role' => 'assistant', 'content' => json_encode(!empty($h['sql']) ? ['accion' => 'consulta', 'sql' => $h['sql']] : ['accion' => 'respuesta', 'texto' => mb_substr((string)$h['a'], 0, 300)], JSON_UNESCAPED_UNICODE)];
    }
    $m[] = ['role' => 'user', 'content' => $msg];
    $err = '';
    for ($i = 0; $i < 2; $i++) {
        $raw = ia_llamar($m, 700); $j = ia_extraer_json($raw);
        if (!$j) { $t = trim(strip_tags($raw)); if ($t !== '' && $t[0] !== '{') return ['texto' => $t]; $m[] = ['role' => 'assistant', 'content' => mb_substr($raw, 0, 500)]; $m[] = ['role' => 'user', 'content' => 'Responde SOLO con el objeto JSON indicado.']; continue; }
        if (($j['accion'] ?? '') !== 'consulta' || empty($j['sql'])) return ['texto' => trim((string)($j['texto'] ?? $j['respuesta'] ?? '')) ?: 'No entendí bien. ¿Puedes darme más detalle?'];
        try { $r = ia_ejecutar((string)$j['sql']); }
        catch (Throwable $e) { $err = $e->getMessage(); $m[] = ['role' => 'assistant', 'content' => $raw]; $m[] = ['role' => 'user', 'content' => 'Esa consulta falló: ' . $err . '. Corrígela usando solo las vistas y columnas listadas y responde SOLO el JSON.']; continue; }
        $muestra = array_slice($r['filas'], 0, 25);
        $texto = ia_llamar([
            ['role' => 'system', 'content' => 'Eres el chatbot de una empresa de soporte TI. Responde en español con tono cercano y breve (máximo 4 frases o una lista corta con guiones). Usa las cifras EXACTAS de los datos, sin inventar nada. Importes con el símbolo S/ (ejemplo de formato: ' . money(1234.5) . '). Si no hay filas, dilo claramente. No menciones SQL, vistas ni columnas técnicas.'],
            ['role' => 'user', 'content' => "Pregunta: $msg\nColumnas: " . implode(', ', $r['columnas']) . "\nFilas (" . count($r['filas']) . " en total" . ($r['truncado'] ? ', hay más' : '') . ", se muestran " . count($muestra) . "):\n" . json_encode($muestra, JSON_UNESCAPED_UNICODE)],
        ], 450);
        return ['texto' => trim(strip_tags($texto)), 'sql' => $r['sql'], 'tabla' => ['columnas' => $r['columnas'], 'filas' => array_slice($r['filas'], 0, 10), 'total' => count($r['filas'])]];
    }
    throw new RuntimeException('No pude consultar eso con los datos disponibles. Prueba reformulando la pregunta. (' . $err . ')');
}

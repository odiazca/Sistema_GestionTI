<?php
/**
 * Notificaciones al cliente: correo (SMTP nativo, sin librerías) y enlaces de WhatsApp.
 * Si el SMTP no está configurado, los correos quedan en la bandeja como «simulado».
 * Nunca lanza excepciones hacia el flujo principal.
 */
require_once __DIR__ . '/ia.php';

class Notifier
{
    public static function conf(string $k, string $d = ''): string { try { return ia_conf_get($k, $d); } catch (Throwable $e) { return $d; } }
    public static function smtpListo(): bool { return self::conf('smtp_host') !== '' && self::conf('smtp_from') !== ''; }

    /** Plantilla HTML de correo con la marca de la empresa */
    public static function plantilla(string $titulo, string $cuerpoHtml, ?string $boton = null, ?string $botonUrl = null): string
    {
        $emp = htmlspecialchars(cfg('empresa_nombre', APP_NAME), ENT_QUOTES, 'UTF-8');
        $b = $boton ? '<p style="margin:22px 0"><a href="' . htmlspecialchars((string)$botonUrl, ENT_QUOTES) . '" style="background:#4f46e5;color:#fff;text-decoration:none;padding:11px 22px;border-radius:9px;font-weight:600;display:inline-block">' . htmlspecialchars($boton, ENT_QUOTES, 'UTF-8') . '</a></p>' : '';
        return '<div style="background:#f1f5f9;padding:24px;font-family:Segoe UI,Arial,sans-serif"><div style="max-width:560px;margin:auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0">'
            . '<div style="background:linear-gradient(135deg,#0b1437,#4f46e5);color:#fff;padding:18px 24px;font-weight:700;font-size:1.05rem">' . $emp . '</div>'
            . '<div style="padding:24px;color:#1e293b;font-size:14px;line-height:1.6"><h2 style="margin:0 0 12px;font-size:1.15rem;color:#0f172a">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h2>' . $cuerpoHtml . $b . '</div>'
            . '<div style="padding:14px 24px;background:#f8fafc;color:#94a3b8;font-size:12px">Mensaje automático de ' . $emp . '. Si no esperabas este correo, ignóralo.</div></div></div>';
    }

    /** Registra y envía. Devuelve 'enviado' | 'simulado' | 'error' */
    public static function enviar(string $to, string $asunto, string $html, array $meta = []): string
    {
        $estado = 'simulado'; $err = null;
        try {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $estado = 'error'; $err = 'Correo del destinatario no válido'; }
            elseif (self::smtpListo()) { [$ok, $err] = self::smtp($to, $asunto, $html); $estado = $ok ? 'enviado' : 'error'; }
            Database::conn()->prepare('INSERT INTO notif_envios(canal,destinatario,asunto,cuerpo,estado,error,cliente_id,ticket_id,cotizacion_id) VALUES(?,?,?,?,?,?,?,?,?)')
                ->execute(['correo', mb_substr($to, 0, 160), mb_substr($asunto, 0, 200), $html, $estado, $err ? mb_substr($err, 0, 250) : null, $meta['cliente_id'] ?? null, $meta['ticket_id'] ?? null, $meta['cotizacion_id'] ?? null]);
        } catch (Throwable $e) { $estado = 'error'; }
        return $estado;
    }

    /** Cliente SMTP mínimo: SSL (465) o STARTTLS (587) o sin cifrado, AUTH LOGIN */
    private static function smtp(string $to, string $asunto, string $html): array
    {
        $host = self::conf('smtp_host'); $port = (int)self::conf('smtp_port', '587'); $seg = self::conf('smtp_seguridad', 'tls');
        $user = self::conf('smtp_user'); $pass = self::conf('smtp_pass') !== '' ? (ia_descifrar(self::conf('smtp_pass')) ?: '') : '';
        $from = self::conf('smtp_from'); $fromN = self::conf('smtp_nombre', cfg('empresa_nombre', APP_NAME));
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $fp = @stream_socket_client(($seg === 'ssl' ? 'ssl://' : 'tcp://') . "$host:$port", $en, $es, 10, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) return [false, "No se pudo conectar a $host:$port ($es)"];
        stream_set_timeout($fp, 10);
        $leer = function () use ($fp) { $r = ''; while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $r; };
        $cmd = function (string $c, array $ok) use ($fp, $leer) { if ($c !== '') fwrite($fp, $c . "\r\n"); $r = $leer(); return in_array((int)substr($r, 0, 3), $ok, true) ? [true, $r] : [false, trim($r)]; };
        try {
            [$o, $r] = $cmd('', [220]); if (!$o) return [false, "Saludo inválido: $r"];
            $ehlo = 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
            [$o, $r] = $cmd($ehlo, [250]); if (!$o) return [false, $r];
            if ($seg === 'tls') {
                [$o, $r] = $cmd('STARTTLS', [220]); if (!$o) return [false, "STARTTLS: $r"];
                if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return [false, 'No se pudo activar TLS'];
                [$o, $r] = $cmd($ehlo, [250]); if (!$o) return [false, $r];
            }
            if ($user !== '') {
                [$o, $r] = $cmd('AUTH LOGIN', [334]); if (!$o) return [false, "AUTH: $r"];
                [$o, $r] = $cmd(base64_encode($user), [334]); if (!$o) return [false, "Usuario: $r"];
                [$o, $r] = $cmd(base64_encode($pass), [235]); if (!$o) return [false, 'Autenticación rechazada (revisa usuario/contraseña)'];
            }
            [$o, $r] = $cmd("MAIL FROM:<$from>", [250]); if (!$o) return [false, $r];
            [$o, $r] = $cmd("RCPT TO:<$to>", [250, 251]); if (!$o) return [false, $r];
            [$o, $r] = $cmd('DATA', [354]); if (!$o) return [false, $r];
            $enc = fn($s) => '=?UTF-8?B?' . base64_encode($s) . '?=';
            $h = "From: {$enc($fromN)} <$from>\r\nTo: <$to>\r\nSubject: {$enc($asunto)}\r\nDate: " . date('r') . "\r\nMessage-ID: <" . bin2hex(random_bytes(8)) . "@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
            $body = chunk_split(base64_encode($html));
            fwrite($fp, $h . $body . "\r\n.\r\n"); $r = $leer();
            if ((int)substr($r, 0, 3) !== 250) return [false, trim($r)];
            fwrite($fp, "QUIT\r\n"); fclose($fp); return [true, null];
        } catch (Throwable $e) { @fclose($fp); return [false, $e->getMessage()]; }
    }

    /** Destinatarios de un cliente: correo de la empresa + usuarios del portal */
    private static function destinos(int $clienteId): array
    {
        $db = Database::conn(); $d = [];
        $c = $db->prepare('SELECT email FROM clientes WHERE id=?'); $c->execute([$clienteId]); if ($e = $c->fetchColumn()) $d[strtolower($e)] = $e;
        $u = $db->prepare("SELECT email FROM usuarios WHERE cliente_id=? AND estado=1"); $u->execute([$clienteId]); foreach ($u->fetchAll(PDO::FETCH_COLUMN) as $e) $d[strtolower($e)] = $e;
        return array_values($d);
    }
    private static function ticket(int $id): ?array
    {
        $s = Database::conn()->prepare("SELECT t.*, c.razon_social cliente, CONCAT(u.nombres,' ',u.apellidos) tecnico FROM tickets t JOIN clientes c ON c.id=t.cliente_id LEFT JOIN usuarios u ON u.id=t.tecnico_id WHERE t.id=?"); $s->execute([$id]); return $s->fetch() ?: null;
    }
    private static function urlTicket(array $t, bool $portal): string
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'; $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$proto://$host" . BASE_URL . '/portal/ticket/' . $t['id'];
    }

    public static function ticketCreado(int $id): void
    {
        try {
            if (self::conf('notif_ticket_creado', '1') !== '1') return; $t = self::ticket($id); if (!$t) return;
            $html = self::plantilla('Recibimos tu solicitud', '<p>Hola, registramos el ticket <b>' . e($t['codigo']) . '</b> para <b>' . e($t['cliente']) . '</b>.</p><p><b>Asunto:</b> ' . e($t['asunto']) . '<br><b>Prioridad:</b> ' . e(PRIORIDADES[$t['prioridad']] ?? $t['prioridad']) . '<br><b>Atención estimada:</b> ' . fdate($t['fecha_limite'], 'd/m/Y H:i') . '</p><p>Te avisaremos cuando cambie su estado.</p>', 'Ver mi ticket', self::urlTicket($t, true));
            foreach (self::destinos((int)$t['cliente_id']) as $to) self::enviar($to, "[{$t['codigo']}] Recibimos tu solicitud", $html, ['cliente_id' => $t['cliente_id'], 'ticket_id' => $id]);
        } catch (Throwable $e) {}
    }
    /** Aviso interno cuando un cliente crea un ticket desde el portal */
    public static function avisoInterno(int $id): void
    {
        try {
            $to = self::conf('notif_copia'); if ($to === '') return; $t = self::ticket($id); if (!$t) return;
            self::enviar($to, "Nuevo ticket del portal: {$t['codigo']}", self::plantilla('Nuevo ticket desde el portal', '<p><b>' . e($t['cliente']) . '</b> reportó: ' . e($t['asunto']) . '</p><p>Prioridad: ' . e($t['prioridad']) . '</p>'), ['cliente_id' => $t['cliente_id'], 'ticket_id' => $id]);
        } catch (Throwable $e) {}
    }
    public static function ticketEstado(int $id, string $estado, string $detalle = ''): void
    {
        try {
            if (self::conf('notif_ticket_estado', '1') !== '1') return; $t = self::ticket($id); if (!$t) return;
            $msg = ['en_proceso' => 'Un técnico ya está atendiendo tu caso.', 'pendiente' => 'Necesitamos información o una acción de tu parte para continuar.', 'resuelto' => 'Marcamos tu caso como resuelto. Si el problema continúa, respóndenos y lo reabriremos.', 'cerrado' => 'Cerramos tu caso. ¡Gracias por confiar en nosotros!', 'abierto' => 'Tu caso fue reabierto y volverá a ser atendido.'][$estado] ?? '';
            $html = self::plantilla('Actualización de tu ticket', '<p>El ticket <b>' . e($t['codigo']) . '</b> (' . e($t['asunto']) . ') cambió a <b>' . e(ESTADOS_TK[$estado] ?? $estado) . '</b>.</p><p>' . e($msg) . '</p>' . ($t['tecnico'] ? '<p>Técnico: ' . e($t['tecnico']) . '</p>' : '') . $detalle, 'Ver mi ticket', self::urlTicket($t, true));
            foreach (self::destinos((int)$t['cliente_id']) as $to) self::enviar($to, "[{$t['codigo']}] " . (ESTADOS_TK[$estado] ?? $estado), $html, ['cliente_id' => $t['cliente_id'], 'ticket_id' => $id]);
        } catch (Throwable $e) {}
    }
    public static function ticketRespuesta(int $id, string $texto): void
    {
        try {
            if (self::conf('notif_ticket_respuesta', '1') !== '1') return; $t = self::ticket($id); if (!$t) return;
            $html = self::plantilla('Nueva respuesta de soporte', '<p>Hay una respuesta en tu ticket <b>' . e($t['codigo']) . '</b>:</p><blockquote style="border-left:4px solid #4f46e5;margin:0;padding:6px 14px;background:#f8fafc">' . nl2br(e(mb_substr($texto, 0, 600))) . '</blockquote>', 'Responder desde el portal', self::urlTicket($t, true));
            foreach (self::destinos((int)$t['cliente_id']) as $to) self::enviar($to, "[{$t['codigo']}] Nueva respuesta de soporte", $html, ['cliente_id' => $t['cliente_id'], 'ticket_id' => $id]);
        } catch (Throwable $e) {}
    }
    public static function credenciales(int $clienteId, string $to, string $nombre, string $login, string $pass): void
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'; $u = "$proto://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . '/portal';
        self::enviar($to, 'Tu acceso al portal de soporte', self::plantilla('Bienvenido al portal de soporte', '<p>Hola ' . e($nombre) . ', ya puedes reportar y seguir tus tickets en línea.</p><p><b>Usuario:</b> ' . e($login) . '<br><b>Contraseña temporal:</b> ' . e($pass) . '</p><p>Te recomendamos cambiarla al ingresar.</p>', 'Entrar al portal', $u), ['cliente_id' => $clienteId]);
    }
    public static function cotizacion(array $q, array $items, string $to, string $mensaje, ?int $cliId): string
    {
        $f = fn($n) => money($n); $filas = '';
        foreach ($items as $it) $filas .= '<tr><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0">' . e($it['descripcion']) . '</td><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right">' . rtrim(rtrim(number_format($it['cantidad'], 2), '0'), '.') . '</td><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right">' . $f($it['precio_unit']) . '</td><td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right">' . $f($it['cantidad'] * $it['precio_unit']) . '</td></tr>';
        $tab = '<table style="width:100%;border-collapse:collapse;font-size:13px"><tr style="background:#f1f5f9"><th align="left" style="padding:6px 8px">Descripción</th><th align="right" style="padding:6px 8px">Cant.</th><th align="right" style="padding:6px 8px">P. unit.</th><th align="right" style="padding:6px 8px">Importe</th></tr>' . $filas . '</table>'
            . '<p style="text-align:right;margin:10px 0 0">Subtotal ' . $f($q['subtotal']) . ($q['descuento'] > 0 ? ' · Descuento −' . $f($q['descuento']) : '') . ' · IGV ' . $f($q['igv']) . '<br><b style="font-size:16px;color:#4f46e5">Total ' . $f($q['total']) . '</b></p><p style="color:#64748b;font-size:12px">Válida hasta el ' . fdate($q['valida_hasta']) . '</p>'
            . ($q['condiciones'] ? '<p style="font-size:12px;color:#475569;white-space:pre-line">' . e($q['condiciones']) . '</p>' : '');
        $html = self::plantilla('Cotización ' . $q['codigo'], ($mensaje !== '' ? '<p>' . nl2br(e($mensaje)) . '</p>' : '<p>Adjuntamos nuestra propuesta económica.</p>') . $tab);
        return self::enviar($to, 'Cotización ' . $q['codigo'] . ' · ' . cfg('empresa_nombre', APP_NAME), $html, ['cliente_id' => $cliId, 'cotizacion_id' => $q['id']]);
    }

    /** Enlace de WhatsApp (clic para chatear); asume Perú (+51) si el número tiene 9 dígitos */
    public static function waLink(?string $tel, string $texto): ?string
    {
        $n = preg_replace('/\D+/', '', (string)$tel); if ($n === '') return null;
        if (strlen($n) === 9) $n = '51' . $n; elseif (strlen($n) < 9) return null;
        return 'https://wa.me/' . $n . '?text=' . rawurlencode($texto);
    }
}

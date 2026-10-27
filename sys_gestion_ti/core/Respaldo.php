<?php
/** Respaldos de la base de datos: crear, listar, restaurar y reiniciar. Sin dependencias externas. */
class Respaldo
{
    const MARCA = '-- TECHDESK-BACKUP v1';
    const TIPOS = ['manual' => 'Manual', 'auto' => 'Automático', 'previo' => 'Antes de restaurar', 'reinicio' => 'Antes de reiniciar'];

    /** Tablas que se vacían al reiniciar: grupo => tablas (orden indiferente: FK desactivadas) */
    const MOVIMIENTOS = [
        'Mesa de ayuda' => ['tickets', 'ticket_comentarios', 'ordenes_servicio', 'ordenes_repuestos', 'visitas'],
        'Facturación' => ['fac_comprobantes', 'fac_items', 'fac_pagos'],
        'CRM comercial' => ['crm_leads', 'crm_oportunidades', 'crm_actividades', 'crm_cotizaciones', 'crm_cotizacion_items'],
        'Historial y avisos' => ['actividad', 'notif_envios', 'ia_consultas'],
    ];
    const MAESTROS = [
        'Clientes y contactos' => ['clientes', 'crm_contactos'],
        'Equipos y contratos' => ['activos', 'contratos'],
        'Repuestos' => ['repuestos'],
    ];
    const CATALOGOS = ['Categorías y base de conocimiento' => ['categorias', 'kb_articulos']];

    public static function dir(): string
    {
        $d = ROOT_PATH . '/storage/respaldos';
        if (!is_dir($d)) { @mkdir($d, 0775, true); @file_put_contents($d . '/.htaccess', "Require all denied\nDeny from all\n"); @file_put_contents($d . '/index.html', ''); }
        return $d;
    }
    public static function nombreValido(string $n): bool { return (bool)preg_match('/^respaldo_\d{8}_\d{6}_[a-z]+\.sql(\.gz)?$/', $n); }
    public static function ruta(string $n): ?string { return self::nombreValido($n) && is_file(self::dir() . '/' . $n) ? self::dir() . '/' . $n : null; }
    public static function zlib(): bool { return function_exists('gzopen'); }

    public static function listar(): array
    {
        $o = [];
        foreach (glob(self::dir() . '/respaldo_*.sql*') ?: [] as $f) {
            $n = basename($f); if (!self::nombreValido($n)) continue;
            preg_match('/_(\d{8})_(\d{6})_([a-z]+)\.sql/', $n, $m);
            $o[] = ['nombre' => $n, 'tamano' => filesize($f), 'fecha' => date('Y-m-d H:i:s', filemtime($f)), 'tipo' => $m[3] ?? 'manual', 'gz' => substr($n, -3) === '.gz'];
        }
        usort($o, fn($a, $b) => strcmp($b['fecha'], $a['fecha']) ?: strcmp($b['nombre'], $a['nombre']));
        return $o;
    }
    public static function tamano(int $b): string { return $b >= 1048576 ? round($b / 1048576, 2) . ' MB' : max(1, round($b / 1024)) . ' KB'; }

    /** Crea un respaldo en el servidor y devuelve su nombre */
    public static function crear(string $tipo = 'manual', bool $comprimir = true): string
    {
        @set_time_limit(0); $db = Database::conn(); $tipo = preg_replace('/[^a-z]/', '', $tipo) ?: 'manual';
        $gz = $comprimir && self::zlib(); $nombre = 'respaldo_' . date('Ymd_His') . '_' . $tipo . '.sql' . ($gz ? '.gz' : ''); $ruta = self::dir() . '/' . $nombre;
        $h = $gz ? gzopen($ruta, 'wb6') : fopen($ruta, 'wb'); if (!$h) throw new RuntimeException('No se pudo escribir en la carpeta de respaldos (storage/respaldos). Revisa los permisos.');
        $w = function (string $s) use ($h, $gz) { $gz ? gzwrite($h, $s) : fwrite($h, $s); };
        $tablas = $db->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        $w(self::MARCA . "\n-- " . APP_NAME . ' · base de datos ' . DB_NAME . ' · ' . date('Y-m-d H:i:s') . ' · ' . count($tablas) . " tablas\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
        foreach ($tablas as $t) {
            $w("DROP TABLE IF EXISTS `$t`;\n" . $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1] . ";\n\n");
            $st = $db->query("SELECT * FROM `$t`", PDO::FETCH_NUM); $buf = [];
            while ($row = $st->fetch()) {
                $buf[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string)$v), $row)) . ')';
                if (count($buf) >= 150) { $w("INSERT INTO `$t` VALUES\n" . implode(",\n", $buf) . ";\n"); $buf = []; }
            }
            if ($buf) $w("INSERT INTO `$t` VALUES\n" . implode(",\n", $buf) . ";\n");
            $w("\n");
        }
        $w("SET FOREIGN_KEY_CHECKS=1;\n"); $gz ? gzclose($h) : fclose($h);
        return $nombre;
    }

    /** Respaldo automático oportunista (se llama al abrir el dashboard del administrador) */
    public static function auto(): void
    {
        $modo = cfg('respaldo_auto', 'no'); if (!in_array($modo, ['diario', 'semanal'], true)) return;
        $ult = (int)cfg('respaldo_auto_ts', '0'); $lim = $modo === 'diario' ? 86400 : 7 * 86400; if (time() - $ult < $lim) return;
        $db = Database::conn(); $db->prepare('INSERT INTO configuracion(clave,valor) VALUES("respaldo_auto_ts",?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)')->execute([(string)time()]);
        self::crear('auto'); self::podar();
    }
    public static function podar(): void
    {
        $keep = max(1, (int)cfg('respaldo_retencion', '10')); $n = 0;
        foreach (self::listar() as $r) if ($r['tipo'] === 'auto' && ++$n > $keep) @unlink(self::dir() . '/' . $r['nombre']);
    }

    /** Valida y restaura un archivo .sql / .sql.gz. Devuelve [sentencias, tablas]. Lanza excepción con el motivo. */
    public static function restaurar(string $ruta): array
    {
        @set_time_limit(0); $gz = substr($ruta, -3) === '.gz';
        if ($gz && !self::zlib()) throw new RuntimeException('Este servidor no puede leer archivos .gz. Descomprime el archivo y sube el .sql.');
        $h = $gz ? gzopen($ruta, 'rb') : fopen($ruta, 'rb'); if (!$h) throw new RuntimeException('No se pudo abrir el archivo.');
        $leer = fn() => $gz ? gzgets($h) : fgets($h);
        $primera = $leer(); if ($primera === false || strpos($primera, self::MARCA) !== 0) throw new RuntimeException('El archivo no es un respaldo de este sistema (falta la cabecera TECHDESK-BACKUP). No se cambió nada.');
        $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $buf = ''; $inq = false; $q = ''; $n = 0; $tablas = 0;
        $ok = '/^(SET\s|DROP TABLE IF EXISTS\s+`|CREATE TABLE\s+`|INSERT INTO\s+`)/i';
        while (($line = $leer()) !== false) {
            if ($buf === '' && (trim($line) === '' || strncmp($line, '--', 2) === 0)) continue;
            $buf .= $line; $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $c = $line[$i];
                if ($inq) { if ($c === '\\' && $q !== '`') $i++; elseif ($c === $q) $inq = false; }
                elseif ($c === "'" || $c === '"' || $c === '`') { $inq = true; $q = $c; }
            }
            if (!$inq && substr(rtrim($line), -1) === ';') {
                $sql = trim($buf); $buf = '';
                if (!preg_match($ok, $sql)) throw new RuntimeException('El archivo contiene una instrucción no permitida y se detuvo la restauración: ' . mb_substr($sql, 0, 60));
                $pdo->exec($sql); $n++; if (stripos($sql, 'CREATE TABLE') === 0) $tablas++;
            }
        }
        $gz ? gzclose($h) : fclose($h);
        if ($inq || trim($buf) !== '') throw new RuntimeException('El archivo está incompleto o dañado (termina a mitad de una instrucción).');
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        require_once ROOT_PATH . '/database/migraciones.php'; ejecutar_migraciones($pdo);   // sube a la versión actual un respaldo antiguo y recrea las vistas
        return [$n, $tablas];
    }

    /** Conteo de filas por grupo, para mostrar qué se va a borrar */
    public static function conteo(array $grupos): array
    {
        $db = Database::conn(); $o = [];
        foreach ($grupos as $g => $tablas) { $t = 0; foreach ($tablas as $x) { try { $t += (int)$db->query("SELECT COUNT(*) FROM `$x`")->fetchColumn(); } catch (Throwable $e) {} } $o[$g] = $t; }
        return $o;
    }

    /** Reinicia datos. $modo: 'movimientos' | 'completo'. Opciones: usuarios, catalogos, empresa (conservar). */
    public static function reiniciar(string $modo, array $conservar, int $adminId): array
    {
        $db = Database::conn(); $vaciar = self::MOVIMIENTOS;
        if ($modo === 'completo') { $vaciar += self::MAESTROS; if (empty($conservar['catalogos'])) $vaciar += self::CATALOGOS; }
        $db->exec('SET FOREIGN_KEY_CHECKS=0'); $filas = 0;
        foreach ($vaciar as $tablas) foreach ($tablas as $t) { try { $filas += (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); $db->exec("TRUNCATE TABLE `$t`"); } catch (Throwable $e) {} }
        if ($modo === 'completo') {
            $db->prepare('DELETE FROM usuarios WHERE id<>? AND (cliente_id IS NOT NULL' . (empty($conservar['usuarios']) ? ' OR 1=1' : '') . ')')->execute([$adminId]);
            $db->exec('UPDATE usuarios SET cliente_id=NULL WHERE cliente_id IS NOT NULL');
            if (empty($conservar['empresa'])) $db->exec("DELETE FROM configuracion WHERE clave LIKE 'empresa\\_%' OR clave IN ('horario_atencion')");
            try { $db->exec("UPDATE crm_etapas SET id=id"); } catch (Throwable $e) {}
        }
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
        return [$filas];
    }
}

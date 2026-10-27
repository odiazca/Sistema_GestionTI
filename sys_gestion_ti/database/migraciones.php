<?php
/** Ejecuta todas las migraciones de forma idempotente (migrar.php y restauración de respaldos) */
require_once __DIR__ . '/v3.php'; require_once __DIR__ . '/v7.php'; require_once __DIR__ . '/v8.php'; require_once __DIR__ . '/v9.php'; require_once __DIR__ . '/v10.php';
function ejecutar_migraciones(PDO $pdo): array
{
    $log = instalar_v3($pdo);
    $log = array_merge($log, instalar_crm($pdo), instalar_facturacion($pdo), instalar_v9($pdo), instalar_v10($pdo));
    require_once dirname(__DIR__) . '/core/ia.php';
    ia_instalar($pdo, true); $log[] = 'Asistente IA y Chat IA listos (tabla ia_consultas y vistas v_ia_*).';
    return $log;
}

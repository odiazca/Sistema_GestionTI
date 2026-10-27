<?php
/** v10: Empresa (logo, formato de moneda) y módulo de respaldos */
function instalar_v10(PDO $pdo): array
{
    $log = [];
    $t = $pdo->query("SELECT DATA_TYPE FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='configuracion' AND column_name='valor'")->fetchColumn();
    if ($t && strtolower($t) !== 'mediumtext' && strtolower($t) !== 'longtext') { $pdo->exec('ALTER TABLE configuracion MODIFY valor MEDIUMTEXT NULL'); $log[] = 'Configuración preparada para guardar el logo.'; }
    $dir = dirname(__DIR__) . '/storage/respaldos';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    if (is_dir($dir)) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
        @file_put_contents($dir . '/index.html', '');
        $log[] = 'Carpeta de respaldos protegida (storage/respaldos).';
    }
    return $log;
}

<?php
/**
 * Lethe - purge des fichiers expirés/révocés, des messages secrets expirés ou
 * consultés, des dépôts expirés et des fragments d'upload abandonnés.
 * À exécuter périodiquement via cron, par exemple toutes les heures :
 *   0 * * * * /usr/bin/php /chemin/vers/lethe/cron/cleanup.php >> /var/log/lethe-cleanup.log 2>&1
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Ce script ne peut être exécuté qu'en ligne de commande (cron).\n");
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/autoload.php';

use Lethe\Helpers;
use Lethe\Services\Cleanup;

try {
    $result = Cleanup::run();
} catch (Throwable $e) {
    fwrite(STDERR, "[cleanup] Erreur : " . $e->getMessage() . "\n");
    exit(1);
}

foreach ($result['log'] as $line) {
    echo $line . "\n";
}
echo "Terminé. {$result['deleted']} élément(s) supprimé(s), " . Helpers::formatBytes($result['freed_bytes']) . " libéré(s).\n";

<?php
declare(strict_types=1);

/** Prueba rápida de conexión. Uso: php scripts/probar_conexion.php */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../config/database.php';

$db->command(['ping' => 1]);
echo "Conexión exitosa con MongoDB." . PHP_EOL;
echo "Base de datos: " . $db->getDatabaseName() . PHP_EOL;
echo "Colecciones:" . PHP_EOL;
foreach ($db->listCollectionNames() as $name) {
    echo " - $name" . PHP_EOL;
}

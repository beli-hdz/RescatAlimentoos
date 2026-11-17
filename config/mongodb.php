<?php
declare(strict_types=1);

/**
 * Conexión MongoDB Atlas. Requiere:
 *   composer install
 *   extensión PHP mongodb habilitada
 * Variables: MONGODB_URI y MONGODB_DATABASE.
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use MongoDB\Client;
use MongoDB\Database;

function mongo_database(): Database
{
    static $database = null;
    if ($database instanceof Database) {
        return $database;
    }

    $uri = getenv('MONGODB_URI') ?: '';
    $name = getenv('MONGODB_DATABASE') ?: 'rescat_alimentos';

    if ($uri === '') {
        throw new RuntimeException('Falta configurar MONGODB_URI en las variables de entorno.');
    }

    $client = new Client($uri, [
        'serverSelectionTimeoutMS' => 10000,
        'retryWrites' => true,
    ]);

    // Fuerza una comprobación real de conectividad antes de continuar.
    $client->selectDatabase('admin')->command(['ping' => 1])->toArray();
    $database = $client->selectDatabase($name);
    return $database;
}

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

    // getenv() solo ve variables reales del sistema; el .env cargado por Dotenv
    // vive en $_ENV/$_SERVER, así que se revisan los tres.
    $read = static function (string $key): string {
        $v = getenv($key);
        if ($v === false || $v === '') {
            $v = $_ENV[$key] ?? $_SERVER[$key] ?? '';
        }
        return is_string($v) ? trim($v) : '';
    };
    $uri = $read('MONGODB_URI');
    $name = $read('MONGODB_DATABASE') ?: 'rescat_alimentos';

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

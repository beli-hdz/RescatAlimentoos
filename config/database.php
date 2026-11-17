<?php
declare(strict_types=1);

/**
 * Conexión a MongoDB (Atlas) y funciones auxiliares.
 *
 * Variables de entorno:
 *   MONGODB_URI        Cadena de conexión de Atlas (obligatoria).
 *   MONGODB_DATABASE   Nombre de la base de datos (por defecto: rescat_alimentos).
 *   APP_TIMEZONE       Zona horaria (por defecto: America/Monterrey).
 *   APP_DEBUG          Pon 1 para ver el detalle de los errores en pantalla (solo en local).
 *
 * En local puedes guardarlas en un archivo .env en la raíz del proyecto.
 * En Render se configuran en Environment. NUNCA subas el .env a GitHub.
 */

use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Operation\FindOneAndUpdate;

/* ---------- Variables de entorno ---------- */

(function (): void {
    $file = __DIR__ . '/../.env';
    if (!is_readable($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) putenv($key . '=' . $value);
    }
})();

function rescat_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;
    }
    return (is_string($value) && $value !== '') ? $value : $default;
}

date_default_timezone_set(rescat_env('APP_TIMEZONE', 'America/Monterrey'));

/* ---------- Manejo de errores ---------- */

set_exception_handler(function (Throwable $e): void {
    error_log('[RescatAlimentos] ' . get_class($e) . ': ' . $e->getMessage());
    if (PHP_SAPI !== 'cli' && !headers_sent()) http_response_code(500);
    $debug = rescat_env('APP_DEBUG') === '1' || PHP_SAPI === 'cli';
    exit($debug
        ? 'Error: ' . $e->getMessage() . (PHP_SAPI === 'cli' ? PHP_EOL : '')
        : 'Ocurrió un error en el servidor. Inténtalo de nuevo en unos minutos.');
});

/* ---------- Conexión ---------- */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Faltan las dependencias de PHP. Ejecuta "composer install" en la carpeta del proyecto.');
}
require_once $autoload;

$mongoUri = rescat_env('MONGODB_URI');
if ($mongoUri === null) {
    http_response_code(500);
    exit('Falta configurar la variable de entorno MONGODB_URI.');
}

$mongoClient = new Client(
    $mongoUri,
    ['serverSelectionTimeoutMS' => 8000],
    ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
);
/** @var Database $db */
$db = $mongoClient->selectDatabase(rescat_env('MONGODB_DATABASE', 'rescat_alimentos'));

/* ---------- Funciones auxiliares ---------- */

/** Fecha/hora actual lista para guardar en MongoDB. */
function mongo_now(): UTCDateTime
{
    return new UTCDateTime();
}

function mongo_date(DateTimeInterface $date): UTCDateTime
{
    return new UTCDateTime($date);
}

/**
 * Convierte un documento de MongoDB en una fila lista para las vistas:
 * agrega "id" (copia de _id) y convierte las fechas a texto "Y-m-d H:i:s".
 */
function mongo_row(?array $doc): ?array
{
    if ($doc === null) return null;
    foreach ($doc as $key => $value) {
        if ($value instanceof UTCDateTime) {
            $doc[$key] = $value->toDateTime()
                ->setTimezone(new DateTimeZone(date_default_timezone_get()))
                ->format('Y-m-d H:i:s');
        }
    }
    if (array_key_exists('_id', $doc) && !array_key_exists('id', $doc)) {
        $doc['id'] = $doc['_id'];
    }
    return $doc;
}

/** Igual que mongo_row() pero para un cursor o lista completa. */
function mongo_rows(iterable $cursor): array
{
    $rows = [];
    foreach ($cursor as $doc) {
        $rows[] = mongo_row((array)$doc);
    }
    return $rows;
}

/**
 * Reemplaza AUTO_INCREMENT: devuelve el siguiente id numérico de una colección.
 * Con $count > 1 reserva un bloque y devuelve el primer id del bloque.
 */
function next_id(Database $db, string $sequence, int $count = 1): int
{
    $doc = $db->selectCollection('counters')->findOneAndUpdate(
        ['_id' => $sequence],
        ['$inc' => ['seq' => $count]],
        ['upsert' => true, 'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
    );
    return (int)$doc['seq'] - $count + 1;
}

/** Búsqueda "contiene", sin distinguir mayúsculas (equivale al LIKE '%texto%' de MySQL). */
function mongo_regex(string $text): Regex
{
    return new Regex(preg_quote($text, '/'), 'i');
}

/** Comparaciones sin distinguir mayúsculas (como utf8mb4_unicode_ci). */
function rescat_ci_collation(): array
{
    return ['locale' => 'en', 'strength' => 2];
}

/** Orden alfabético en español (ignora mayúsculas al ordenar). */
function rescat_sort_collation(): array
{
    return ['locale' => 'es'];
}

/** ¿La excepción es por un valor duplicado en un índice único? */
function is_duplicate_key(Throwable $e): bool
{
    return $e->getCode() === 11000 || str_contains($e->getMessage(), 'E11000');
}

/** Valida una fecha "Y-m-d" y la devuelve como objeto, o null si no es válida. */
function parse_ymd(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return ($date && $date->format('Y-m-d') === $value) ? $date : null;
}

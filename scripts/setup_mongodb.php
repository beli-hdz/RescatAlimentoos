<?php
declare(strict_types=1);

/**
 * Prepara la base de datos de MongoDB. Es seguro ejecutarlo muchas veces:
 *   1) Crea los índices (incluidos los únicos de correo y usuario).
 *   2) Si no existe ningún super administrador y están definidas ADMIN_USERNAME,
 *      ADMIN_EMAIL y ADMIN_PASSWORD, crea el primero.
 *
 * Se ejecuta solo al arrancar el contenedor (docker/entrypoint.sh) o a mano:
 *   php scripts/setup_mongodb.php
 */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../config/database.php';

$unique = ['unique' => true, 'collation' => rescat_ci_collation()];

$indexes = [
    'users' => [
        [['email' => 1], $unique + ['name' => 'uniq_email']],
        [['username' => 1], $unique + ['name' => 'uniq_username']],
        [['created_at' => -1], ['name' => 'created_at_desc']],
    ],
    'centers' => [
        [['center_type' => 1, 'active' => 1, 'name' => 1], ['name' => 'type_active_name']],
    ],
    'donations' => [
        [['user_id' => 1, 'created_at' => -1], ['name' => 'user_created']],
        [['status' => 1, 'created_at' => -1], ['name' => 'status_created']],
        [['center_id' => 1], ['name' => 'center_id']],
        [['pickup_point_id' => 1], ['name' => 'pickup_point_id']],
        [['status' => 1, 'expiry_date' => 1], ['name' => 'status_expiry']],
        [['created_at' => -1], ['name' => 'created_at_desc']],
    ],
    'forum_posts' => [
        [['created_at' => -1], ['name' => 'created_at_desc']],
        [['user_id' => 1], ['name' => 'user_id']],
    ],
    'forum_replies' => [
        [['post_id' => 1, 'created_at' => 1], ['name' => 'post_created']],
        [['user_id' => 1], ['name' => 'user_id']],
    ],
    'delivery_updates' => [
        [['donation_id' => 1, 'created_at' => -1], ['name' => 'donation_created']],
        [['user_id' => 1], ['name' => 'user_id']],
    ],
    'notifications' => [
        [['user_id' => 1, 'is_read' => 1, 'created_at' => -1], ['name' => 'user_read_created']],
    ],
];

foreach ($indexes as $collection => $list) {
    foreach ($list as [$keys, $options]) {
        $db->selectCollection($collection)->createIndex($keys, $options);
    }
    echo "[ok] índices de «$collection»" . PHP_EOL;
}

/* ---------- Primer super administrador ---------- */

$users = $db->selectCollection('users');
if ($users->countDocuments(['role' => 'super_admin']) === 0) {
    $username = rescat_env('ADMIN_USERNAME');
    $email = rescat_env('ADMIN_EMAIL');
    $password = rescat_env('ADMIN_PASSWORD');

    if ($username && $email && $password) {
        $existing = $users->findOne(
            ['$or' => [['email' => $email], ['username' => $username]]],
            ['collation' => rescat_ci_collation()]
        );
        if ($existing) {
            $users->updateOne(['_id' => $existing['_id']], ['$set' => ['role' => 'super_admin']]);
            echo "[ok] «{$existing['username']}» ahora es super administrador" . PHP_EOL;
        } else {
            $users->insertOne([
                '_id' => next_id($db, 'users'),
                'name' => rescat_env('ADMIN_NAME', 'Administrador'),
                'email' => $email,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'super_admin',
                'created_at' => mongo_now(),
            ]);
            echo "[ok] super administrador «$username» creado" . PHP_EOL;
        }
    } else {
        echo "[aviso] No hay super administrador. Define ADMIN_USERNAME, ADMIN_EMAIL y ADMIN_PASSWORD para crear el primero." . PHP_EOL;
    }
}

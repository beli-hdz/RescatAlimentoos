<?php
use MongoDB\Database;

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function logged_in(): bool { return isset($_SESSION['user_id']); }
function require_login(): void { if (!logged_in()) { header('Location: login.php'); exit; } }
function require_admin(): void {
    require_login();
    if (!in_array(($_SESSION['role'] ?? 'user'), ['admin','super_admin'], true)) {
        http_response_code(403);
        exit('Acceso denegado. Esta sección es exclusiva del administrador.');
    }
}
function is_super_admin(): bool { return ($_SESSION['role'] ?? 'user') === 'super_admin'; }
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf_token'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) { http_response_code(403); exit('Solicitud no válida. Regresa e inténtalo de nuevo.'); }
    }
}
function csrf_check(): void { verify_csrf(); }
function flash(string $message, string $type='success'): void { $_SESSION['flash'] = ['message'=>$message, 'type'=>$type]; }
function show_flash(): void {
    if (!empty($_SESSION['flash'])) { $f=$_SESSION['flash']; echo '<div class="notice '.e($f['type']).'">'.e($f['message']).'</div>'; unset($_SESSION['flash']); }
}


/** Guarda una notificación para un usuario. */
function create_notification(Database $db, int $userId, string $title, string $message, string $url, string $type): void {
    $db->selectCollection('notifications')->insertOne([
        '_id' => next_id($db, 'notifications'),
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'url' => $url,
        'type' => $type,
        'is_read' => false,
        'created_at' => mongo_now(),
    ]);
}

/** Guarda la misma notificación para varios usuarios con una sola operación. */
function create_notifications(Database $db, array $userIds, string $title, string $message, string $url, string $type): void {
    $userIds = array_values(array_unique(array_map('intval', $userIds)));
    if (!$userIds) return;
    $nextId = next_id($db, 'notifications', count($userIds));
    $now = mongo_now();
    $docs = [];
    foreach ($userIds as $i => $userId) {
        $docs[] = [
            '_id' => $nextId + $i,
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'type' => $type,
            'is_read' => false,
            'created_at' => $now,
        ];
    }
    $db->selectCollection('notifications')->insertMany($docs);
}

/** Notifica a todos los administradores. */
function notify_admins(Database $db, string $title, string $message, string $url, string $type): void {
    $ids = $db->selectCollection('users')->distinct('_id', ['role' => ['$in' => ['admin', 'super_admin']]]);
    create_notifications($db, $ids, $title, $message, $url, $type);
}

/** Notifica a todos los usuarios excepto a quien generó la acción. */
function notify_all_except(Database $db, int $exceptUserId, string $title, string $message, string $url, string $type): void {
    $ids = $db->selectCollection('users')->distinct('_id', ['_id' => ['$ne' => $exceptUserId]]);
    create_notifications($db, $ids, $title, $message, $url, $type);
}

/** Cantidad de donaciones por estado: ['available' => 3, 'delivered' => 1, ...]. */
function donation_status_counts(Database $db): array {
    $counts = [];
    $rows = $db->selectCollection('donations')->aggregate([
        ['$group' => ['_id' => '$status', 'n' => ['$sum' => 1]]],
    ]);
    foreach ($rows as $row) $counts[(string)$row['_id']] = (int)$row['n'];
    return $counts;
}

/* ---------- Eliminaciones en cascada (antes lo hacían las llaves foráneas de MySQL) ---------- */

/** Elimina una o varias donaciones junto con su historial de seguimiento. */
function delete_donations(Database $db, array $donationIds): void {
    $donationIds = array_values(array_map('intval', $donationIds));
    if (!$donationIds) return;
    $db->selectCollection('delivery_updates')->deleteMany(['donation_id' => ['$in' => $donationIds]]);
    $db->selectCollection('forum_posts')->updateMany(['donation_id' => ['$in' => $donationIds]], ['$set' => ['donation_id' => null]]);
    $db->selectCollection('donations')->deleteMany(['_id' => ['$in' => $donationIds]]);
}

/** Elimina un usuario y todo lo que le pertenece. */
function delete_user_cascade(Database $db, int $userId): void {
    delete_donations($db, $db->selectCollection('donations')->distinct('_id', ['user_id' => $userId]));

    $postIds = array_map('intval', $db->selectCollection('forum_posts')->distinct('_id', ['user_id' => $userId]));
    if ($postIds) {
        $db->selectCollection('forum_replies')->deleteMany(['post_id' => ['$in' => $postIds]]);
        $db->selectCollection('forum_posts')->deleteMany(['_id' => ['$in' => $postIds]]);
    }
    $db->selectCollection('forum_replies')->deleteMany(['user_id' => $userId]);
    $db->selectCollection('delivery_updates')->deleteMany(['user_id' => $userId]);
    $db->selectCollection('notifications')->deleteMany(['user_id' => $userId]);
    $db->selectCollection('users')->deleteOne(['_id' => $userId]);
}

/** Elimina un centro o punto de recolección y libera las donaciones que lo usaban. */
function delete_center_cascade(Database $db, int $centerId): void {
    $donations = $db->selectCollection('donations');
    $donations->updateMany(['center_id' => $centerId], ['$set' => ['center_id' => null]]);
    $donations->updateMany(['pickup_point_id' => $centerId], ['$set' => ['pickup_point_id' => null]]);
    $db->selectCollection('centers')->deleteOne(['_id' => $centerId]);
}



/**
 * Devuelve una imagen específica para el alimento cuando no se cargó una foto propia.
 * Las fotos subidas por el usuario siempre tienen prioridad.
 */
function donation_image_src(?string $imagePath, string $foodName = '', string $category = ''): string {
    if ($imagePath && is_file(__DIR__ . '/../' . $imagePath)) return $imagePath;

    $raw = strtolower(trim($foodName . ' ' . $category));
    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $raw) ?: $raw;
    $images = [
        'papa' => 'photo-1518977676601-b53f82aba655',
        'patata' => 'photo-1518977676601-b53f82aba655',
        'tomate' => 'photo-1546094096-0df4bcaaa337',
        'zanahoria' => 'photo-1445282768818-728615cc910a',
        'manzana' => 'photo-1560806887-1e4cd0b6cbd6',
        'platano' => 'photo-1571771894821-ce9b6c11b08e',
        'banana' => 'photo-1571771894821-ce9b6c11b08e',
        'naranja' => 'photo-1547514701-42782101795e',
        'uva' => 'photo-1537640538966-79f369143f8f',
        'lechuga' => 'photo-1556801712-76c8eb07bbc9',
        'brocoli' => 'photo-1459411552884-841db9b3cc2a',
        'pan' => 'photo-1509440159596-0249088772ff',
        'arroz' => 'photo-1586201375761-83865001e31c',
        'leche' => 'photo-1563636619-e9143da7973b',
        'huevo' => 'photo-1506976785307-8732e854ad03',
        'queso' => 'photo-1486297678162-eb2a19b0a32d'
    ];
    foreach ($images as $term => $photoId) {
        if (preg_match('/\\b' . preg_quote($term, '/') . '\\w*/', $name)) {
            return 'https://images.unsplash.com/' . $photoId . '?auto=format&fit=crop&w=900&q=82';
        }
    }
    return 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=82';
}

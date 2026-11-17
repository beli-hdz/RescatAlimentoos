<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$posts = mongo_rows($db->selectCollection('forum_posts')->find([], ['sort' => ['created_at' => -1]]));
$postIds = array_map('intval', array_column($posts, 'id'));
$replyRows = $postIds
    ? mongo_rows($db->selectCollection('forum_replies')->find(['post_id' => ['$in' => $postIds]], ['sort' => ['created_at' => 1, '_id' => 1]]))
    : [];
$authorIds = array_values(array_unique(array_map('intval', array_merge(array_column($posts, 'user_id'), array_column($replyRows, 'user_id')))));
$authors = [];
if ($authorIds) {
    foreach (mongo_rows($db->selectCollection('users')->find(['_id' => ['$in' => $authorIds]], ['projection' => ['username' => 1, 'role' => 1]])) as $u) {
        $authors[(int)$u['id']] = $u;
    }
}
$withAuthor = function (array $row) use ($authors): array {
    $author = $authors[(int)$row['user_id']] ?? [];
    $row['username'] = $author['username'] ?? 'usuario';
    $row['role'] = $author['role'] ?? 'user';
    return $row;
};
$posts = array_map($withAuthor, $posts);
$repliesByPost = [];
foreach ($replyRows as $reply) $repliesByPost[(int)$reply['post_id']][] = $withAuthor($reply);
$pageTitle = 'Foro de entregas'; require __DIR__ . '/includes/header.php';
$isAdminView = in_array(($_SESSION['role'] ?? 'user'), ['admin','super_admin'], true);
$types = ['community'=>'Actualización','status'=>'Seguimiento','delivered'=>'Donación entregada'];
?>
<section class="forum-heading"><span class="eyebrow">Comunidad RescatAlimentos</span><h1>Foro de entregas y novedades</h1><p>Comparte avances, responde a otros usuarios y coordina entregas.</p><?php if (logged_in()): ?><a class="btn teal" href="publicar_foro.php">Crear publicación</a><?php endif; ?></section>
<?php if (!$posts): ?><div class="empty">Aún no hay publicaciones. ¡Sé la primera persona en compartir una actualización!</div><?php endif; ?>
<div class="forum-list"><?php foreach ($posts as $p): $replies = $repliesByPost[(int)$p['id']] ?? []; ?>
<article class="card forum-post" id="publicacion-<?= (int)$p['id'] ?>"><div class="card-body">
<span class="tag"><?= e($types[$p['post_type']] ?? 'Actualización') ?></span><h2><?= e($p['title']) ?></h2><p><?= nl2br(e($p['body'])) ?></p>
<?php if (!empty($p['image_path']) && is_file(__DIR__ . '/' . $p['image_path'])): ?><img class="forum-image" src="<?= e($p['image_path']) ?>" alt="Imagen adjunta a la publicación"><?php endif; ?>
<small class="small">Publicado por <?= e($p['username']) ?> · <?= e($p['created_at']) ?></small>
<section class="forum-replies"><h3>Respuestas (<?= count($replies) ?>)</h3>
<?php foreach($replies as $r): ?><div class="forum-reply <?= in_array($r['role'],['admin','super_admin'],true)?'admin-reply':'' ?>">
<strong><?= in_array($r['role'],['admin','super_admin'],true)?'RescatAlimentos respondió':e($r['username']) ?></strong>
<p><?= nl2br(e($r['body'])) ?></p><small class="small"><?=e($r['created_at'])?></small></div><?php endforeach; ?>
<?php if(logged_in()): ?><form method="post" action="responder_foro.php" class="forum-reply-form"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="post_id" value="<?=(int)$p['id']?>"><label for="reply-<?= (int)$p['id'] ?>">Escribe una respuesta</label><textarea id="reply-<?= (int)$p['id'] ?>" name="body" maxlength="2000" required placeholder="Comparte tu respuesta..."></textarea><button class="btn teal" type="submit">Responder</button></form><?php else: ?><p class="small">Inicia sesión para responder a esta publicación.</p><?php endif; ?>
</section></div></article><?php endforeach; ?></div>
<?php require __DIR__ . '/includes/footer.php'; ?>

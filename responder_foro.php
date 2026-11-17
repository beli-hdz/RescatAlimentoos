<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: foro.php'); exit; }
verify_csrf();
$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$body = trim($_POST['body'] ?? '');
if (!$postId || $body === '' || mb_strlen($body) > 2000) { flash('Escribe una respuesta válida.','error'); header('Location: foro.php'); exit; }
$post = $db->selectCollection('forum_posts')->findOne(['_id' => (int)$postId], ['projection' => ['title' => 1]]);
if (!$post) { flash('La publicación ya no existe.','error'); header('Location: foro.php'); exit; }
$db->selectCollection('forum_replies')->insertOne([
    '_id' => next_id($db, 'forum_replies'),
    'post_id' => (int)$postId,
    'user_id' => (int)$_SESSION['user_id'],
    'body' => $body,
    'created_at' => mongo_now(),
]);
$role=$_SESSION['role']??'user';
$label=in_array($role,['admin','super_admin'],true)?'RescatAlimentos respondió':'Nueva respuesta en el foro';
notify_all_except($db, (int)$_SESSION['user_id'], $label, 'Hay una nueva respuesta en: '.$post['title'], 'foro.php#publicacion-'.$postId, 'forum');
flash('Tu respuesta se publicó correctamente.');
header('Location: foro.php#publicacion-'.$postId); exit;

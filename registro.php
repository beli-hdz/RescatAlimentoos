<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Crear cuenta'; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($name === '' || mb_strlen($name) > 100 || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
      !preg_match('/^[a-zA-Z0-9]{4,30}$/', $username) ||
      !preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9]{8,64}$/', $password)) {
    $error = 'Revisa los datos. Usuario: 4-30 letras o números. Contraseña: mínimo 8 caracteres, con letras y números, sin símbolos.';
  } else {
    $users = $db->selectCollection('users');
    $duplicated = $users->countDocuments(
      ['$or' => [['email' => $email], ['username' => $username]]],
      ['collation' => rescat_ci_collation()]
    ) > 0;
    if ($duplicated) {
      $error = 'El correo o nombre de usuario ya está registrado.';
    } else {
      try {
        $users->insertOne([
          '_id' => next_id($db, 'users'),
          'name' => $name,
          'email' => $email,
          'username' => $username,
          'password_hash' => password_hash($password, PASSWORD_DEFAULT),
          'role' => 'user',
          'created_at' => mongo_now(),
        ]);
        header('Location: login.php?registered=1'); exit;
      } catch (Throwable $ex) {
        if (!is_duplicate_key($ex)) throw $ex;
        $error = 'El correo o nombre de usuario ya está registrado.';
      }
    }
  }
}
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap"><aside class="auth-art"><a class="brand footer-brand" href="index.php"><span class="brand-mark"><img src="assets/img/icono-rescatalimentos-v3.svg" alt="" width="40" height="40"></span> Rescat<span>Alimentos</span></a><blockquote>Lo que hoy sobra en una mesa, mañana puede <em>llenar otra</em>.</blockquote><ul class="auth-points"><li>Publica donaciones en minutos</li><li>Entrega en puntos autorizados</li><li>Da seguimiento a cada alimento</li></ul></aside>
<section class="panel auth-panel"><h1>Crear tu cuenta</h1><p class="muted">Únete para gestionar tus pedidos y publicar donaciones.</p>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<form method="post" autocomplete="on">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="field"><label for="name">Nombre completo</label><input id="name" name="name" maxlength="100" required value="<?= e($_POST['name'] ?? '') ?>"></div>
<div class="field"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" maxlength="150" required value="<?= e($_POST['email'] ?? '') ?>"></div>
<div class="field"><label for="username">Nombre de usuario</label><input id="username" name="username" data-alphanumeric minlength="4" maxlength="30" pattern="[A-Za-z0-9]{4,30}" required value="<?= e($_POST['username'] ?? '') ?>"><small class="small">De 4 a 30 letras y números, sin espacios.</small></div>
<div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" minlength="8" maxlength="64" pattern="(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9]{8,64}" required><div class="pw-meter" id="pw-meter" data-level="0" aria-live="polite"><div class="pw-bars"><i></i><i></i><i></i><i></i></div><div class="pw-label"><span>Seguridad:</span><b id="pw-text">Escribe tu contraseña</b></div><ul class="pw-reqs"><li data-r="len">Mínimo 8 caracteres</li><li data-r="let">Al menos una letra</li><li data-r="num">Al menos un número</li><li data-r="long">12 o más caracteres (más segura)</li><li data-r="mix">Mayúsculas y minúsculas</li></ul></div></div>
<button class="btn teal" type="submit">Crear cuenta</button>
</form><p class="muted">¿Ya tienes cuenta? <a href="login.php"><strong>Inicia sesión</strong></a></p></section></div>
<script>
(function(){
  var i=document.getElementById('password'),m=document.getElementById('pw-meter'),t=document.getElementById('pw-text');
  if(!i||!m)return;
  var names=['Escribe tu contraseña','Baja','Media','Segura','Muy segura'];
  function upd(){
    var v=i.value,r={len:v.length>=8,let:/[A-Za-z]/.test(v),num:/\d/.test(v),long:v.length>=12,mix:/[a-z]/.test(v)&&/[A-Z]/.test(v)};
    Object.keys(r).forEach(function(k){var li=m.querySelector('[data-r="'+k+'"]');if(li)li.classList.toggle('ok',r[k]);});
    var lvl=0;
    if(v.length){
      var base=r.len&&r.let&&r.num;
      lvl=1;
      if(base)lvl=2;
      if(base&&(r.long||r.mix))lvl=3;
      if(base&&r.long&&r.mix)lvl=4;
    }
    m.dataset.level=lvl;t.textContent=names[lvl];
  }
  i.addEventListener('input',upd);upd();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

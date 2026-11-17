<?php require_once __DIR__ . '/functions.php'; require_once __DIR__ . '/../config/database.php'; ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>RescatAlimentos</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/mejoras-visuales.css">
  <link rel="stylesheet" href="assets/css/mejoras-v6.css?v=6">
</head>
<body class="<?= in_array(($_SESSION['role'] ?? 'user'), ['admin','super_admin'], true) ? 'admin-dashboard-page' : '' ?> page-<?= e(basename($_SERVER['PHP_SELF'] ?? '', '.php')) ?>">
<?php $currentPage = basename($_SERVER['PHP_SELF'] ?? ''); $publicPages = ['index.php','donaciones.php','foro.php','juego.php','publicar_donacion.php','mis_pedidos.php','centros.php','publicar_foro.php','login.php','registro.php']; ?>
<?php if (!in_array(($_SESSION['role'] ?? 'user'), ['admin','super_admin'], true) || in_array($currentPage, $publicPages, true)): ?>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.5 2 8 0 5.5-4.8 10-10 10z"/><path d="M2 21c0-3 1.9-5.5 6-8"/></svg></span> Rescat<span>Alimentos</span></a>
  <button class="mobile-menu-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" data-menu-toggle><span></span><span></span><span></span><b>Menú</b></button>
  <nav id="main-navigation" aria-label="Menú principal">
    <a href="index.php"<?= $currentPage==='index.php' ? ' aria-current="page"' : '' ?>>Inicio</a>
    <?php if (logged_in()): ?><a href="donaciones.php"<?= $currentPage==='donaciones.php' ? ' aria-current="page"' : '' ?>>Mis donaciones</a><?php endif; ?>
    <a href="foro.php"<?= $currentPage==='foro.php' ? ' aria-current="page"' : '' ?>>Foro</a>
    <a href="juego.php"<?= $currentPage==='juego.php' ? ' aria-current="page"' : '' ?>>Juego</a>
    <a href="centros.php"<?= $currentPage==='centros.php' ? ' aria-current="page"' : '' ?>>Centros de donación</a>
    <?php if (logged_in()): ?>
      <?php $unreadCount = (int)$db->selectCollection('notifications')->countDocuments(['user_id' => (int)$_SESSION['user_id'], 'is_read' => false]); ?>
      <a href="notificaciones.php"<?= $currentPage==='notificaciones.php' ? ' aria-current="page"' : '' ?>>Notificaciones<?= $unreadCount ? ' (' . $unreadCount . ')' : '' ?></a>
      <a href="publicar_donacion.php"<?= $currentPage==='publicar_donacion.php' ? ' aria-current="page"' : '' ?>>Donar comida</a>
      <a href="mis_pedidos.php"<?= $currentPage==='mis_pedidos.php' ? ' aria-current="page"' : '' ?>>Mi cuenta</a>
      <?php if (in_array(($_SESSION['role'] ?? 'user'), ['admin','super_admin'], true)): ?><a href="admin.php"<?= $currentPage==='admin.php' ? ' aria-current="page"' : '' ?>>Dashboard</a><?php endif; ?>
      <a class="nav-cta" href="logout.php">Salir</a>
    <?php else: ?>
      <a href="login.php"<?= $currentPage==='login.php' ? ' aria-current="page"' : '' ?>>Iniciar sesión</a>
      <a class="nav-cta" href="registro.php">Crear cuenta</a>
    <?php endif; ?>
    <button class="theme-toggle" type="button" data-theme-toggle aria-label="Cambiar tema" title="Cambiar modo claro u oscuro">◐ <span>Modo</span></button>
  </nav>
</header>
<?php endif; ?>
<?php if (in_array(($_SESSION['role'] ?? 'user'), ['admin','super_admin'], true) && !in_array($currentPage, $publicPages, true)): ?><button class="theme-toggle admin-theme-toggle" type="button" data-theme-toggle aria-label="Cambiar tema" title="Cambiar modo claro u oscuro">◐ Modo</button><?php endif; ?>
<main class="container">
<?php show_flash(); ?>

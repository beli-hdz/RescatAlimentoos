<?php
$adminActive = $adminActive ?? 'dashboard';
$adminIsSuper = is_super_admin();
?>
<aside class="dashboard-sidebar"><div class="sidebar-inner">
  <a class="sidebar-brand" href="admin.php"><span class="brand-mark"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.5 2 8 0 5.5-4.8 10-10 10z"/><path d="M2 21c0-3 1.9-5.5 6-8"/></svg></span><span>Rescat<span>Alimentos</span></span></a>
  <div class="sidebar-profile">
    <div class="profile-avatar" aria-hidden="true"><span><?= strtoupper(substr(($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'A'), 0, 1)) ?></span></div>
    <strong><?= e($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Administrador') ?></strong>
    <small><?= $adminIsSuper ? 'Superadministrador' : 'Administrador' ?></small>
  </div>
  <div class="sidebar-caption">MENÚ PRINCIPAL</div>
  <a class="sidebar-link <?= $adminActive==='dashboard'?'active':'' ?>" href="admin.php"><span class="side-symbol"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/></svg></span> Dashboard</a>
  <a class="sidebar-link <?= $adminActive==='inventory'?'active':'' ?>" href="inventario_donaciones.php"><span class="side-symbol"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg></span> Inventario</a>
  <?php $adminUnreadCount = (int)$db->selectCollection('notifications')->countDocuments(['user_id' => (int)$_SESSION['user_id'], 'is_read' => false]); ?>
  <a class="sidebar-link <?= $adminActive==='notifications'?'active':'' ?>" href="notificaciones.php"><span class="side-symbol"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg></span> Notificaciones<?= $adminUnreadCount ? ' (' . $adminUnreadCount . ')' : '' ?></a>
  <a class="sidebar-link <?= $adminActive==='assign'?'active':'' ?>" href="asignar_centros.php"><span class="side-symbol"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0116 0z"/><circle cx="12" cy="10" r="3"/></svg></span> Asignar centros</a>
  <?php if ($adminIsSuper): ?>
  <a class="sidebar-link <?= $adminActive==='centers'?'active':'' ?>" href="administrar_centros.php"><span class="side-symbol"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0116 0z"/><circle cx="12" cy="10" r="3"/></svg></span> Centros y puntos</a>
  <a class="sidebar-link <?= $adminActive==='users'?'active':'' ?>" href="usuarios_roles.php"><span class="side-symbol"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M21 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8"/></svg></span> Usuarios y roles</a>
  <?php endif; ?>
  <div class="sidebar-bottom"><a class="sidebar-link" href="index.php"><span class="side-symbol"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></svg></span> Ver sitio web</a><a class="sidebar-link" href="logout.php"><span class="side-symbol"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg></span> Cerrar sesión</a></div>
</div></aside>

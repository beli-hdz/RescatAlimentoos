<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $centerId = filter_input(INPUT_POST, 'center_id', FILTER_VALIDATE_INT);
    if ($id) {
        $donationOk = $db->selectCollection('donations')->countDocuments(['_id' => (int)$id, 'status' => ['$ne' => 'cancelled']]) > 0;
        $centerOk = $centerId
            ? $db->selectCollection('centers')->countDocuments(['_id' => (int)$centerId, 'active' => true, 'center_type' => 'center']) > 0
            : false;
        if ($donationOk && $centerOk) {
            $db->selectCollection('donations')->updateOne(['_id' => (int)$id], ['$set' => ['center_id' => (int)$centerId]]);
            flash('Producto asignado al centro correctamente.');
        } else {
            flash('No se pudo realizar la asignación. Verifica el estado del producto y que el centro esté activo.', 'error');
        }
    }
    header('Location: asignar_centros.php');
    exit;
}
$filter = $_GET['assignment'] ?? 'all';
if (!in_array($filter, ['all','unassigned','assigned'], true)) $filter='unassigned';
$status = $_GET['status'] ?? 'assignable';
if (!in_array($status, ['assignable','all','available','in_process','picked_up','delivered','completed'], true)) $status='assignable';
$search = trim($_GET['search'] ?? '');
$conditions = [];
if ($status==='assignable') $conditions[] = ['status' => ['$ne' => 'cancelled']];
elseif ($status!=='all') $conditions[] = ['status' => $status];
if ($filter==='unassigned') $conditions[] = ['center_id' => null];
if ($filter==='assigned') $conditions[] = ['center_id' => ['$ne' => null]];
if ($search!=='') {
    $regex = mongo_regex($search);
    $conditions[] = ['$or' => [['food_name' => $regex], ['category' => $regex], ['location' => $regex]]];
}
$products = mongo_rows($db->selectCollection('donations')->find(
    $conditions ? ['$and' => $conditions] : [],
    ['sort' => ['created_at' => -1], 'limit' => 100]
));
$centerNames = [];
foreach (mongo_rows($db->selectCollection('centers')->find([], ['projection' => ['name' => 1]])) as $c) $centerNames[(int)$c['id']] = $c['name'];
$products = array_map(function (array $p) use ($centerNames): array {
    $p['center_name'] = !empty($p['center_id']) ? ($centerNames[(int)$p['center_id']] ?? null) : null;
    return $p;
}, $products);
$centers = mongo_rows($db->selectCollection('centers')->find(
    ['active' => true, 'center_type' => 'center'],
    ['sort' => ['name' => 1], 'collation' => rescat_sort_collation(), 'projection' => ['name' => 1]]
));
$pageTitle='Asignar centros'; require __DIR__.'/includes/header.php';
?>
<div class="dashboard-shell admin-page-shell">
<?php $adminActive='assign'; require __DIR__.'/includes/admin_sidebar.php'; ?>
<div class="dashboard-main admin-content-main">
<section class="admin-heading"><div><span class="eyebrow">DISTRIBUCIÓN DE ALIMENTOS</span><h1>Asignar centros</h1><p class="muted">Selecciona el centro de destino de cada donación. Puedes cambiar la asignación mientras el registro no esté cancelado.</p></div><a class="btn light" href="admin.php">Dashboard</a></section>
<div class="inventory-summary"><div><strong><?=count($products)?></strong><span>Productos encontrados</span></div><div><strong><?=count(array_filter($products,fn($p)=>empty($p['center_id'])))?></strong><span>Sin asignar</span></div><div><strong><?=count($centers)?></strong><span>Centros activos</span></div></div>
<form method="get" class="filter-panel inventory-filters">
<div class="field search-field"><label for="search">Buscar producto</label><input id="search" type="search" name="search" value="<?=e($search)?>" placeholder="Nombre, categoría o ubicación"></div>
<div class="field"><label for="assignment">Asignación</label><select id="assignment" name="assignment"><option value="unassigned" <?=$filter==='unassigned'?'selected':''?>>Sin asignar</option><option value="assigned" <?=$filter==='assigned'?'selected':''?>>Asignados</option><option value="all" <?=$filter==='all'?'selected':''?>>Todos</option></select></div>
<div class="field"><label for="status">Estado</label><select id="status" name="status"><option value="assignable" <?=$status==='assignable'?'selected':''?>>Todas las donaciones asignables</option><option value="all" <?=$status==='all'?'selected':''?>>Todos los estados</option><option value="available" <?=$status==='available'?'selected':''?>>Solo disponibles</option><option value="in_process" <?=$status==='in_process'?'selected':''?>>En proceso</option><option value="picked_up" <?=$status==='picked_up'?'selected':''?>>Recogidas</option><option value="delivered" <?=$status==='delivered'?'selected':''?>>Entregadas</option><option value="completed" <?=$status==='completed'?'selected':''?>>Completadas</option></select></div>
<div class="filter-actions"><button class="btn teal" type="submit">Aplicar filtros</button><a class="btn light" href="asignar_centros.php">Limpiar</a></div>
</form>
<div class="admin-table-card"><div class="table-wrap"><table class="admin-data-table assign-data-table"><thead><tr><th>Producto</th><th>Cantidad / categoría</th><th>Ubicación</th><th>Estado</th><th>Centro de donaciones</th><th>Asignar / cambiar centro</th></tr></thead><tbody>
<?php if(!$products): ?><tr><td colspan="6"><div class="inventory-no-results"><strong>No hay productos que coincidan</strong><span>Prueba con otros filtros o revisa el inventario.</span></div></td></tr><?php endif; ?>
<?php foreach($products as $product): ?><tr><td><b><?=e($product['food_name'])?></b></td><td><?=e($product['quantity'])?><small><?=e($product['category'])?></small></td><td><?=e($product['location'])?></td><td><span class="status-pill <?=in_array($product['status'],['available','in_process'],true)?'is-available':'is-unavailable'?>"><?=e(['available'=>'Disponible','in_process'=>'En proceso','picked_up'=>'Pedido recogido','delivered'=>'Entregado','completed'=>'Completado','cancelled'=>'Cancelado'][$product['status']] ?? $product['status'])?></span></td><td><?=e($product['center_name']?:'Sin asignar')?></td><td>
<?php if($centers): ?><form method="post" class="assign-center-form"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=(int)$product['id']?>"><select name="center_id" required aria-label="Centro para <?=e($product['food_name'])?>"><option value="">Seleccionar centro</option><?php foreach($centers as $center): ?><option value="<?=(int)$center['id']?>" <?=(int)$product['center_id']===(int)$center['id']?'selected':''?>><?=e($center['name'])?></option><?php endforeach; ?></select><button class="btn teal" type="submit">Guardar</button></form><?php else: ?><span class="muted">No hay centros de donaciones activos</span><?php endif; ?>
</td></tr><?php endforeach; ?>
</tbody></table></div></div>
</div></div>
<?php require __DIR__.'/includes/footer.php'; ?>

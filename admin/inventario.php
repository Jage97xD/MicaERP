<?php
require_once "../config/db.php";
require_once "layout.php";

$buscar = $_GET['buscar'] ?? '';

$sql = "
SELECT 
    p.*,
    c.nombre AS categoria_nombre,
    m.nombre AS marca_nombre
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN marcas m ON p.marca_id = m.id
WHERE COALESCE(p.tipo_item,'producto') <> 'servicio'
";
$sql .= erp_scope_sql_producto($pdo, 'p.categoria_id');

$params = [];

if($buscar !== ''){
    $sql .= " AND (p.nombre LIKE :buscar OR p.codigo LIKE :buscar OR p.sku LIKE :buscar OR m.nombre LIKE :buscar)";
    $params[':buscar'] = "%$buscar%";
}

$sql .= " ORDER BY p.stock ASC, p.nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scopeInv = erp_scope_sql_producto($pdo, 'categoria_id');
$totalProductos = $pdo->query("SELECT COUNT(*) FROM productos WHERE COALESCE(tipo_item,'producto') <> 'servicio' $scopeInv")->fetchColumn();
$stockBajo = $pdo->query("SELECT COUNT(*) FROM productos WHERE COALESCE(tipo_item,'producto') <> 'servicio' AND stock <= stock_minimo AND activo = 1 $scopeInv")->fetchColumn();
$valorInventario = $pdo->query("SELECT COALESCE(SUM(stock * costo),0) FROM productos WHERE COALESCE(tipo_item,'producto') <> 'servicio' $scopeInv")->fetchColumn();
$totalStock = $pdo->query("SELECT COALESCE(SUM(stock),0) FROM productos WHERE COALESCE(tipo_item,'producto') <> 'servicio' $scopeInv")->fetchColumn();

admin_header("Inventario", "inventario");
?>

<style>
.filter-box{display:grid;grid-template-columns:1fr 140px;gap:12px;margin-bottom:20px}.filter-box input{padding:12px;border:1px solid #e5e7eb;border-radius:10px;font-size:15px}.stock-low{color:#dc2626;font-weight:bold}.stock-ok{color:#16a34a;font-weight:bold}.stock-zero{color:#991b1b;font-weight:bold}.inv-actions{display:flex;gap:8px;flex-wrap:wrap}.inv-state{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900}.inv-state.ok{background:#dcfce7;color:#166534}.inv-state.low{background:#fef3c7;color:#92400e}.inv-state.zero{background:#fee2e2;color:#991b1b}@media(max-width:800px){.filter-box{grid-template-columns:1fr}}
</style>

<div class="cards">
    <div class="card"><small>Productos con inventario</small><strong><?= $totalProductos ?></strong></div>
    <div class="card"><small>Unidades en stock</small><strong><?= $totalStock ?></strong></div>
    <div class="card"><small>Stock bajo</small><strong><?= $stockBajo ?></strong></div>
    <div class="card"><small>Valor inventario</small><strong>S/ <?= number_format($valorInventario, 2) ?></strong></div>
</div>

<div class="panel">
    <div class="panel-header"><div><h3>Control de inventario</h3><p style="margin:4px 0 0;color:#64748b;">Los servicios no aparecen aquí porque no manejan stock. Alcance: <?= htmlspecialchars(alcanceResumen()) ?></p></div></div>
    <form class="filter-box" method="GET"><input name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar por producto, código, SKU o marca"><button class="btn" type="submit">Buscar</button></form>
    <table class="table"><thead><tr><th>ID</th><th>Producto</th><th>Categoría</th><th>Marca</th><th>Costo</th><th>Stock mínimo</th><th>Stock actual</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach($productos as $p): ?>
    <?php $stock=(int)($p['stock']??0); $minimo=(int)($p['stock_minimo']??0); if($stock<=0){$estadoClase='zero';$estadoTexto='🔴 Sin stock';$stockClase='stock-zero';}elseif($stock<=$minimo){$estadoClase='low';$estadoTexto='🟡 Stock bajo';$stockClase='stock-low';}else{$estadoClase='ok';$estadoTexto='🟢 Normal';$stockClase='stock-ok';} ?>
    <tr><td><?= (int)$p['id'] ?></td><td><strong><?= htmlspecialchars($p['nombre']) ?></strong><br><small>Código: <?= htmlspecialchars($p['codigo'] ?? '-') ?> | SKU: <?= htmlspecialchars($p['sku'] ?? '-') ?></small></td><td><?= htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría') ?></td><td><?= htmlspecialchars($p['marca_nombre'] ?? 'Sin marca') ?></td><td>S/ <?= number_format((float)($p['costo'] ?? 0), 2) ?></td><td><?= $minimo ?></td><td class="<?= $stockClase ?>"><?= $stock ?></td><td><span class="inv-state <?= $estadoClase ?>"><?= $estadoTexto ?></span></td><td class="inv-actions"><a class="btn green" href="inventario_movimiento.php?producto_id=<?= (int)$p['id'] ?>&tipo=Entrada">Entrada</a><a class="btn red" href="inventario_movimiento.php?producto_id=<?= (int)$p['id'] ?>&tipo=Salida">Salida</a><a class="btn gray" href="inventario_movimiento.php?producto_id=<?= (int)$p['id'] ?>&tipo=Ajuste">Ajuste</a><a class="btn" href="inventario_historial.php?producto_id=<?= (int)$p['id'] ?>">Historial</a></td></tr>
    <?php endforeach; ?>
    <?php if(count($productos) === 0): ?><tr><td colspan="9">No hay productos con inventario registrados.</td></tr><?php endif; ?>
    </tbody></table>
</div>
<?php admin_footer(); ?>
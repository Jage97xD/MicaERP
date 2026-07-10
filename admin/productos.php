<?php
require_once "../config/db.php";
require_once "layout.php";

$buscar = $_GET['buscar'] ?? '';
$empresaFiltro = (int)($_GET['empresa_id'] ?? 0);

$sql = "
SELECT p.*, c.nombre AS categoria_nombre, m.nombre AS marca_nombre, t.nombre AS tienda_nombre, e.nombre AS empresa_nombre
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN marcas m ON p.marca_id = m.id
LEFT JOIN marketplace_tiendas t ON t.id = p.tienda_id
LEFT JOIN marketplace_empresas e ON e.id = t.empresa_id
WHERE 1=1
";
$sql .= erp_scope_sql_tienda_producto($pdo, 'p');

$params = [];

if($empresaFiltro > 0){
    $sql .= " AND t.empresa_id = :empresa_id";
    $params[':empresa_id'] = $empresaFiltro;
}

if($buscar !== ''){
    $sql .= " AND (p.nombre LIKE :buscar OR p.codigo LIKE :buscar OR p.sku LIKE :buscar OR m.nombre LIKE :buscar)";
    $params[':buscar'] = "%$buscar%";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header("Productos", "productos");
?>

<style>
.filter-box{
    display:grid;
    grid-template-columns:1fr 220px 150px;
    gap:12px;
    margin-bottom:20px;
}
.filter-box input,.filter-box select{
    padding:12px;
    border:1px solid #e5e7eb;
    border-radius:10px;
    font-size:15px;
}
.product-img{
    width:58px;
    height:48px;
    object-fit:cover;
    border-radius:8px;
    background:#eef2ff;
}
.stock-low{
    color:#dc2626;
    font-weight:bold;
}
.stock-ok{
    color:#16a34a;
    font-weight:bold;
}
</style>

<div class="panel">
    <div class="panel-header">
        <div><h3>Listado de productos</h3><p style="margin:4px 0 0;color:#64748b;">Alcance: <?= htmlspecialchars(alcanceResumen()) ?></p></div>
        <?php if(rolPuede('productos','crear')): ?><a class="btn" href="producto_nuevo.php">+ Nuevo producto</a><?php endif; ?>
    </div>

    <?php $empresasFiltroProd = $pdo->query("SELECT id,nombre FROM marketplace_empresas WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC); ?>
    <form class="filter-box" method="GET">
        <input name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar por producto, código, SKU o marca">
        <select name="empresa_id">
            <option value="0">Todas las empresas</option>
            <?php foreach($empresasFiltroProd as $ef): ?>
                <option value="<?= (int)$ef['id'] ?>" <?= $empresaFiltro===(int)$ef['id']?'selected':'' ?>><?= htmlspecialchars($ef['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Buscar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Img</th>
                <th>ID</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Marca</th><th>Empresa</th><th>Tienda</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($productos as $p): ?>
            <tr>
                <td>
                    <?php if($p['imagen_principal']): ?>
                        <img class="product-img" src="../<?= htmlspecialchars($p['imagen_principal']) ?>">
                    <?php else: ?>
                        <div class="product-img"></div>
                    <?php endif; ?>
                </td>
                <td><?= $p['id'] ?></td>
                <td>
                    <strong><?= htmlspecialchars($p['nombre']) ?></strong><br>
                    <small>Código: <?= htmlspecialchars($p['codigo'] ?? '-') ?> | SKU: <?= htmlspecialchars($p['sku'] ?? '-') ?></small>
                </td>
                <td><?= htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría') ?></td>
                <td><?= htmlspecialchars($p['marca_nombre'] ?? 'Sin marca') ?></td><td><?= htmlspecialchars($p['empresa_nombre'] ?? '-') ?></td><td><?= htmlspecialchars($p['tienda_nombre'] ?? 'Marketplace') ?></td>
                <td>
                    S/ <?= number_format($p['precio'], 2) ?>
                    <?php if($p['precio_oferta'] > 0): ?>
                        <br><small>Oferta: S/ <?= number_format($p['precio_oferta'], 2) ?></small>
                    <?php endif; ?>
                </td>
                <td class="<?= $p['stock'] <= $p['stock_minimo'] ? 'stock-low' : 'stock-ok' ?>">
                    <?= $p['stock'] ?>
                </td>
                <td>
                    <span class="badge <?= $p['activo'] ? 'ok' : 'warn' ?>">
                        <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td class="actions">
                    <a class="btn gray" href="producto_editar.php?id=<?= $p['id'] ?>">Editar</a>

                    <?php if($p['activo']): ?>
                        <a class="btn red" href="producto_desactivar.php?id=<?= $p['id'] ?>" onclick="return confirm('¿Desactivar producto?')">Desactivar</a>
                    <?php else: ?>
                        <a class="btn green" href="producto_activar.php?id=<?= $p['id'] ?>">Activar</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($productos) == 0): ?>
            <tr><td colspan="10">No hay productos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_footer(); ?>
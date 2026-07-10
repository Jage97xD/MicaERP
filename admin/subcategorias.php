<?php
require_once "../config/db.php";
require_once "layout.php";

$buscar = $_GET['buscar'] ?? '';

$sql = "SELECT s.*, c.nombre AS categoria_nombre, COUNT(p.id) AS total_productos
        FROM subcategorias s
        LEFT JOIN categorias c ON s.categoria_id = c.id
        LEFT JOIN productos p ON p.subcategoria_id = s.id
        WHERE 1=1";
$sql .= erp_scope_sql_producto($pdo, 'c.id');
$params = [];

if($buscar !== ''){
    $sql .= " AND (s.nombre LIKE :buscar OR s.slug LIKE :buscar OR c.nombre LIKE :buscar)";
    $params[':buscar'] = "%$buscar%";
}

$sql .= " GROUP BY s.id ORDER BY s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subcategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header("Subcategorías", "subcategorias");
?>

<style>
.filter-box{display:grid;grid-template-columns:1fr 130px;gap:12px;margin-bottom:20px}
.filter-box input{padding:12px;border:1px solid #e5e7eb;border-radius:10px;font-size:15px}
</style>

<div class="panel">
    <div class="panel-header">
        <h3>Listado de subcategorías</h3>
        <a class="btn" href="subcategoria_form.php">+ Nueva subcategoría</a>
    </div>

    <form class="filter-box" method="GET">
        <input name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar subcategoría o categoría">
        <button class="btn" type="submit">Buscar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Subcategoría</th>
                <th>Categoría padre</th>
                <th>Slug</th>
                <th>Productos</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($subcategorias as $s): ?>
            <tr>
                <td><?= $s['id'] ?></td>
                <td><strong><?= htmlspecialchars($s['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($s['categoria_nombre'] ?? 'Sin categoría') ?></td>
                <td><?= htmlspecialchars($s['slug']) ?></td>
                <td><?= $s['total_productos'] ?></td>
                <td><span class="badge <?= $s['activo'] ? 'ok' : 'warn' ?>"><?= $s['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="actions">
                    <a class="btn gray" href="subcategoria_form.php?id=<?= $s['id'] ?>">Editar</a>
                    <a class="btn <?= $s['activo'] ? 'red' : 'green' ?>" href="subcategoria_toggle.php?id=<?= $s['id'] ?>">
                        <?= $s['activo'] ? 'Desactivar' : 'Activar' ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($subcategorias) === 0): ?>
            <tr><td colspan="7">No hay subcategorías registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_footer(); ?>
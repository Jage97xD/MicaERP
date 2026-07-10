<?php
require_once "../config/db.php";
require_once "layout.php";

$buscar = $_GET['buscar'] ?? '';

$sql = "SELECT c.*, COUNT(s.id) AS total_subcategorias
        FROM categorias c
        LEFT JOIN subcategorias s ON s.categoria_id = c.id
        WHERE 1=1
        ";
$params = [];

if($buscar !== ''){
    $sql .= " AND (c.nombre LIKE :buscar OR c.slug LIKE :buscar)";
    $params[':buscar'] = "%$buscar%";
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header("Categorías", "categorias");
?>

<style>
.filter-box{display:grid;grid-template-columns:1fr 130px;gap:12px;margin-bottom:20px}
.filter-box input{padding:12px;border:1px solid #e5e7eb;border-radius:10px;font-size:15px}
.color-dot{width:28px;height:28px;border-radius:50%;display:inline-block;border:1px solid #ddd}
</style>

<div class="panel">
    <div class="panel-header">
        <h3>Listado de categorías</h3>
        <a class="btn" href="categoria_form.php">+ Nueva categoría</a>
    </div>

    <form class="filter-box" method="GET">
        <input name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar categoría">
        <button class="btn" type="submit">Buscar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Icono</th>
                <th>Nombre</th>
                <th>Slug</th>
                <th>Color</th>
                <th>Subcategorías</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($categorias as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['icono'] ?? '') ?></td>
                <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($c['slug']) ?></td>
                <td><span class="color-dot" style="background:<?= htmlspecialchars($c['color'] ?? '#2563eb') ?>"></span></td>
                <td><?= $c['total_subcategorias'] ?></td>
                <td><span class="badge <?= $c['activo'] ? 'ok' : 'warn' ?>"><?= $c['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="actions">
                    <a class="btn gray" href="categoria_form.php?id=<?= $c['id'] ?>">Editar</a>
                    <a class="btn <?= $c['activo'] ? 'red' : 'green' ?>" href="categoria_toggle.php?id=<?= $c['id'] ?>">
                        <?= $c['activo'] ? 'Desactivar' : 'Activar' ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($categorias) === 0): ?>
            <tr><td colspan="8">No hay categorías registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_footer(); ?>
<?php
require_once "../config/db.php";
require_once "layout.php";

$buscar = $_GET['buscar'] ?? '';

$sql = "SELECT m.*, COUNT(p.id) AS total_productos
        FROM marcas m
        LEFT JOIN productos p ON p.marca_id = m.id
        WHERE 1=1";
$sql .= erp_scope_sql_producto($pdo, 'p.categoria_id');
$params = [];

if($buscar !== ''){
    $sql .= " AND (m.nombre LIKE :buscar OR m.slug LIKE :buscar)";
    $params[':buscar'] = "%$buscar%";
}

$sql .= " GROUP BY m.id ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);

admin_header("Marcas", "marcas");
?>

<style>
.filter-box{display:grid;grid-template-columns:1fr 130px;gap:12px;margin-bottom:20px}
.filter-box input{padding:12px;border:1px solid #e5e7eb;border-radius:10px;font-size:15px}
</style>

<div class="panel">
    <div class="panel-header">
        <h3>Listado de marcas</h3>
        <a class="btn" href="marca_form.php">+ Nueva marca</a>
    </div>

    <form class="filter-box" method="GET">
        <input name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar marca">
        <button class="btn" type="submit">Buscar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Marca</th>
                <th>Slug</th>
                <th>Productos</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($marcas as $m): ?>
            <tr>
                <td><?= $m['id'] ?></td>
                <td><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($m['slug']) ?></td>
                <td><?= $m['total_productos'] ?></td>
                <td><span class="badge <?= $m['activo'] ? 'ok' : 'warn' ?>"><?= $m['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="actions">
                    <a class="btn gray" href="marca_form.php?id=<?= $m['id'] ?>">Editar</a>
                    <a class="btn <?= $m['activo'] ? 'red' : 'green' ?>" href="marca_toggle.php?id=<?= $m['id'] ?>">
                        <?= $m['activo'] ? 'Desactivar' : 'Activar' ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($marcas) === 0): ?>
            <tr><td colspan="6">No hay marcas registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_footer(); ?>
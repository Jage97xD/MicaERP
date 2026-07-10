<?php
require_once "config/db.php";
require_once "config/erp_core.php";
require_once "includes/v3/common.php";
require_once "includes/v3/empresa_context.php";

$buscar = $_GET['buscar'] ?? '';
$categoria = '';

$categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$sql = "
SELECT
    p.*,
    c.nombre AS categoria,
    c.slug AS categoria_slug,
    s.nombre AS subcategoria,
    m.nombre AS marca
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN subcategorias s ON p.subcategoria_id = s.id
LEFT JOIN marcas m ON p.marca_id = m.id
WHERE p.activo = 1
AND p.oferta = 1
";

$params = [];

if($buscar !== ''){
    $sql .= " AND (
        p.nombre LIKE :buscar
        OR p.descripcion_corta LIKE :buscar
        OR m.nombre LIKE :buscar
        OR p.codigo LIKE :buscar
        OR p.sku LIKE :buscar
    )";
    $params[':buscar'] = "%$buscar%";
}

$sql .= " ORDER BY p.id DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ofertas - Mica Store V3</title>
<link rel="stylesheet" href="includes/v3/store_v3.css">
<link rel="stylesheet" href="includes/v3/login_modal.css">
<link rel="stylesheet" href="includes/v3/header_cliente.css">
</head>
<body>

<?php require "includes/v3/topbar.php"; ?>
<?php require "includes/v3/header.php"; ?>
<?php require "includes/v3/menu.php"; ?>

<main class="v3-container" style="padding-top:35px;">
    <div class="v3-section-head" id="productos">
        <h2>⭐ Ofertas</h2>
        <span><?= count($productos) ?> producto(s)</span>
    </div>

    <form class="v3-filters" method="GET">
        <input name="buscar" value="<?= h($buscar) ?>" placeholder="Buscar oferta">
        <select disabled>
            <option>Solo productos en oferta</option>
        </select>
        <button>Buscar</button>
    </form>

    <section class="v3-product-grid">
        <?php foreach($productos as $p): ?>
            <?= productoCardV3($p) ?>
        <?php endforeach; ?>

        <?php if(count($productos) === 0): ?>
            <p>No hay ofertas registradas todavía.</p>
        <?php endif; ?>
    </section>
</main>

<?php require "includes/v3/footer.php"; ?>
<?php require "includes/v3/login_modal.php"; ?>

<?php $waFlotanteOfertas = preg_replace('/\D/','', erp_config_empresa($pdo, $empresaId ?? 0)['whatsapp'] ?? '') ?: '51920137707'; ?>
<a class="v3-float-chat" target="_blank" href="https://wa.me/<?= h($waFlotanteOfertas) ?>">💬</a>

<script>window.MICA_CATEGORIA_ACTUAL = "";</script>
<script src="includes/v3/store_v3.js"></script>
<script src="includes/v3/login_modal.js"></script>
<script src="includes/v3/header_cliente.js"></script>

</body>
</html>

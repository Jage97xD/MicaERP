<?php
require_once "config/db.php";
require_once "config/erp_core.php";
require_once "includes/v3/common.php";
require_once "includes/v3/empresa_context.php";

$marcaId = isset($_GET['marca_id']) ? (int)$_GET['marca_id'] : 0;
$categoria = '';
$buscar = $_GET['buscar'] ?? '';

$categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $pdo->query("SELECT * FROM marcas WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

$productos = [];
$titulo = "Marcas";

if($marcaId > 0){
    $stmtMarca = $pdo->prepare("SELECT * FROM marcas WHERE id = ? LIMIT 1");
    $stmtMarca->execute([$marcaId]);
    $marca = $stmtMarca->fetch(PDO::FETCH_ASSOC);
    if($marca){
        $titulo = "Marca: " . $marca['nombre'];
    }

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
    AND p.marca_id = :marca_id
    ";

    $params = [':marca_id' => $marcaId];

    if($buscar !== ''){
        $sql .= " AND (
            p.nombre LIKE :buscar
            OR p.descripcion_corta LIKE :buscar
            OR p.codigo LIKE :buscar
            OR p.sku LIKE :buscar
        )";
        $params[':buscar'] = "%$buscar%";
    }

    $sql .= " ORDER BY p.id DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= h($titulo) ?> - Mica Store V3</title>
<link rel="stylesheet" href="includes/v3/store_v3.css">
<link rel="stylesheet" href="includes/v3/login_modal.css">
<link rel="stylesheet" href="includes/v3/header_cliente.css">
<style>
.brand-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:20px}
.brand-card{background:white;border-radius:18px;padding:22px;box-shadow:0 8px 22px rgba(15,23,42,.08);font-weight:900;text-align:center}
.brand-card:hover{background:#eef6ff;color:#0057d9}
@media(max-width:900px){.brand-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.brand-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<?php require "includes/v3/topbar.php"; ?>
<?php require "includes/v3/header.php"; ?>
<?php require "includes/v3/menu.php"; ?>

<main class="v3-container" style="padding-top:35px;">
    <div class="v3-section-head">
        <h2>🏷 <?= h($titulo) ?></h2>
        <?php if($marcaId > 0): ?>
            <a href="marcas.php">Ver todas las marcas</a>
        <?php endif; ?>
    </div>

    <?php if($marcaId === 0): ?>
        <section class="brand-grid">
            <?php foreach($marcas as $m): ?>
                <a class="brand-card" href="marcas.php?marca_id=<?= (int)$m['id'] ?>">
                    🏷 <?= h($m['nombre']) ?>
                </a>
            <?php endforeach; ?>

            <?php if(count($marcas) === 0): ?>
                <p>No hay marcas registradas todavía.</p>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <form class="v3-filters" method="GET">
            <input type="hidden" name="marca_id" value="<?= (int)$marcaId ?>">
            <input name="buscar" value="<?= h($buscar) ?>" placeholder="Buscar dentro de esta marca">
            <select disabled>
                <option><?= h($titulo) ?></option>
            </select>
            <button>Buscar</button>
        </form>

        <section class="v3-product-grid">
            <?php foreach($productos as $p): ?>
                <?= productoCardV3($p) ?>
            <?php endforeach; ?>

            <?php if(count($productos) === 0): ?>
                <p>No hay productos para esta marca.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php require "includes/v3/footer.php"; ?>
<?php require "includes/v3/login_modal.php"; ?>

<?php $waFlotanteMarcas = preg_replace('/\D/','', erp_config_empresa($pdo, $empresaId ?? 0)['whatsapp'] ?? '') ?: '51920137707'; ?>
<a class="v3-float-chat" target="_blank" href="https://wa.me/<?= h($waFlotanteMarcas) ?>">💬</a>

<script>window.MICA_CATEGORIA_ACTUAL = "";</script>
<script src="includes/v3/store_v3.js"></script>
<script src="includes/v3/login_modal.js"></script>
<script src="includes/v3/header_cliente.js"></script>

</body>
</html>

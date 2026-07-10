<?php
require_once __DIR__ . "/../config/db.php";

$buscar = $_GET["buscar"] ?? "";
$categoria = $_GET["categoria"] ?? "";

function q($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$categorias = $pdo->query("SELECT * FROM categorias WHERE activo=1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

function productos($pdo, $categoriaSlug="", $limit=100, $buscar=""){
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
    WHERE p.activo=1
    ";
    $params = [];

    if($categoriaSlug !== ""){
        $sql .= " AND c.slug = :categoria";
        $params[":categoria"] = $categoriaSlug;
    }

    if($buscar !== ""){
        $sql .= " AND (p.nombre LIKE :buscar OR p.descripcion_corta LIKE :buscar OR m.nombre LIKE :buscar)";
        $params[":buscar"] = "%$buscar%";
    }

    $sql .= " ORDER BY p.id DESC LIMIT " . (int)$limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$productos = productos($pdo, $categoria, 100, $buscar);

$sliders = [];
try{
    $sliders = $pdo->query("SELECT * FROM sliders WHERE activo=1 ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

if(!$sliders){
    $sliders = [[
        "titulo"=>"TECNOLOGÍA QUE",
        "titulo_resaltado"=>"CONECTA",
        "subtitulo"=>"Laptops, licencias, tóners, redes y servicios técnicos.",
        "texto_boton"=>"Ver tecnología",
        "url_boton"=>"index.php?categoria=tecnologia",
        "imagen"=>"",
        "color_inicio"=>"#020817",
        "color_fin"=>"#001b47",
        "color_resaltado"=>"#37c5ff"
    ]];
}

$bloques = [];
try{
    $bloques = $pdo->query("SELECT * FROM home_bloques WHERE activo=1 ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

$tituloPagina = "Catálogo";
foreach($categorias as $cat){
    if($cat["slug"] === $categoria){
        $tituloPagina = $cat["nombre"];
    }
}

function productoCard($p){
    ob_start(); ?>
    <article class="product-card">
        <?php if(!empty($p["oferta"])): ?>
            <span class="badge offer">OFERTA</span>
        <?php elseif(!empty($p["nuevo"])): ?>
            <span class="badge">NUEVO</span>
        <?php endif; ?>

        <img src="<?= q($p["imagen_principal"] ?: "../img/banners/slide-tecnologia.svg") ?>" alt="<?= q($p["nombre"]) ?>">

        <small><?= q($p["categoria"] ?? "Sin categoría") ?> · <?= q($p["subcategoria"] ?? "") ?></small>
        <h3><?= q($p["nombre"]) ?></h3>
        <p><?= q($p["descripcion_corta"] ?? "") ?></p>

        <div class="price">
            S/ <?= number_format((float)($p["precio_oferta"] > 0 ? $p["precio_oferta"] : $p["precio"]), 2) ?>
            <?php if($p["precio_oferta"] > 0): ?>
                <span>S/ <?= number_format((float)$p["precio"], 2) ?></span>
            <?php endif; ?>
        </div>

        <div class="stock">Stock: <?= (int)$p["stock"] ?></div>

        <div class="product-actions">
            <a href="../producto_mysql.php?id=<?= (int)$p["id"] ?>">Ver detalle</a>
            <button type="button" onclick="agregarCotizacion(<?= (int)$p['id'] ?>, '<?= q(addslashes($p['nombre'])) ?>')">Agregar</button>
        </div>
    </article>
    <?php return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mica Store v2</title>
    <link rel="stylesheet" href="assets/css/store.css">
</head>
<body>

<?php require __DIR__ . "/partials/topbar.php"; ?>
<?php require __DIR__ . "/partials/header.php"; ?>
<?php require __DIR__ . "/partials/menu.php"; ?>
<?php require __DIR__ . "/partials/slider.php"; ?>
<?php require __DIR__ . "/partials/services.php"; ?>

<main class="container">
    <div class="section-head">
        <h2><?= q($tituloPagina) ?></h2>
        <span><?= count($productos) ?> producto(s)</span>
    </div>

    <form class="filters" method="GET">
        <input name="buscar" value="<?= q($buscar) ?>" placeholder="Buscar producto, marca o descripción">
        <select name="categoria">
            <option value="">Todas las categorías</option>
            <?php foreach($categorias as $cat): ?>
                <option value="<?= q($cat["slug"]) ?>" <?= $categoria === $cat["slug"] ? "selected" : "" ?>>
                    <?= q($cat["nombre"]) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button>Buscar</button>
    </form>

    <section class="product-grid" id="productos">
        <?php foreach($productos as $p): ?>
            <?= productoCard($p) ?>
        <?php endforeach; ?>

        <?php if(count($productos) === 0): ?>
            <p>No hay productos registrados todavía.</p>
        <?php endif; ?>
    </section>

    <?php foreach($categorias as $cat): ?>
        <?php $nov = productos($pdo, $cat["slug"], 4); ?>
        <?php if(count($nov) > 0): ?>
            <div class="section-head">
                <h2>Novedades <?= q($cat["nombre"]) ?></h2>
                <a href="index.php?categoria=<?= q($cat["slug"]) ?>">Ver más →</a>
            </div>

            <section class="product-grid">
                <?php foreach($nov as $p): ?>
                    <?= productoCard($p) ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php require __DIR__ . "/partials/blocks.php"; ?>
</main>

<?php require __DIR__ . "/partials/footer.php"; ?>

<a class="float-chat" target="_blank" href="https://wa.me/51920137707">💬</a>

<script src="assets/js/store.js"></script>
</body>
</html>

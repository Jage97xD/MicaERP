<?php
require_once "../config/db.php";
require_once "cliente_common.php";
requerirCliente();

$clienteId = clienteId();

if(isset($_GET['eliminar'])){
    $productoId = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM cliente_favoritos WHERE cliente_id=? AND producto_id=?");
    $stmt->execute([$clienteId, $productoId]);

    header("Location: favoritos.php?ok=1");
    exit;
}

$stmt = $pdo->prepare("
SELECT
    p.*,
    c.nombre AS categoria,
    m.nombre AS marca
FROM cliente_favoritos f
INNER JOIN productos p ON f.producto_id = p.id
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN marcas m ON p.marca_id = m.id
WHERE f.cliente_id = ?
AND p.activo = 1
ORDER BY f.id DESC
");
$stmt->execute([$clienteId]);
$favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis favoritos - Mica Store</title>
<link rel="stylesheet" href="cliente_style.css">
<style>
.fav-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.products article{position:relative}
.remove-fav{background:#ef4444!important}
.empty-favs{text-align:center;padding:45px}
.empty-favs h2{font-size:28px}
.empty-favs a{display:inline-block;background:#0057d9;color:white;padding:14px 22px;border-radius:14px;font-weight:bold;margin-top:12px}
</style>
</head>
<body>

<header class="topbar">
    <a class="brand" href="../tienda_visual_v3.php"><span>M</span> Mica Store</a>
    <nav>
        <a href="mi_cuenta.php">Mi cuenta</a>
        <a href="../tienda_visual_v3.php">Tienda</a>
        <a href="cliente_logout.php">Salir</a>
    </nav>
</header>

<main class="wrap">
    <div class="fav-head">
        <div>
            <h1>Mis favoritos</h1>
            <p>Productos que guardaste para revisar luego.</p>
        </div>
        <strong><?= count($favoritos) ?> producto(s)</strong>
    </div>

    <?php if(isset($_GET['ok'])): ?>
        <div class="ok">Producto quitado de favoritos.</div>
    <?php endif; ?>

    <section class="card">
        <?php if(count($favoritos) === 0): ?>
            <div class="empty-favs">
                <h2>Aún no tienes favoritos</h2>
                <p>Guarda productos con el botón ❤️ para verlos aquí.</p>
                <a href="../tienda_visual_v3.php">Ir a la tienda</a>
            </div>
        <?php else: ?>
            <div class="products">
                <?php foreach($favoritos as $p): ?>
                    <?php $precio = $p['precio_oferta'] > 0 ? $p['precio_oferta'] : $p['precio']; ?>
                    <article>
                        <img src="../<?= h($p['imagen_principal'] ?: 'img/banners/slide-tecnologia.svg') ?>">
                        <small><?= h($p['categoria'] ?? '') ?> <?= !empty($p['marca']) ? '· '.h($p['marca']) : '' ?></small>
                        <h3><?= h($p['nombre']) ?></h3>
                        <strong>S/ <?= number_format((float)$precio, 2) ?></strong>
                        <a href="../producto_mysql.php?id=<?= (int)$p['id'] ?>">Ver producto</a>
                        <a class="remove-fav" href="favoritos.php?eliminar=<?= (int)$p['id'] ?>">Quitar ❤️</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

</body>
</html>

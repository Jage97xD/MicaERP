<?php
require_once "config/db.php";
require_once "config/erp_core.php";
require_once "includes/v3/common.php";
require_once "includes/v3/contacto_widget.php";

$slugTienda = trim($_GET['slug'] ?? '');
$stT = $pdo->prepare("SELECT t.*, c.nombre AS categoria_nombre, e.nombre AS empresa_nombre, e.slug AS empresa_slug
                       FROM marketplace_tiendas t
                       LEFT JOIN categorias c ON c.id = t.categoria_id
                       LEFT JOIN marketplace_empresas e ON e.id = t.empresa_id
                       WHERE t.slug = ? AND t.activo = 1 LIMIT 1");
$stT->execute([$slugTienda]);
$tienda = $stT->fetch(PDO::FETCH_ASSOC);

if(!$tienda){
    http_response_code(404);
    echo "<h1 style='font-family:sans-serif;text-align:center;margin-top:60px'>Esta tienda no existe o ya no está disponible.</h1>";
    echo "<p style='text-align:center'><a href='tiendas.php'>Ver todas las tiendas</a></p>";
    exit;
}

// El sitio del vendedor vive dentro del contexto de su empresa (mismo logo,
// colores y menú de la empresa a la que pertenece), para que se sienta parte
// del mismo mercado y no una página suelta.
$empresaId = (int)($tienda['empresa_id'] ?? 0);
$empresaSlugActual = $tienda['empresa_slug'] ?? null;
$GLOBALS['empresaSlugActual'] = $empresaSlugActual;
$GLOBALS['empresaId'] = $empresaId;

$buscar = '';
$categoria = '';
$categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$productos = obtenerProductosV3($pdo, '', 100, '', $empresaId, (int)$tienda['id']);

function micaHomeConfig($pdo, $clave, $default='', $empresaId=0){
    static $cache = [];
    $key = $empresaId.'|'.$clave;
    if(isset($cache[$key])) return $cache[$key];
    $cfg = erp_config_empresa($pdo, $empresaId);
    return $cache[$key] = ($cfg[$clave] ?? $default);
}

$waTienda = preg_replace('/\D/', '', $tienda['whatsapp'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= h($tienda['nombre']) ?><?= $tienda['empresa_nombre'] ? ' - '.h($tienda['empresa_nombre']) : '' ?></title>
<link rel="stylesheet" href="includes/v3/store_v3.css">
<link rel="stylesheet" href="includes/v3/login_modal.css">
<link rel="stylesheet" href="includes/v3/header_cliente.css">
<style>
.vendor-banner{max-width:1180px;margin:26px auto 0;padding:0 20px}
.vendor-card{background:linear-gradient(120deg,#07162f,var(--v3-primary,#0057d9));border-radius:24px;padding:30px;color:white;display:flex;align-items:center;gap:22px;flex-wrap:wrap;box-shadow:0 16px 34px rgba(15,23,42,.18)}
.vendor-logo{width:96px;height:96px;border-radius:20px;background:white;object-fit:contain;padding:8px;flex:0 0 auto}
.vendor-logo-fallback{width:96px;height:96px;border-radius:20px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:900;flex:0 0 auto}
.vendor-info h1{margin:0 0 6px;font-size:28px}
.vendor-info p{margin:2px 0;opacity:.9;font-size:14.5px}
.vendor-actions{margin-left:auto;display:flex;gap:10px;flex-wrap:wrap}
.vendor-actions a{background:#22c55e;color:white;padding:12px 18px;border-radius:12px;font-weight:800;text-decoration:none}
.vendor-actions a.secondary{background:rgba(255,255,255,.15)}
@media(max-width:700px){.vendor-actions{margin-left:0}}
</style>
</head>
<body>

<?php require "includes/v3/topbar.php"; ?>
<?php require "includes/v3/header.php"; ?>
<?php require "includes/v3/menu.php"; ?>

<section class="vendor-banner">
    <div class="vendor-card">
        <?php if(!empty($tienda['logo'])): ?>
            <img class="vendor-logo" src="<?= h($tienda['logo']) ?>" alt="<?= h($tienda['nombre']) ?>">
        <?php else: ?>
            <div class="vendor-logo-fallback"><?= h(mb_substr($tienda['nombre'],0,1)) ?></div>
        <?php endif; ?>
        <div class="vendor-info">
            <h1>🏬 <?= h($tienda['nombre']) ?></h1>
            <?php if(!empty($tienda['categoria_nombre'])): ?><p>Categoría: <?= h($tienda['categoria_nombre']) ?></p><?php endif; ?>
            <?php if(!empty($tienda['responsable'])): ?><p>Atendido por: <?= h($tienda['responsable']) ?></p><?php endif; ?>
            <?php if(!empty($tienda['direccion'])): ?><p>📍 <?= h($tienda['direccion']) ?></p><?php endif; ?>
            <?php if(!empty($tienda['descripcion'])): ?><p><?= nl2br(h($tienda['descripcion'])) ?></p><?php endif; ?>
        </div>
        <div class="vendor-actions">
            <?php if($waTienda !== ''): ?><a target="_blank" href="https://wa.me/<?= h($waTienda) ?>">💬 WhatsApp</a><?php endif; ?>
            <a class="secondary" href="tiendas.php">Ver todas las tiendas</a>
        </div>
    </div>
</section>

<main class="v3-container">
    <div class="v3-section-head">
        <h2>Productos de <?= h($tienda['nombre']) ?></h2>
        <span><?= count($productos) ?> producto(s)</span>
    </div>

    <section class="v3-product-grid">
        <?php foreach($productos as $p): ?>
            <?= productoCardV3($p) ?>
        <?php endforeach; ?>

        <?php if(count($productos) === 0): ?>
            <p>Esta tienda todavía no tiene productos publicados.</p>
        <?php endif; ?>
    </section>
</main>

<?php require "includes/v3/footer.php"; ?>
</body>
</html>

<?php
require_once "config/db.php";
require_once "config/erp_core.php";
require_once "includes/v3/common.php";
require_once "includes/v3/empresa_context.php";
$buscar = $_GET['buscar'] ?? '';
$categoria = '';
try{ $categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){ $categorias=[]; }
$config = micaConfigTodos($pdo);
$nombreTienda = $config['nombre_comercial'] ?? 'Mica Store';
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nosotros - <?= h($nombreTienda) ?></title><link rel="stylesheet" href="includes/v3/store_v3.css"><link rel="stylesheet" href="includes/v3/login_modal.css"><link rel="stylesheet" href="includes/v3/header_cliente.css"></head><body>
<?php require "includes/v3/topbar.php"; require "includes/v3/header.php"; require "includes/v3/menu.php"; ?>

<section class="v3-page-hero"><div class="v3-page-hero-inner"><h1>Nosotros</h1><p>Conoce la misión, visión y valores que guían a <?= h($nombreTienda) ?>.</p></div></section>
<main class="v3-page-wrap">
  <section class="v3-info-grid">
    <article class="v3-info-card"><h3>🎯 Misión</h3><p><?= nl2br(h($config['mision'] ?? 'Brindar productos y servicios de calidad, con atención cercana, precios competitivos y una experiencia de compra confiable para nuestros clientes.')) ?></p></article>
    <article class="v3-info-card"><h3>🚀 Visión</h3><p><?= nl2br(h($config['vision'] ?? 'Ser una tienda referente en nuestro mercado, integrando tecnología, atención personalizada y mejora continua para crecer junto con nuestros clientes.')) ?></p></article>
    <article class="v3-info-card"><h3>💎 Valores</h3><p><?= nl2br(h($config['valores'] ?? 'Honestidad, responsabilidad, puntualidad, respeto, innovación y compromiso con el cliente.')) ?></p><div class="v3-values"><span>Confianza</span><span>Servicio</span><span>Calidad</span></div></article>
  </section>
</main>
<?php require "includes/v3/footer.php"; require "includes/v3/login_modal.php"; ?>
<script>window.MICA_CATEGORIA_ACTUAL = "";</script><script src="includes/v3/store_v3.js"></script><script src="includes/v3/login_modal.js"></script><script src="includes/v3/header_cliente.js"></script></body></html>

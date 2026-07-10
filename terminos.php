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
<title>Términos y condiciones - <?= h($nombreTienda) ?></title><link rel="stylesheet" href="includes/v3/store_v3.css"><link rel="stylesheet" href="includes/v3/login_modal.css"><link rel="stylesheet" href="includes/v3/header_cliente.css"></head><body>
<?php require "includes/v3/topbar.php"; require "includes/v3/header.php"; require "includes/v3/menu.php"; ?>

<section class="v3-page-hero"><div class="v3-page-hero-inner"><h1>Términos y condiciones</h1><p>Información general sobre compras, cotizaciones, entregas y atención comercial.</p></div></section>
<main class="v3-page-wrap"><section class="v3-legal-card">
<?php if(!empty($config['terminos_texto'])): ?>
<?= nl2br(h($config['terminos_texto'])) ?>
<?php else: ?>
<h2>Condiciones generales</h2>
<p>Las cotizaciones generadas en la tienda están sujetas a validación de disponibilidad, precio final, forma de pago y condiciones de entrega.</p>
<ul><li>Los precios pueden variar hasta que la cotización sea confirmada por un vendedor.</li><li>La atención y entrega dependen de disponibilidad de stock y cobertura.</li><li>El pedido se considera aceptado cuando el pago fue validado y el vendedor confirma la atención.</li><li>Las garantías se atienden según las condiciones del producto, proveedor y comprobante de compra.</li></ul>
<p>Para consultas específicas, comunícate por nuestros canales oficiales.</p>
<?php endif; ?>
</section></main>
<?php require "includes/v3/footer.php"; require "includes/v3/login_modal.php"; ?>
<script>window.MICA_CATEGORIA_ACTUAL = "";</script><script src="includes/v3/store_v3.js"></script><script src="includes/v3/login_modal.js"></script><script src="includes/v3/header_cliente.js"></script></body></html>

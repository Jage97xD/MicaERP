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
<title>Política de privacidad - <?= h($nombreTienda) ?></title><link rel="stylesheet" href="includes/v3/store_v3.css"><link rel="stylesheet" href="includes/v3/login_modal.css"><link rel="stylesheet" href="includes/v3/header_cliente.css"></head><body>
<?php require "includes/v3/topbar.php"; require "includes/v3/header.php"; require "includes/v3/menu.php"; ?>

<section class="v3-page-hero"><div class="v3-page-hero-inner"><h1>Política de privacidad</h1><p>Tratamiento de datos personales y consentimiento comercial.</p></div></section>
<main class="v3-page-wrap"><section class="v3-legal-card">
<?php if(!empty($config['privacidad_texto'])): ?>
<?= nl2br(h($config['privacidad_texto'])) ?>
<?php else: ?>
<h2>Uso de datos personales</h2>
<p>Los datos registrados por clientes se utilizan para gestionar cotizaciones, pedidos, atención, entregas y comunicaciones comerciales cuando el cliente lo autoriza.</p>
<ul><li>Podemos contactarte para responder consultas o coordinar pedidos.</li><li>Solo enviaremos ofertas si aceptaste recibir promociones.</li><li>Puedes solicitar actualización o eliminación de tus datos por nuestros canales de contacto.</li><li>No vendemos información personal a terceros.</li></ul>
<?php endif; ?>
</section></main>
<?php require "includes/v3/footer.php"; require "includes/v3/login_modal.php"; ?>
<script>window.MICA_CATEGORIA_ACTUAL = "";</script><script src="includes/v3/store_v3.js"></script><script src="includes/v3/login_modal.js"></script><script src="includes/v3/header_cliente.js"></script></body></html>

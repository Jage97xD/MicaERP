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
$ok=''; $error='';
try{
    $pdo->exec("CREATE TABLE IF NOT EXISTS libro_reclamaciones (id INT AUTO_INCREMENT PRIMARY KEY, codigo VARCHAR(30) NULL, tipo VARCHAR(30) NOT NULL, nombre VARCHAR(160) NOT NULL, documento VARCHAR(30) NULL, correo VARCHAR(160) NULL, celular VARCHAR(40) NULL, direccion VARCHAR(220) NULL, producto_servicio VARCHAR(180) NULL, detalle TEXT NOT NULL, pedido TEXT NULL, estado VARCHAR(40) DEFAULT 'Nuevo', creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}catch(Exception $e){}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $tipo=trim($_POST['tipo']??'Reclamo'); $nombre=trim($_POST['nombre']??''); $detalle=trim($_POST['detalle']??'');
    if($nombre==='' || $detalle===''){$error='Ingresa tu nombre y el detalle del reclamo o queja.';} else {
        try{
            $codigo='LR-'.date('Ymd').'-'.rand(1000,9999);
            $stmt=$pdo->prepare("INSERT INTO libro_reclamaciones (codigo,tipo,nombre,documento,correo,celular,direccion,producto_servicio,detalle,pedido) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$codigo,$tipo,$nombre,trim($_POST['documento']??''),trim($_POST['correo']??''),trim($_POST['celular']??''),trim($_POST['direccion']??''),trim($_POST['producto_servicio']??''),$detalle,trim($_POST['pedido']??'')]);
            $ok='Tu registro fue enviado correctamente. Código: '.$codigo;
        }catch(Exception $e){ $error='No se pudo registrar. Intenta nuevamente o comunícate por WhatsApp.'; }
    }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Libro de reclamaciones - <?= h($nombreTienda) ?></title><link rel="stylesheet" href="includes/v3/store_v3.css"><link rel="stylesheet" href="includes/v3/login_modal.css"><link rel="stylesheet" href="includes/v3/header_cliente.css"></head><body>
<?php require "includes/v3/topbar.php"; require "includes/v3/header.php"; require "includes/v3/menu.php"; ?>
<section class="v3-page-hero"><div class="v3-page-hero-inner"><h1>Libro de reclamaciones</h1><p>Registra un reclamo o queja relacionada con productos, servicios, atención o pedidos.</p></div></section>
<main class="v3-page-wrap"><section class="v3-form-card">
<?php if($ok): ?><div class="v3-alert-ok"><?= h($ok) ?></div><?php endif; ?><?php if($error): ?><div class="v3-alert-error"><?= h($error) ?></div><?php endif; ?>
<form method="POST" class="v3-claim-form">
<div><label>Tipo *</label><select name="tipo"><option>Reclamo</option><option>Queja</option></select></div>
<div><label>Producto/servicio</label><input name="producto_servicio" placeholder="Ejemplo: pedido, producto o atención"></div>
<div><label>Nombre completo *</label><input name="nombre" required></div><div><label>DNI/RUC</label><input name="documento"></div>
<div><label>Correo</label><input type="email" name="correo"></div><div><label>Celular</label><input name="celular"></div>
<div class="full"><label>Dirección</label><input name="direccion"></div>
<div class="full"><label>Detalle *</label><textarea name="detalle" required placeholder="Describe lo ocurrido"></textarea></div>
<div class="full"><label>Pedido del consumidor</label><textarea name="pedido" placeholder="Indica qué solución solicitas"></textarea></div>
<div class="full"><button type="submit">Enviar registro</button></div>
</form></section></main>
<?php require "includes/v3/footer.php"; require "includes/v3/login_modal.php"; ?>
<script>window.MICA_CATEGORIA_ACTUAL="";</script><script src="includes/v3/store_v3.js"></script><script src="includes/v3/login_modal.js"></script><script src="includes/v3/header_cliente.js"></script></body></html>

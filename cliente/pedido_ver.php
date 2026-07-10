<?php
require_once "../config/db.php";
require_once "cliente_common.php";
requerirCliente();

function estadoClass($estado){
    $slug = strtolower(trim((string)$estado));
    $slug = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

$estados = [
    'Pendiente de revisión' => 'Recibimos tu cotización. Un vendedor la revisará.',
    'Esperando pago' => 'El vendedor está esperando la confirmación del pago.',
    'Pago recibido' => 'Tu pago fue validado.',
    'Pedido aceptado' => 'Tu pedido fue aceptado y será preparado.',
    'Preparando pedido' => 'Estamos preparando tus productos.',
    'Salió de tienda' => 'Tu pedido salió de la tienda.',
    'En camino' => 'Tu pedido está en camino.',
    'Entregado' => 'Tu producto fue entregado.',
];

$cid = clienteId();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM clientes_web WHERE id=?");
$stmt->execute([$cid]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id=? AND (cliente_web_id=? OR correo=? OR documento=?) LIMIT 1");
$stmt->execute([$id, $cid, $cliente['correo'] ?? '', $cliente['documento'] ?? '']);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$pedido){ die('Pedido no encontrado.'); }

$stmt = $pdo->prepare("SELECT d.*, p.nombre, p.imagen_principal FROM cotizacion_detalle d LEFT JOIN productos p ON d.producto_id=p.id WHERE d.cotizacion_id=? ORDER BY d.id ASC");
$stmt->execute([$id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$estadoActual = $pedido['estado'] ?? 'Pendiente de revisión';
$actualIndex = array_search($estadoActual, array_keys($estados), true);
if($estadoActual === 'Cancelado' || $estadoActual === 'Anulado') $actualIndex = -1;
if($actualIndex === false) $actualIndex = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Estado de pedido - Mica Store</title>
<link rel="stylesheet" href="cliente_style.css">
<style>
.order-head{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}.order-number{font-size:28px;margin:0}.estado{display:inline-block;border-radius:999px;padding:8px 13px;font-size:13px;font-weight:900}.estado-pendiente-de-revision{background:#fef3c7;color:#92400e}.estado-esperando-pago{background:#ffedd5;color:#9a3412}.estado-pago-recibido,.estado-pedido-aceptado{background:#dcfce7;color:#166534}.estado-preparando-pedido{background:#dbeafe;color:#1e40af}.estado-salio-de-tienda,.estado-en-camino{background:#e0f2fe;color:#075985}.estado-entregado{background:#dcfce7;color:#166534}.estado-cancelado,.estado-anulado{background:#fee2e2;color:#991b1b}.timeline{display:grid;gap:12px;margin-top:20px}.step{display:grid;grid-template-columns:34px 1fr;gap:12px;align-items:start;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:14px}.dot{width:34px;height:34px;border-radius:50%;background:#cbd5e1;color:white;display:flex;align-items:center;justify-content:center;font-weight:900}.step.done{background:#f0fdf4;border-color:#86efac}.step.done .dot{background:#16a34a}.step.current{background:#eff6ff;border-color:#93c5fd}.step.current .dot{background:#2563eb}.step h3{margin:0 0 4px}.step p{margin:0;color:#64748b}.cancel-box{background:#fee2e2;color:#991b1b;border-radius:16px;padding:16px;font-weight:900}.eta{background:#eef2ff;color:#1e3a8a;border-radius:16px;padding:16px;margin-top:16px;font-weight:900}.item{display:grid;grid-template-columns:70px 1fr auto;gap:14px;align-items:center;border-bottom:1px solid #e5e7eb;padding:12px 0}.item img{width:70px;height:60px;object-fit:cover;border-radius:10px;background:#f8fafc}.note{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;border-radius:16px;padding:16px;margin-top:16px}@media(max-width:700px){.order-head,.item{grid-template-columns:1fr;display:block}.item img{margin-bottom:10px}}
</style>
</head>
<body>
<header class="topbar">
    <a class="brand" href="../tienda_visual_v3.php"><span>M</span> Mica Store</a>
    <nav>
        <a href="mis_pedidos.php">Mis pedidos</a>
        <a href="mi_cuenta.php">Mi cuenta</a>
        <a href="../tienda_visual_v3.php">Tienda</a>
        <a href="cliente_logout.php">Salir</a>
    </nav>
</header>

<main class="wrap">
    <section class="card">
        <div class="order-head">
            <div>
                <h1 class="order-number"><?= h($pedido['numero'] ?? 'COT-'.$pedido['id']) ?></h1>
                <p>Fecha: <?= h($pedido['creado_en'] ?? '') ?></p>
            </div>
            <div>
                <span class="estado estado-<?= h(estadoClass($estadoActual)) ?>"><?= h($estadoActual) ?></span>
                <h2>S/ <?= number_format((float)($pedido['total'] ?? 0), 2) ?></h2>
            </div>
        </div>

        <?php if(!empty($pedido['fecha_entrega_estimada'])): ?>
            <div class="eta">Llegada aproximada: <?= h(date('d/m/Y H:i', strtotime($pedido['fecha_entrega_estimada']))) ?></div>
        <?php endif; ?>

        <?php if(!empty($pedido['tracking_observacion'])): ?>
            <div class="note"><?= nl2br(h($pedido['tracking_observacion'])) ?></div>
        <?php endif; ?>

        <?php if($estadoActual === 'Cancelado' || $estadoActual === 'Anulado'): ?>
            <div class="cancel-box" style="margin-top:18px;">Este pedido fue cancelado. Comunícate con ventas para más información.</div>
        <?php else: ?>
            <div class="timeline">
                <?php $i=0; foreach($estados as $estado=>$texto): ?>
                    <div class="step <?= $i < $actualIndex ? 'done' : ($i === $actualIndex ? 'current' : '') ?>">
                        <div class="dot"><?= $i < $actualIndex ? '✓' : ($i+1) ?></div>
                        <div><h3><?= h($estado) ?></h3><p><?= h($texto) ?></p></div>
                    </div>
                <?php $i++; endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Productos del pedido</h2>
        <?php foreach($detalles as $d): ?>
            <div class="item">
                <img src="../<?= h($d['imagen_principal'] ?: 'img/banners/slide-tecnologia.svg') ?>">
                <div>
                    <strong><?= h($d['nombre'] ?: ($d['producto_nombre'] ?? 'Producto')) ?></strong><br>
                    <small>Cantidad: <?= (int)$d['cantidad'] ?> · Precio: S/ <?= number_format((float)($d['precio'] ?? 0), 2) ?></small>
                </div>
                <strong>S/ <?= number_format((float)($d['subtotal'] ?? 0), 2) ?></strong>
            </div>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>

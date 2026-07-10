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

$cid = clienteId();
$stmt = $pdo->prepare("SELECT * FROM clientes_web WHERE id=?");
$stmt->execute([$cid]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE cliente_web_id=? OR correo=? OR documento=? ORDER BY id DESC");
$stmt->execute([$cid, $cliente['correo'] ?? '', $cliente['documento'] ?? '']);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis pedidos - Mica Store</title>
<link rel="stylesheet" href="cliente_style.css">
<style>
.order-card{display:grid;grid-template-columns:1fr auto;gap:18px;align-items:center;border:1px solid #e5e7eb;border-radius:18px;padding:18px;margin-bottom:14px;background:#fff}.order-card h3{margin:0 0 6px}.order-meta{color:#64748b;font-size:14px}.order-total{font-size:22px;color:#0057d9;font-weight:900;text-align:right}.order-actions a{display:inline-block;background:#111827;color:#fff;padding:11px 16px;border-radius:12px;font-weight:900;margin-top:8px}.estado{display:inline-block;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:900}.estado-pendiente,.estado-pendiente-de-revision{background:#fef3c7;color:#92400e}.estado-esperando-pago{background:#ffedd5;color:#9a3412}.estado-pago-recibido,.estado-pedido-aceptado{background:#dcfce7;color:#166534}.estado-preparando-pedido{background:#dbeafe;color:#1e40af}.estado-salio-de-tienda,.estado-en-camino{background:#e0f2fe;color:#075985}.estado-entregado{background:#dcfce7;color:#166534}.estado-cancelado,.estado-anulado{background:#fee2e2;color:#991b1b}.empty-orders{text-align:center;padding:45px}.empty-orders a{display:inline-block;background:#0057d9;color:#fff;padding:14px 22px;border-radius:14px;font-weight:900;margin-top:12px}@media(max-width:700px){.order-card{grid-template-columns:1fr}.order-total{text-align:left}}
</style>
</head>
<body>
<header class="topbar">
    <a class="brand" href="../tienda_visual_v3.php"><span>M</span> Mica Store</a>
    <nav>
        <a href="mi_cuenta.php">Mi cuenta</a>
        <a href="favoritos.php">Favoritos</a>
        <a href="../tienda_visual_v3.php">Tienda</a>
        <a href="cliente_logout.php">Salir</a>
    </nav>
</header>

<main class="wrap">
    <h1>Mis pedidos</h1>
    <p>Consulta el avance de tus cotizaciones y pedidos.</p>

    <section class="card">
        <?php if(count($pedidos) === 0): ?>
            <div class="empty-orders">
                <h2>Aún no tienes pedidos</h2>
                <p>Agrega productos a tu cotización para iniciar un pedido.</p>
                <a href="../tienda_visual_v3.php">Ir a la tienda</a>
            </div>
        <?php else: ?>
            <?php foreach($pedidos as $p): ?>
                <?php $estado = $p['estado'] ?? 'Pendiente de revisión'; ?>
                <article class="order-card">
                    <div>
                        <h3><?= h($p['numero'] ?? 'COT-'.$p['id']) ?></h3>
                        <div class="order-meta">Fecha: <?= h($p['creado_en'] ?? '') ?></div>
                        <div style="margin-top:10px"><span class="estado estado-<?= h(estadoClass($estado)) ?>"><?= h($estado) ?></span></div>
                        <?php if(!empty($p['fecha_entrega_estimada'])): ?>
                            <div class="order-meta" style="margin-top:8px">Llegada aproximada: <?= h(date('d/m/Y H:i', strtotime($p['fecha_entrega_estimada']))) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="order-actions">
                        <div class="order-total">S/ <?= number_format((float)($p['total'] ?? 0), 2) ?></div>
                        <a href="pedido_ver.php?id=<?= (int)$p['id'] ?>">Ver estado</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
</body>
</html>

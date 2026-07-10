<?php
require_once "../config/db.php";
require_once "layout.php";

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$cliente){ die("Cliente no encontrado"); }
$scopeCliente = erp_scope_sql_tienda_cotizacion($pdo, 'co');
$stmt = $pdo->prepare("SELECT co.* FROM cotizaciones co WHERE co.cliente_id = ? $scopeCliente ORDER BY co.id DESC");
$stmt->execute([$id]);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCotizado = 0;
foreach($cotizaciones as $c){ $totalCotizado += $c['total']; }

admin_header("Cliente: " . $cliente['nombre'], "clientes");
?>

<style>
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:22px}
.info-card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:18px}
.info-card p{margin:8px 0}
@media(max-width:800px){.detail-grid{grid-template-columns:1fr}}
</style>

<div class="panel">
    <div class="panel-header">
        <h3>Detalle del cliente</h3>
        <a class="btn gray" href="clientes.php">Volver</a>
    </div>

    <div class="detail-grid">
        <div class="info-card">
            <h3>Datos principales</h3>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($cliente['nombre']) ?></p>
            <p><strong>DNI/RUC:</strong> <?= htmlspecialchars($cliente['documento']) ?></p>
            <p><strong>Celular:</strong> <?= htmlspecialchars($cliente['celular']) ?></p>
            <p><strong>Correo:</strong> <?= htmlspecialchars($cliente['correo'] ?? '-') ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($cliente['direccion'] ?? '-') ?></p>
            <p><strong>Acepta ofertas:</strong> <?= !empty($cliente['acepta_ofertas']) ? 'Sí' : 'No indicado' ?></p>
        </div>

        <div class="info-card">
            <h3>Resumen comercial</h3>
            <p><strong>Total cotizaciones:</strong> <?= count($cotizaciones) ?></p>
            <p><strong>Total cotizado:</strong> S/ <?= number_format($totalCotizado, 2) ?></p>
            <p><strong>Registrado:</strong> <?= htmlspecialchars($cliente['creado_en']) ?></p>
        </div>
    </div>

    <?php if(!erp_es_admin_global()): ?><div style="background:#eef6ff;color:#1e3a8a;border:1px solid #bfdbfe;border-radius:14px;padding:14px;margin:16px 0;font-weight:800;">Solo se muestran cotizaciones/pedidos relacionados a tus categorías permitidas: <?= htmlspecialchars(erp_scope_resumen_comercial($pdo)) ?>.</div><?php endif; ?>

    <h3>Historial de cotizaciones visibles</h3>

    <table class="table">
        <thead>
            <tr><th>N°</th><th>Entrega</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Detalle</th></tr>
        </thead>
        <tbody>
            <?php foreach($cotizaciones as $c): ?>
            <tr>
                <td>#<?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['tipo_entrega']) ?></td>
                <td>S/ <?= number_format($c['total'], 2) ?></td>
                <td><?= htmlspecialchars($c['estado']) ?></td>
                <td><?= htmlspecialchars($c['creado_en']) ?></td>
                <td><a class="btn gray" href="cotizacion_ver_pro.php?id=<?= $c['id'] ?>">Ver</a></td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($cotizaciones) === 0): ?>
            <tr><td colspan="6">Este cliente todavía no tiene cotizaciones.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_footer(); ?>
<?php
require_once "../config/db.php";
require_once "layout.php";

$scopeP = erp_es_vendedor_tienda() ? ' AND tienda_id='.(int)erp_tienda_id_actual().' ' : erp_scope_sql_producto($pdo, 'categoria_id');
$scopePp = erp_scope_sql_tienda_producto($pdo, 'p');
$scopeCo = erp_scope_sql_tienda_cotizacion($pdo, 'co');

$totalProductos = $pdo->query("SELECT COUNT(*) FROM productos WHERE 1=1 $scopeP")->fetchColumn();
$productosActivos = $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1 $scopeP")->fetchColumn();
$totalClientes = $pdo->query("SELECT COUNT(DISTINCT co.cliente_id) FROM cotizaciones co WHERE co.cliente_id IS NOT NULL $scopeCo")->fetchColumn();
$totalCotizaciones = $pdo->query("SELECT COUNT(*) FROM cotizaciones co WHERE 1=1 $scopeCo")->fetchColumn();
$cotPendientes = $pdo->query("SELECT COUNT(*) FROM cotizaciones co WHERE co.estado IN ('Pendiente','Pendiente de revisión') $scopeCo")->fetchColumn();
$cotProceso = $pdo->query("SELECT COUNT(*) FROM cotizaciones co WHERE co.estado IN ('En proceso','Pedido aceptado','Preparando pedido','Salió de tienda','En camino') $scopeCo")->fetchColumn();
$cotAtendidas = $pdo->query("SELECT COUNT(*) FROM cotizaciones co WHERE co.estado IN ('Atendido','Entregado') $scopeCo")->fetchColumn();
$stockBajo = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock <= stock_minimo AND activo = 1 $scopeP")->fetchColumn();
$valorInventario = $pdo->query("SELECT COALESCE(SUM(stock * costo),0) FROM productos WHERE 1=1 $scopeP")->fetchColumn();
$totalCotizado = $pdo->query("SELECT COALESCE(SUM(total),0) FROM cotizaciones co WHERE 1=1 $scopeCo")->fetchColumn();

$ultimasCotizaciones = $pdo->query("SELECT co.id, co.nombre_cliente, co.celular, co.total, co.estado, co.creado_en FROM cotizaciones co WHERE 1=1 $scopeCo ORDER BY co.id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

$stockCritico = $pdo->query("SELECT id, nombre, stock, stock_minimo FROM productos WHERE stock <= stock_minimo AND activo = 1 $scopeP ORDER BY stock ASC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

$topProductos = $pdo->query("SELECT cd.producto_nombre, SUM(cd.cantidad) AS unidades, SUM(cd.subtotal) AS total FROM cotizacion_detalle cd INNER JOIN productos p ON p.id=cd.producto_id WHERE 1=1 $scopePp GROUP BY cd.producto_nombre ORDER BY unidades DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

$cotPorEstado = $pdo->query("SELECT co.estado, COUNT(*) AS total FROM cotizaciones co WHERE 1=1 $scopeCo GROUP BY co.estado")->fetchAll(PDO::FETCH_ASSOC);

$labelsEstado = [];
$dataEstado = [];
foreach($cotPorEstado as $e){
    $labelsEstado[] = $e['estado'];
    $dataEstado[] = (int)$e['total'];
}

$labelsTop = [];
$dataTop = [];
foreach($topProductos as $p){
    $labelsTop[] = $p['producto_nombre'];
    $dataTop[] = (int)$p['unidades'];
}

admin_header("Dashboard", "dashboard");
?>

<style>
.kpi-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    margin-bottom:24px;
}
.kpi{
    background:white;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:20px;
    box-shadow:0 8px 24px rgba(15,23,42,.08);
}
.kpi small{
    color:#6b7280;
    font-weight:bold;
}
.kpi strong{
    display:block;
    font-size:30px;
    margin-top:8px;
    color:#2563eb;
}
.grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:22px;
    margin-bottom:22px;
}
.status{
    padding:6px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}
.status.pendiente{background:#fef3c7;color:#92400e}
.status.proceso{background:#dbeafe;color:#1e40af}
.status.atendido{background:#dcfce7;color:#166534}
.status.anulado{background:#fee2e2;color:#991b1b}
.stock-danger{color:#dc2626;font-weight:bold}
.chart-box{
    height:300px;
}
@media(max-width:1000px){
    .kpi-grid{grid-template-columns:repeat(2,1fr)}
    .grid-2{grid-template-columns:1fr}
}
@media(max-width:600px){
    .kpi-grid{grid-template-columns:1fr}
}
</style>

<div class="kpi-grid">
    <div class="kpi"><small>Productos activos</small><strong><?= $productosActivos ?></strong></div>
    <div class="kpi"><small>Clientes</small><strong><?= $totalClientes ?></strong></div>
    <div class="kpi"><small>Cotizaciones pendientes</small><strong><?= $cotPendientes ?></strong></div>
    <div class="kpi"><small>Stock bajo</small><strong><?= $stockBajo ?></strong></div>

    <div class="kpi"><small>Total productos</small><strong><?= $totalProductos ?></strong></div>
    <div class="kpi"><small>Total cotizaciones</small><strong><?= $totalCotizaciones ?></strong></div>
    <div class="kpi"><small>Total cotizado</small><strong>S/ <?= number_format($totalCotizado, 2) ?></strong></div>
    <div class="kpi"><small>Valor inventario</small><strong>S/ <?= number_format($valorInventario, 2) ?></strong></div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-header"><h3>Cotizaciones por estado</h3></div>
        <div class="chart-box"><canvas id="chartEstados"></canvas></div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3>Top productos cotizados</h3></div>
        <div class="chart-box"><canvas id="chartProductos"></canvas></div>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-header">
            <h3>Últimas cotizaciones</h3>
            <a class="btn gray" href="cotizaciones.php">Ver todas</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ultimasCotizaciones as $c): ?>
                <?php
                    $class = "pendiente";
                    if($c['estado'] === "En proceso") $class = "proceso";
                    if($c['estado'] === "Atendido") $class = "atendido";
                    if($c['estado'] === "Anulado") $class = "anulado";
                ?>
                <tr>
                    <td><a href="cotizacion_ver_pro.php?id=<?= $c['id'] ?>">#<?= $c['id'] ?></a></td>
                    <td><?= htmlspecialchars($c['nombre_cliente']) ?></td>
                    <td>S/ <?= number_format($c['total'], 2) ?></td>
                    <td><span class="status <?= $class ?>"><?= htmlspecialchars($c['estado']) ?></span></td>
                    <td><?= $c['creado_en'] ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if(count($ultimasCotizaciones) === 0): ?>
                <tr><td colspan="5">Todavía no hay cotizaciones.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>Productos con stock crítico</h3>
            <a class="btn gray" href="inventario.php">Ir a inventario</a>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Stock</th>
                    <th>Mínimo</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($stockCritico as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td class="stock-danger"><?= $p['stock'] ?></td>
                    <td><?= $p['stock_minimo'] ?></td>
                    <td><a class="btn green" href="inventario_movimiento.php?producto_id=<?= $p['id'] ?>&tipo=Entrada">Entrada</a></td>
                </tr>
                <?php endforeach; ?>

                <?php if(count($stockCritico) === 0): ?>
                <tr><td colspan="4">No hay productos en stock crítico.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3>Resumen rápido</h3><p style="margin:4px 0 0;color:#64748b;">Alcance: <?= htmlspecialchars(alcanceResumen()) ?></p>
    </div>

    <table class="table">
        <tr>
            <th>Cotizaciones pendientes</th>
            <td><?= $cotPendientes ?></td>
        </tr>
        <tr>
            <th>Cotizaciones en proceso</th>
            <td><?= $cotProceso ?></td>
        </tr>
        <tr>
            <th>Cotizaciones atendidas</th>
            <td><?= $cotAtendidas ?></td>
        </tr>
        <tr>
            <th>Valor total del inventario</th>
            <td>S/ <?= number_format($valorInventario, 2) ?></td>
        </tr>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labelsEstado = <?= json_encode($labelsEstado, JSON_UNESCAPED_UNICODE) ?>;
const dataEstado = <?= json_encode($dataEstado) ?>;
const labelsTop = <?= json_encode($labelsTop, JSON_UNESCAPED_UNICODE) ?>;
const dataTop = <?= json_encode($dataTop) ?>;

new Chart(document.getElementById("chartEstados"), {
    type: "doughnut",
    data: {
        labels: labelsEstado,
        datasets: [{
            data: dataEstado
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById("chartProductos"), {
    type: "bar",
    data: {
        labels: labelsTop,
        datasets: [{
            label: "Unidades cotizadas",
            data: dataTop
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php admin_footer(); ?>
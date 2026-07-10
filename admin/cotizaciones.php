<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
function estadoClass($estado){
    $slug = strtolower(trim((string)$estado));
    $slug = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

$estadosPedido = [
    'Pendiente de revisión',
    'Esperando pago',
    'Pago recibido',
    'Pedido aceptado',
    'Preparando pedido',
    'Salió de tienda',
    'En camino',
    'Entregado',
    'Cancelado'
];

$buscar = $_GET['buscar'] ?? '';
$estado = $_GET['estado'] ?? '';

$sql = "SELECT * FROM cotizaciones co WHERE 1=1";
$sql .= erp_scope_sql_tienda_cotizacion($pdo, 'co');
$params = [];

if ($buscar !== '') {
    $sql .= " AND (co.nombre_cliente LIKE :buscar OR co.nombre_completo LIKE :buscar OR co.nombre LIKE :buscar OR co.documento LIKE :buscar OR co.dni_ruc LIKE :buscar OR co.celular LIKE :buscar OR co.telefono LIKE :buscar OR co.id LIKE :buscar OR co.numero LIKE :buscar)";
    $params[':buscar'] = "%$buscar%";
}

if ($estado !== '') {
    $sql .= " AND co.estado = :estado";
    $params[':estado'] = $estado;
}

$sql .= " ORDER BY co.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

function contarEstado($pdo, $estado){
    try{
        $scope = erp_scope_sql_tienda_cotizacion($pdo, 'co');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones co WHERE co.estado=? $scope");
        $stmt->execute([$estado]);
        return (int)$stmt->fetchColumn();
    }catch(Exception $e){ return 0; }
}

$totalPendiente = contarEstado($pdo, 'Pendiente de revisión') + contarEstado($pdo, 'Pendiente');
$totalPago = contarEstado($pdo, 'Esperando pago') + contarEstado($pdo, 'Pago recibido');
$totalProceso = contarEstado($pdo, 'Pedido aceptado') + contarEstado($pdo, 'Preparando pedido') + contarEstado($pdo, 'Salió de tienda') + contarEstado($pdo, 'En camino');
$totalEntregado = contarEstado($pdo, 'Entregado');

admin_header("Cotizaciones y pedidos", "cotizaciones");
?>

<style>
.filter-box{display:grid;grid-template-columns:1fr 240px 120px;gap:12px;margin-bottom:20px}.filter-box input,.filter-box select{padding:12px;border:1px solid #e5e7eb;border-radius:10px;font-size:15px}.estado{padding:7px 10px;border-radius:20px;font-size:12px;font-weight:bold;display:inline-block}.estado-pendiente,.estado-pendiente-de-revision{background:#fef3c7;color:#92400e}.estado-esperando-pago{background:#ffedd5;color:#9a3412}.estado-pago-recibido,.estado-pedido-aceptado{background:#dcfce7;color:#166534}.estado-preparando-pedido{background:#dbeafe;color:#1e40af}.estado-salio-de-tienda,.estado-en-camino{background:#e0f2fe;color:#075985}.estado-entregado{background:#dcfce7;color:#166534}.estado-cancelado,.estado-anulado{background:#fee2e2;color:#991b1b}.tracking-mini{font-size:12px;color:#64748b;margin-top:4px}@media(max-width:800px){.filter-box{grid-template-columns:1fr}}
</style>

<div class="cards">
    <div class="card"><small>Pendientes</small><strong><?= $totalPendiente ?></strong></div>
    <div class="card"><small>Pago</small><strong><?= $totalPago ?></strong></div>
    <div class="card"><small>En proceso</small><strong><?= $totalProceso ?></strong></div>
    <div class="card"><small>Entregados</small><strong><?= $totalEntregado ?></strong></div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h3>Cotizaciones y pedidos</h3>
            <p style="margin:4px 0 0;color:#64748b;">Valida pagos y actualiza el estado que verá el cliente. Alcance: <?= h(alcanceResumen()) ?></p>
        </div>
    </div>

    <form class="filter-box" method="GET">
        <input name="buscar" value="<?= h($buscar) ?>" placeholder="Buscar por número, cliente, DNI/RUC o celular">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach($estadosPedido as $e): ?>
                <option value="<?= h($e) ?>" <?= $estado===$e?'selected':'' ?>><?= h($e) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Filtrar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>N°</th><th>Cliente</th><th>Documento</th><th>Celular</th>
                <th>Entrega</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($cotizaciones as $c): ?>
            <?php $estadoActual = $c['estado'] ?? 'Pendiente de revisión'; ?>
            <tr>
                <td><strong><?= h($c['numero'] ?? '#'.$c['id']) ?></strong></td>
                <td><?= h($c['nombre_cliente'] ?? $c['nombre_completo'] ?? $c['nombre'] ?? '') ?></td>
                <td><?= h($c['documento'] ?? $c['dni_ruc'] ?? '') ?></td>
                <td><?= h($c['celular'] ?? $c['telefono'] ?? '') ?></td>
                <td><?= h($c['tipo_entrega'] ?? $c['entrega'] ?? '') ?></td>
                <td>S/ <?= number_format((float)($c['total'] ?? 0), 2) ?></td>
                <td>
                    <span class="estado estado-<?= h(estadoClass($estadoActual)) ?>"><?= h($estadoActual) ?></span>
                    <?php if(!empty($c['fecha_entrega_estimada'])): ?><div class="tracking-mini">ETA: <?= h(date('d/m/Y H:i', strtotime($c['fecha_entrega_estimada']))) ?></div><?php endif; ?>
                </td>
                <td><?= h($c['creado_en'] ?? '') ?></td>
                <td><a class="btn gray" href="cotizacion_ver_pro.php?id=<?= (int)$c['id'] ?>">Ver</a></td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($cotizaciones) === 0): ?>
            <tr><td colspan="9">No hay cotizaciones registradas.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_footer(); ?>

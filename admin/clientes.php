<?php
require_once "../config/db.php";
require_once "layout.php";

$buscar = $_GET['buscar'] ?? '';

/*
 * CRM global:
 * - El cliente es compartido para todo MicaStore.
 * - Ventas puede ver datos básicos de todos los clientes.
 * - Los totales/cotizaciones se filtran por el alcance de categorías del usuario.
 */
$scopeCot = erp_scope_sql_tienda_cotizacion($pdo, 'co');

$sql = "
SELECT
    cl.*,
    COUNT(co.id) AS total_cotizaciones,
    COALESCE(SUM(co.total),0) AS total_cotizado,
    MAX(co.creado_en) AS ultima_cotizacion
FROM clientes cl
LEFT JOIN cotizaciones co ON co.cliente_id = cl.id $scopeCot
WHERE 1=1
";
$params = [];

if ($buscar !== '') {
    $sql .= " AND (cl.nombre LIKE :buscar OR cl.documento LIKE :buscar OR cl.celular LIKE :buscar OR cl.correo LIKE :buscar)";
    $params[':buscar'] = "%$buscar%";
}

$sql .= " GROUP BY cl.id ORDER BY cl.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$clientesCotizan = $pdo->query("SELECT COUNT(DISTINCT co.cliente_id) FROM cotizaciones co WHERE co.cliente_id IS NOT NULL $scopeCot")->fetchColumn();
$totalCotizado = $pdo->query("SELECT COALESCE(SUM(co.total),0) FROM cotizaciones co WHERE 1=1 $scopeCot")->fetchColumn();

admin_header("Clientes", "clientes");
?>

<style>
.filter-box{display:grid;grid-template-columns:1fr 120px;gap:12px;margin-bottom:20px}
.filter-box input{padding:12px;border:1px solid #e5e7eb;border-radius:10px;font-size:15px}
.crm-note{background:#eef6ff;color:#1e3a8a;border:1px solid #bfdbfe;border-radius:14px;padding:14px;margin-bottom:16px;font-weight:800}
.consent{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:900}
.consent.yes{background:#dcfce7;color:#166534}.consent.no{background:#f1f5f9;color:#475569}
@media(max-width:800px){.filter-box{grid-template-columns:1fr}}
</style>

<div class="cards">
    <div class="card"><small>Clientes registrados</small><strong><?= $totalClientes ?></strong></div>
    <div class="card"><small>Clientes con cotización visible</small><strong><?= $clientesCotizan ?></strong></div>
    <div class="card"><small>Total cotizado visible</small><strong>S/ <?= number_format($totalCotizado, 2) ?></strong></div>
    <div class="card"><small>Módulo</small><strong>CRM</strong></div>
</div>

<div class="panel">
    <div class="panel-header"><h3>Listado de clientes</h3></div>

    <?php if(!erp_es_admin_global()): ?>
        <div class="crm-note">
            Puedes ver todos los clientes registrados para atención comercial. Los montos, cotizaciones y pedidos solo muestran operaciones de tus categorías permitidas: <?= htmlspecialchars(erp_scope_resumen_comercial($pdo)) ?>.
        </div>
    <?php endif; ?>

    <form class="filter-box" method="GET">
        <input name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar por nombre, DNI/RUC, celular o correo">
        <button class="btn" type="submit">Buscar</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th><th>Cliente</th><th>DNI/RUC</th><th>Celular</th><th>Correo</th><th>Ofertas</th><th>Cotizaciones visibles</th><th>Total visible</th><th>Última visible</th><th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($clientes as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['nombre']) ?></td>
                <td><?= htmlspecialchars($c['documento']) ?></td>
                <td><?= htmlspecialchars($c['celular']) ?></td>
                <td><?= htmlspecialchars($c['correo'] ?? '-') ?></td>
                <td>
                    <?php $acepta = (int)($c['acepta_ofertas'] ?? 0); ?>
                    <span class="consent <?= $acepta ? 'yes' : 'no' ?>"><?= $acepta ? 'Sí acepta' : 'No indicado' ?></span>
                </td>
                <td><?= $c['total_cotizaciones'] ?></td>
                <td>S/ <?= number_format($c['total_cotizado'], 2) ?></td>
                <td><?= $c['ultima_cotizacion'] ?? '-' ?></td>
                <td><a class="btn gray" href="cliente_ver.php?id=<?= $c['id'] ?>">Ver</a></td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($clientes) === 0): ?>
            <tr><td colspan="10">No hay clientes registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_footer(); ?>

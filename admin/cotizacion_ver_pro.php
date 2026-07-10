<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

function columnasCot($pdo){
    $cols = [];
    foreach($pdo->query("DESCRIBE cotizaciones")->fetchAll(PDO::FETCH_ASSOC) as $r){
        $cols[$r['Field']] = true;
    }
    return $cols;
}

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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE id=?");
$stmt->execute([$id]);
$cot = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$cot){ die("Cotización no encontrada"); }
if(!erp_cotizacion_en_scope($pdo, $id)){ http_response_code(403); die('Acceso restringido. Esta cotización contiene productos fuera de tus categorías permitidas.'); }

$cols = columnasCot($pdo);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $estado = $_POST['estado'] ?? 'Pendiente de revisión';
    if(!in_array($estado, $estadosPedido, true)){
        $estado = 'Pendiente de revisión';
    }

    $datos = [
        'estado' => $estado,
        'pago_validado' => isset($_POST['pago_validado']) ? 1 : 0,
        'fecha_entrega_estimada' => trim($_POST['fecha_entrega_estimada'] ?? '') ?: null,
        'tracking_observacion' => trim($_POST['tracking_observacion'] ?? ''),
        'tracking_actualizado_en' => date('Y-m-d H:i:s')
    ];

    if($estado === 'Pago recibido' && empty($cot['fecha_pago_validado'])){
        $datos['fecha_pago_validado'] = date('Y-m-d H:i:s');
        $datos['pago_validado'] = 1;
    }

    if($estado === 'Pedido aceptado' && empty($cot['fecha_pago_validado']) && isset($_POST['pago_validado'])){
        $datos['fecha_pago_validado'] = date('Y-m-d H:i:s');
    }

    if($estado === 'Salió de tienda' && empty($cot['fecha_salida'])){
        $datos['fecha_salida'] = date('Y-m-d H:i:s');
    }

    if($estado === 'Entregado' && empty($cot['fecha_entregado'])){
        $datos['fecha_entregado'] = date('Y-m-d H:i:s');
    }

    $sets = [];
    $vals = [];
    foreach($datos as $k=>$v){
        if(isset($cols[$k])){
            $sets[] = "$k=?";
            $vals[] = $v;
        }
    }

    if($sets){
        $vals[] = $id;
        $stmt = $pdo->prepare("UPDATE cotizaciones SET ".implode(',', $sets)." WHERE id=?");
        $stmt->execute($vals);
        erp_auditoria($pdo, 'cotizaciones', 'editar', 'Actualizó estado/tracking de cotización a: ' . $estado, 'cotizaciones', $id);
    }

    if(isset($_POST['descontar_stock'])){
        $stmt = $pdo->prepare("SELECT producto_id,cantidad FROM cotizacion_detalle WHERE cotizacion_id=?");
        $stmt->execute([$id]);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $d){
            $upd = $pdo->prepare("UPDATE productos SET stock = GREATEST(stock - ?, 0) WHERE id=?");
            $upd->execute([(int)$d['cantidad'], (int)$d['producto_id']]);
        }
        erp_auditoria($pdo, 'inventario', 'editar', 'Descontó stock desde cotización', 'cotizaciones', $id);
    }

    header("Location: cotizacion_ver_pro.php?id=".$id."&ok=1");
    exit;
}

$stmt = $pdo->prepare("
SELECT d.*, p.nombre, p.imagen_principal, p.stock
FROM cotizacion_detalle d
LEFT JOIN productos p ON d.producto_id = p.id
WHERE d.cotizacion_id=?
ORDER BY d.id ASC
");
$stmt->execute([$id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach($detalles as $d){
    $total += (float)($d['subtotal'] ?? ((float)$d['precio'] * (int)$d['cantidad']));
}

$estadoActual = $cot['estado'] ?? 'Pendiente de revisión';
admin_header("Ver cotización", "cotizaciones");
?>

<style>
.grid{display:grid;grid-template-columns:1.2fr .8fr;gap:20px}.detail-row{display:grid;grid-template-columns:70px 1fr 100px 120px;gap:14px;align-items:center;border-bottom:1px solid #e5e7eb;padding:14px 0}.detail-row img{width:70px;height:70px;border-radius:12px;object-fit:cover;background:#f8fafc}.total{font-size:26px;color:#2563eb;font-weight:900;text-align:right;margin-top:18px}.info-box{background:#f8fafc;border-radius:14px;padding:14px;margin-bottom:12px}.info-box strong{display:block;color:#475569;font-size:13px;margin-bottom:4px}.badge{display:inline-block;background:#e0f2fe;color:#075985;border-radius:999px;padding:7px 12px;font-weight:bold}.tracking-admin{background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:16px;margin:16px 0}.tracking-steps{display:grid;gap:10px;margin-top:12px}.tracking-step{display:flex;gap:10px;align-items:flex-start;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:10px}.tracking-step.done{border-color:#86efac;background:#f0fdf4}.tracking-step.current{border-color:#93c5fd;background:#eff6ff}.tracking-dot{width:26px;height:26px;border-radius:50%;background:#cbd5e1;display:flex;align-items:center;justify-content:center;font-weight:900;color:white;flex-shrink:0}.tracking-step.done .tracking-dot{background:#16a34a}.tracking-step.current .tracking-dot{background:#2563eb}.form-control{width:100%;padding:12px;border:1px solid #d8dee9;border-radius:10px;margin-top:6px}.estado-pendiente-de-revision{background:#fef3c7;color:#92400e}.estado-esperando-pago{background:#ffedd5;color:#9a3412}.estado-pago-recibido,.estado-pedido-aceptado{background:#dcfce7;color:#166534}.estado-preparando-pedido{background:#dbeafe;color:#1e40af}.estado-salio-de-tienda,.estado-en-camino{background:#e0f2fe;color:#075985}.estado-entregado{background:#dcfce7;color:#166534}.estado-cancelado{background:#fee2e2;color:#991b1b}@media(max-width:900px){.grid{grid-template-columns:1fr}.detail-row{grid-template-columns:60px 1fr}}
</style>

<div class="panel">
    <div class="panel-header">
        <div>
            <h3>Cotización <?= h($cot['numero'] ?? '#'.$cot['id']) ?></h3>
            <span class="badge estado-<?= h(estadoClass($estadoActual)) ?>"><?= h($estadoActual) ?></span>
        </div>
        <a class="btn gray" href="cotizaciones.php">Volver</a>
    </div>

    <?php if(isset($_GET['ok'])): ?>
        <div style="background:#dcfce7;color:#166534;padding:14px;border-radius:12px;margin-bottom:18px;font-weight:bold;">Cambios guardados correctamente.</div>
    <?php endif; ?>

    <div class="grid">
        <div>
            <h3>Productos</h3>
            <?php foreach($detalles as $d): ?>
                <div class="detail-row">
                    <img src="../<?= h($d['imagen_principal'] ?: 'img/banners/slide-tecnologia.svg') ?>">
                    <div><strong><?= h($d['nombre'] ?: ($d['producto_nombre'] ?? 'Producto')) ?></strong><br><small>Stock actual: <?= (int)$d['stock'] ?></small></div>
                    <div>Cant: <?= (int)$d['cantidad'] ?></div>
                    <div><strong>S/ <?= number_format((float)($d['subtotal'] ?? 0), 2) ?></strong></div>
                </div>
            <?php endforeach; ?>
            <div class="total">Total: S/ <?= number_format($total, 2) ?></div>

            <div class="tracking-admin">
                <h3>Tracking del pedido</h3>
                <p style="color:#64748b;margin-top:5px;">El cliente verá este avance desde Mi cuenta &gt; Mis pedidos.</p>
                <div class="tracking-steps">
                    <?php $actualIndex = array_search($estadoActual, $estadosPedido, true); if($actualIndex === false) $actualIndex = 0; ?>
                    <?php foreach($estadosPedido as $i=>$e): ?>
                        <?php if($e === 'Cancelado' && $estadoActual !== 'Cancelado') continue; ?>
                        <div class="tracking-step <?= $estadoActual === 'Cancelado' && $e === 'Cancelado' ? 'current' : ($i < $actualIndex ? 'done' : ($i === $actualIndex ? 'current' : '')) ?>">
                            <div class="tracking-dot"><?= $i < $actualIndex ? '✓' : ($i+1) ?></div>
                            <div><strong><?= h($e) ?></strong></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div>
            <h3>Cliente</h3>
            <div class="info-box"><strong>Nombre</strong><?= h($cot['nombre_cliente'] ?? $cot['nombre_completo'] ?? $cot['nombre'] ?? '') ?></div>
            <div class="info-box"><strong>Documento</strong><?= h($cot['documento'] ?? $cot['dni_ruc'] ?? '') ?></div>
            <div class="info-box"><strong>Celular</strong><?= h($cot['celular'] ?? $cot['telefono'] ?? '') ?></div>
            <div class="info-box"><strong>Correo</strong><?= h($cot['correo'] ?? '') ?></div>
            <div class="info-box"><strong>Entrega</strong><?= h($cot['tipo_entrega'] ?? $cot['entrega'] ?? '') ?></div>
            <div class="info-box"><strong>Dirección</strong><?= h($cot['direccion'] ?? $cot['destino'] ?? '') ?></div>
            <div class="info-box"><strong>Observaciones</strong><?= nl2br(h($cot['observaciones'] ?? $cot['comentarios'] ?? '')) ?></div>

            <form method="POST">
                <label><strong>Estado del pedido</strong></label>
                <select name="estado" class="form-control">
                    <?php foreach($estadosPedido as $e): ?>
                        <option value="<?= h($e) ?>" <?= $estadoActual === $e ? 'selected' : '' ?>><?= h($e) ?></option>
                    <?php endforeach; ?>
                </select>

                <br>
                <label><input type="checkbox" name="pago_validado" value="1" <?= !empty($cot['pago_validado']) ? 'checked' : '' ?>> Pago validado</label>

                <br><br>
                <label><strong>Fecha/hora aproximada de llegada</strong></label>
                <input class="form-control" type="datetime-local" name="fecha_entrega_estimada" value="<?= !empty($cot['fecha_entrega_estimada']) ? h(date('Y-m-d\\TH:i', strtotime($cot['fecha_entrega_estimada']))) : '' ?>">

                <br>
                <label><strong>Mensaje visible para el cliente</strong></label>
                <textarea class="form-control" name="tracking_observacion" rows="4" placeholder="Ejemplo: El pedido salió con el motorizado. Llegada aproximada 40 minutos."><?= h($cot['tracking_observacion'] ?? '') ?></textarea>

                <br>
                <label><input type="checkbox" name="descontar_stock" value="1"> Descontar stock al guardar</label>

                <br><br>
                <button class="btn green" type="submit">Guardar estado</button>
            </form>
        </div>
    </div>
</div>

<?php admin_footer(); ?>

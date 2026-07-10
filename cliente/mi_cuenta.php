<?php
require_once "../config/db.php";
require_once "cliente_common.php";
requerirCliente();

$cid = clienteId();

$stmt = $pdo->prepare("SELECT * FROM clientes_web WHERE id=?");
$stmt->execute([$cid]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $aceptaOfertas = isset($_POST['acepta_ofertas']) ? 1 : 0;
    $aceptaContacto = isset($_POST['acepta_contacto']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE clientes_web SET nombre=?, celular=?, documento=?, direccion=?, distrito=?, provincia=?, acepta_ofertas=?, acepta_contacto=?, fecha_consentimiento=? WHERE id=?");
    $stmt->execute([
        trim($_POST['nombre'] ?? ''),
        trim($_POST['celular'] ?? ''),
        trim($_POST['documento'] ?? ''),
        trim($_POST['direccion'] ?? ''),
        trim($_POST['distrito'] ?? ''),
        trim($_POST['provincia'] ?? ''),
        $aceptaOfertas,
        $aceptaContacto,
        ($aceptaOfertas || $aceptaContacto) ? date('Y-m-d H:i:s') : ($cliente['fecha_consentimiento'] ?? null),
        $cid
    ]);

    $_SESSION['cliente_web_nombre'] = trim($_POST['nombre'] ?? '');
    header("Location: mi_cuenta.php?ok=1");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM cotizaciones WHERE cliente_web_id=? OR correo=? ORDER BY id DESC LIMIT 10");
$stmt->execute([$cid, $cliente['correo']]);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
SELECT p.*, c.nombre AS categoria
FROM cliente_favoritos f
INNER JOIN productos p ON f.producto_id=p.id
LEFT JOIN categorias c ON p.categoria_id=c.id
WHERE f.cliente_id=?
ORDER BY f.id DESC
LIMIT 8
");
$stmt->execute([$cid]);
$favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi cuenta - Mica Store</title>
<link rel="stylesheet" href="cliente_style.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="../tienda_visual_v3.php"><span>M</span> Mica Store</a>
    <nav>
        <a href="../tienda_visual_v3.php">Tienda</a>
        <a href="mis_pedidos.php">Mis pedidos</a>
        <a href="favoritos.php">Favoritos</a>
        <a href="cliente_logout.php">Salir</a>
    </nav>
</header>

<main class="wrap">
    <h1>Mi cuenta</h1>
    <p>Bienvenido, <strong><?= h(clienteNombre()) ?></strong></p>
    <p><a style="display:inline-block;background:#0057d9;color:white;padding:12px 18px;border-radius:12px;font-weight:900" href="mis_pedidos.php">📦 Ver todos mis pedidos</a></p>

    <?php if(isset($_GET['ok'])): ?><div class="ok">Datos actualizados correctamente.</div><?php endif; ?>

    <div class="grid">
        <section class="card">
            <h2>Mis datos</h2>
            <form method="POST" class="form-grid">
                <div class="full">
                    <label>Nombre</label>
                    <input name="nombre" value="<?= h($cliente['nombre']) ?>">
                </div>
                <div>
                    <label>Correo</label>
                    <input value="<?= h($cliente['correo']) ?>" disabled>
                </div>
                <div>
                    <label>Celular</label>
                    <input name="celular" value="<?= h($cliente['celular']) ?>">
                </div>
                <div>
                    <label>DNI/RUC</label>
                    <input name="documento" value="<?= h($cliente['documento']) ?>">
                </div>
                <div class="full">
                    <label>Dirección</label>
                    <input name="direccion" value="<?= h($cliente['direccion']) ?>">
                </div>
                <div>
                    <label>Distrito</label>
                    <input name="distrito" value="<?= h($cliente['distrito']) ?>">
                </div>
                <div>
                    <label>Provincia</label>
                    <input name="provincia" value="<?= h($cliente['provincia']) ?>">
                </div>
                <div class="full" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px;">
                    <strong>Preferencias comerciales</strong>
                    <label style="display:flex;gap:10px;align-items:flex-start;font-weight:normal;margin-top:12px;">
                        <input type="checkbox" name="acepta_ofertas" value="1" style="width:auto;margin-top:3px;" <?= !empty($cliente['acepta_ofertas']) ? 'checked' : '' ?>>
                        <span>Deseo recibir ofertas, promociones y novedades de Mica Store.</span>
                    </label>
                    <label style="display:flex;gap:10px;align-items:flex-start;font-weight:normal;margin-top:10px;">
                        <input type="checkbox" name="acepta_contacto" value="1" style="width:auto;margin-top:3px;" <?= !empty($cliente['acepta_contacto']) ? 'checked' : '' ?>>
                        <span>Acepto que Mica Store me contacte para ofrecerme productos o servicios relacionados.</span>
                    </label>
                </div>
                <button>Guardar datos</button>
            </form>
        </section>

        <section class="card">
            <h2>Mis pedidos recientes</h2>
            <?php if(count($cotizaciones) === 0): ?>
                <p>Aún no tienes pedidos.</p>
            <?php else: ?>
                <?php foreach($cotizaciones as $c): ?>
                    <div class="item-line">
                        <div>
                            <strong><?= h($c['numero'] ?? 'COT-'.$c['id']) ?></strong><br>
                            <small><?= h($c['creado_en'] ?? '') ?></small>
                        </div>
                        <div>
                            <span class="badge"><?= h($c['estado'] ?? 'Pendiente de revisión') ?></span><br>
                            <strong>S/ <?= number_format((float)($c['total'] ?? 0), 2) ?></strong><br>
                            <a style="display:inline-block;margin-top:8px;color:#0057d9;font-weight:bold" href="pedido_ver.php?id=<?= (int)$c['id'] ?>">Ver estado</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <section class="card">
        <h2>Mis favoritos</h2>
        <?php if(count($favoritos) === 0): ?>
            <p>Aún no agregaste favoritos.</p>
        <?php else: ?>
            <div class="products">
                <?php foreach($favoritos as $p): ?>
                    <article>
                        <img src="../<?= h($p['imagen_principal'] ?: 'img/banners/slide-tecnologia.svg') ?>">
                        <small><?= h($p['categoria']) ?></small>
                        <h3><?= h($p['nombre']) ?></h3>
                        <strong>S/ <?= number_format((float)($p['precio_oferta'] > 0 ? $p['precio_oferta'] : $p['precio']), 2) ?></strong>
                        <a href="../producto_mysql.php?id=<?= (int)$p['id'] ?>">Ver producto</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>

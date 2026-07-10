<?php
require_once "config/db.php";
require_once "config/erp_core.php";
require_once "includes/v3/empresa_context.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

function tablaExistePublica($pdo, $tabla){
    try{
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabla]);
        return (bool)$stmt->fetchColumn();
    }catch(Exception $e){
        return false;
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
SELECT
    p.*,
    c.nombre AS categoria,
    c.slug AS categoria_slug,
    s.nombre AS subcategoria,
    s.slug AS subcategoria_slug,
    m.nombre AS marca, t.nombre AS tienda_nombre, t.slug AS tienda_slug, t.whatsapp AS tienda_whatsapp, t.logo AS tienda_logo
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN subcategorias s ON p.subcategoria_id = s.id
LEFT JOIN marcas m ON p.marca_id = m.id
LEFT JOIN marketplace_tiendas t ON t.id = p.tienda_id AND t.activo=1
WHERE p.id = ?
LIMIT 1
");
$stmt->execute([$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$producto){
    die("Producto no encontrado");
}

$tipoItem = $producto['tipo_item'] ?? 'producto';
$esServicio = $tipoItem === 'servicio';
$nombreItem = $esServicio ? 'servicio' : 'producto';
$textoDisponible = $esServicio ? 'Servicio disponible' : 'Producto disponible';
$waTexto = "Hola, deseo cotizar este " . $nombreItem . ": " . $producto['nombre'] . " - Precio S/ " . number_format((float)($producto['precio_oferta'] > 0 ? $producto['precio_oferta'] : $producto['precio']), 2);
$waNumero = preg_replace("/\D+", "", (string)($producto["tienda_whatsapp"] ?? ""));
if($waNumero === "") $waNumero = "51920137707";
$waUrl = "https://wa.me/".$waNumero."?text=" . urlencode($waTexto);

try{
    $sid = session_id();
    $stmt = $pdo->prepare("INSERT INTO producto_vistos (producto_id, session_id) VALUES (?, ?)");
    $stmt->execute([$id, $sid]);
}catch(Exception $e){}

$imagenes = [];
try{
    $stmt = $pdo->prepare("SELECT * FROM imagenes_producto WHERE producto_id = ? ORDER BY orden ASC, id ASC");
    $stmt->execute([$id]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

if(count($imagenes) === 0 && !empty($producto['imagen_principal'])){
    $imagenes[] = ['imagen' => $producto['imagen_principal']];
}

if(count($imagenes) === 0){
    $imagenes[] = ['imagen' => 'img/banners/slide-tecnologia.svg'];
}

/* NUEVO: Características del producto */
$caracteristicas = [];
try{
    if(tablaExistePublica($pdo, "producto_caracteristicas")){
        $stmt = $pdo->prepare("SELECT * FROM producto_caracteristicas WHERE producto_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){
            $txt = $r["texto"] ?? $r["caracteristica"] ?? $r["nombre"] ?? "";
            $txt = trim((string)$txt);
            if($txt !== ""){
                $caracteristicas[] = $txt;
            }
        }
    }
}catch(Exception $e){}

/* NUEVO: Características del producto */
$caracteristicas = [];
try{
    if(tablaExistePublica($pdo, "producto_caracteristicas")){
        $stmt = $pdo->prepare("
            SELECT *
            FROM producto_caracteristicas
            WHERE producto_id = ?
            ORDER BY orden ASC, id ASC
        ");
        $stmt->execute([$id]);

        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){
            $txt = trim($r["texto"] ?? "");
            if($txt !== ""){
                $caracteristicas[] = $txt;
            }
        }
    }
}catch(Exception $e){}

/* NUEVO: Preguntas frecuentes del producto */
$preguntas = [];
try{
    if(tablaExistePublica($pdo, "producto_preguntas")){
        $stmt = $pdo->prepare("SELECT * FROM producto_preguntas WHERE producto_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}catch(Exception $e){}

$precioFinal = $producto['precio_oferta'] > 0 ? $producto['precio_oferta'] : $producto['precio'];

$relacionados = [];
try{
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.nombre AS categoria,
            s.nombre AS subcategoria,
            m.nombre AS marca, t.nombre AS tienda_nombre, t.slug AS tienda_slug, t.whatsapp AS tienda_whatsapp, t.logo AS tienda_logo
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN subcategorias s ON p.subcategoria_id = s.id
        LEFT JOIN marcas m ON p.marca_id = m.id
        WHERE p.activo = 1
        AND p.id <> ?
        AND (
            p.categoria_id = ?
            OR p.subcategoria_id = ?
            OR p.marca_id = ?
        )
        ORDER BY p.id DESC
        LIMIT 4
    ");
    $stmt->execute([
        $id,
        $producto['categoria_id'] ?? 0,
        $producto['subcategoria_id'] ?? 0,
        $producto['marca_id'] ?? 0
    ]);
    $relacionados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

$vistos = [];
try{
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.*, c.nombre AS categoria, s.nombre AS subcategoria
        FROM producto_vistos pv
        INNER JOIN productos p ON pv.producto_id = p.id
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN subcategorias s ON p.subcategoria_id = s.id
        WHERE pv.session_id = ?
        AND p.id <> ?
        AND p.activo = 1
        ORDER BY pv.visto_en DESC
        LIMIT 4
    ");
    $stmt->execute([session_id(), $id]);
    $vistos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

function productoMiniCard($p){
    $precio = $p['precio_oferta'] > 0 ? $p['precio_oferta'] : $p['precio'];
    ob_start(); ?>
    <article class="rel-card">
        <img src="<?= h($p['imagen_principal'] ?: 'img/banners/slide-tecnologia.svg') ?>" alt="<?= h($p['nombre']) ?>">
        <small><?= h($p['categoria'] ?? '') ?></small>
        <h3><?= h($p['nombre']) ?></h3>
        <strong>S/ <?= number_format((float)$precio, 2) ?></strong>
        <a href="producto_mysql.php?id=<?= (int)$p['id'] ?>">Ver producto</a>
    </article>
    <?php return ob_get_clean();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= h($producto['nombre']) ?> - Mica Store</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#edf4fb;color:#07162f}
a{text-decoration:none;color:inherit}
.top{background:#07162f;color:white;height:38px;display:flex;align-items:center;justify-content:space-between;padding:0 40px;font-weight:bold;font-size:14px}
.header{background:white;height:105px;display:flex;align-items:center;justify-content:space-between;padding:0 45px;box-shadow:0 2px 12px rgba(15,23,42,.06)}
.brand{display:flex;align-items:center;gap:12px;font-weight:900;font-size:28px}
.brand span{width:54px;height:54px;border-radius:14px;background:#008ee6;color:white;display:flex;align-items:center;justify-content:center}
.header-actions{display:flex;gap:18px;font-weight:bold}
.header-actions a{padding:12px 16px;border-radius:12px;background:#f1f5f9}
.menu{height:54px;background:#111827;display:flex;align-items:center;justify-content:center;gap:0;position:sticky;top:0;z-index:999}
.menu a{height:54px;color:white;font-weight:bold;padding:0 18px;display:flex;align-items:center}
.menu a:hover{background:#0057d9}

.wrap{max-width:1220px;margin:35px auto;padding:0 25px}
.breadcrumb{font-size:14px;color:#64748b;margin-bottom:18px}
.product-layout{display:grid;grid-template-columns:1.05fr .95fr;gap:28px}
.card{background:white;border-radius:22px;box-shadow:0 10px 28px rgba(15,23,42,.09);padding:24px}
.gallery{display:grid;grid-template-columns:92px 1fr;gap:18px}
.thumbs{display:flex;flex-direction:column;gap:12px}
.thumbs button{border:2px solid transparent;background:white;border-radius:14px;padding:4px;cursor:pointer;height:78px}
.thumbs button.active{border-color:#0057d9}
.thumbs img{width:100%;height:100%;object-fit:cover;border-radius:10px}
.main-img{height:500px;border-radius:20px;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden}
.main-img img{width:100%;height:100%;object-fit:contain}
.badges{display:flex;gap:10px;margin-bottom:15px}
.badge{background:#0057d9;color:white;padding:8px 12px;border-radius:999px;font-size:13px;font-weight:bold}
.badge.red{background:#ef4444}
.badge.green{background:#16a34a}
.info h1{font-size:34px;margin:0 0 10px}
.meta{display:flex;flex-wrap:wrap;gap:10px;margin:15px 0;color:#475569}
.meta span{background:#f1f5f9;border-radius:999px;padding:8px 12px}
.price{font-size:38px;font-weight:900;color:#0057d9;margin:20px 0 5px}
.old{font-size:18px;color:#9ca3af;text-decoration:line-through;margin-left:12px}
.stock{font-weight:bold;color:#16a34a;margin:10px 0}
.stock.low{color:#ef4444}
.qty{display:flex;align-items:center;gap:10px;margin:24px 0}
.qty button{width:42px;height:42px;border:0;border-radius:12px;background:#e8eefb;font-size:22px;font-weight:bold;cursor:pointer}
.qty input{width:72px;height:42px;border:1px solid #d8dee9;border-radius:12px;text-align:center;font-size:18px;font-weight:bold}
.actions{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:20px}
.actions button,.actions a{border:0;border-radius:14px;padding:16px 18px;font-weight:900;font-size:16px;cursor:pointer;text-align:center}
.add{background:#22c55e;color:white}
.wa{background:#111827;color:white}
.side-box{margin-top:18px;border:1px solid #e5e7eb;border-radius:16px;padding:16px;background:#f8fafc}
.side-box div{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e5e7eb}
.side-box div:last-child{border-bottom:0}
.tabs{margin-top:28px;background:white;border-radius:22px;box-shadow:0 10px 28px rgba(15,23,42,.09);overflow:hidden}
.tab-head{display:flex;border-bottom:1px solid #e5e7eb;background:#f8fafc;flex-wrap:wrap}
.tab-head button{border:0;background:transparent;padding:18px 24px;font-weight:900;cursor:pointer}
.tab-head button.active{background:white;color:#0057d9;border-top:4px solid #0057d9}
.tab-body{padding:26px;display:none;line-height:1.7}
.tab-body.active{display:block}
.spec-table{width:100%;border-collapse:collapse}
.spec-table td{padding:12px;border-bottom:1px solid #e5e7eb}
.spec-table td:first-child{font-weight:bold;width:260px;background:#f8fafc}
.feature-list{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin:0;padding:0;list-style:none}
.feature-list li{background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px;font-weight:bold}
.faq-item{border:1px solid #e5e7eb;border-radius:16px;margin-bottom:12px;background:#f8fafc;overflow:hidden}
.faq-q{padding:16px;font-weight:900;background:white}
.faq-a{padding:16px;color:#475569;border-top:1px solid #e5e7eb}
.section-title{margin:36px 0 18px;font-size:28px}
.rel-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.rel-card{background:white;border-radius:18px;box-shadow:0 8px 22px rgba(15,23,42,.08);padding:14px}
.rel-card img{width:100%;height:170px;object-fit:cover;border-radius:14px;background:#f8fafc}
.rel-card small{display:block;color:#64748b;margin-top:10px}
.rel-card h3{font-size:17px;min-height:44px}
.rel-card strong{color:#0057d9;font-size:20px}
.rel-card a{display:block;background:#111827;color:white;text-align:center;padding:11px;border-radius:12px;margin-top:12px;font-weight:bold}
.float-wa{position:fixed;right:26px;bottom:26px;background:#22c55e;color:white;border-radius:999px;padding:16px 22px;font-weight:900;box-shadow:0 12px 28px rgba(34,197,94,.35);z-index:999}
.toast{position:fixed;top:25px;right:25px;background:#16a34a;color:white;padding:14px 20px;border-radius:12px;font-weight:bold;z-index:9999}
@media(max-width:900px){
    .product-layout{grid-template-columns:1fr}
    .gallery{grid-template-columns:1fr}
    .thumbs{flex-direction:row;overflow:auto}
    .thumbs button{min-width:80px}
    .rel-grid{grid-template-columns:repeat(2,1fr)}
    .feature-list{grid-template-columns:1fr}
    .header{height:auto;padding:18px;gap:16px;flex-direction:column}
}
@media(max-width:600px){
    .rel-grid{grid-template-columns:1fr}
    .actions{grid-template-columns:1fr}
    .main-img{height:320px}
    .top{display:none}
}
</style>
</head>
<body>

<div class="top">
    <div>🚚 Envíos a todo el Perú &nbsp;&nbsp; 🛡️ Garantía en productos</div>
    <div>Facebook &nbsp;&nbsp; Instagram &nbsp;&nbsp; TikTok</div>
</div>

<header class="header">
    <a class="brand" href="<?= h(erp_url_empresa($GLOBALS['empresaSlugActual'] ?? null, 'tienda_visual_v3.php')) ?>"><span>M</span> Mica Store</a>
    <div class="header-actions">
        <a href="<?= h(erp_url_empresa($GLOBALS['empresaSlugActual'] ?? null, 'tienda_visual_v3.php')) ?>">Inicio</a>
        <a href="<?= h(erp_url_empresa($GLOBALS['empresaSlugActual'] ?? null, 'cotizacion_mysql.php')) ?>">🛒 Cotización <strong id="contadorCarrito">0</strong></a>
        <a href="<?= h($waUrl) ?>" target="_blank">💬 WhatsApp</a>
    </div>
</header>

<nav class="menu">
    <a href="<?= h(erp_url_empresa($GLOBALS['empresaSlugActual'] ?? null, 'tienda_visual_v3.php')) ?>">Inicio</a>
    <a href="<?= h(erp_url_empresa($GLOBALS['empresaSlugActual'] ?? null, 'tienda_visual_v3.php#productos')) ?>">Catálogo</a>
    <a href="<?= h(erp_url_empresa($GLOBALS['empresaSlugActual'] ?? null, 'tienda_visual_v3.php?categoria='.$producto['categoria_slug'])) ?>"><?= h($producto['categoria']) ?></a>
</nav>

<main class="wrap">
    <div class="breadcrumb">
        Inicio / <?= h($producto['categoria']) ?> / <?= h($producto['subcategoria']) ?> / <?= h($producto['nombre']) ?>
    </div>

    <section class="product-layout">
        <div class="card">
            <div class="gallery">
                <div class="thumbs">
                    <?php foreach($imagenes as $i => $img): ?>
                        <button class="<?= $i === 0 ? 'active' : '' ?>" onclick="cambiarImagen('<?= h($img['imagen']) ?>', this)">
                            <img src="<?= h($img['imagen']) ?>" alt="Imagen <?= $i+1 ?>">
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="main-img">
                    <img id="imagenPrincipal" src="<?= h($imagenes[0]['imagen']) ?>" alt="<?= h($producto['nombre']) ?>">
                </div>
            </div>
        </div>

        <div class="card info">
            <div class="badges">
                <?php if(!empty($producto['oferta'])): ?><span class="badge red">OFERTA</span><?php endif; ?>
                <?php if(!empty($producto['nuevo'])): ?><span class="badge">NUEVO</span><?php endif; ?>
                <span class="badge green"><?= h($textoDisponible) ?></span>
            </div>

            <h1><?= h($producto['nombre']) ?></h1>

            <?php if(!empty($producto['tienda_nombre'])): ?><div class="side-box" style="margin-bottom:14px"><strong>🏬 Vendido por <a href="tienda_publica.php?slug=<?= h($producto['tienda_slug']) ?>" style="color:inherit;text-decoration:underline"><?= h($producto['tienda_nombre']) ?></a></strong><?php if(!empty($producto['tienda_whatsapp'])): ?><div><span>WhatsApp</span><b><?= h($producto['tienda_whatsapp']) ?></b></div><?php endif; ?></div><?php endif; ?>
<div class="meta">
                <?php if(!$esServicio): ?>
                    <span>Marca: <?= h($producto['marca'] ?: 'Sin marca') ?></span>
                <?php endif; ?>
                <span>Categoría: <?= h($producto['categoria']) ?></span>
                <?php if($producto['subcategoria']): ?><span>Subcategoría: <?= h($producto['subcategoria']) ?></span><?php endif; ?>
                <?php if($esServicio): ?><span>Tipo: Servicio</span><?php endif; ?>
            </div>

            <div class="price">
                S/ <?= number_format((float)$precioFinal, 2) ?>
                <?php if($producto['precio_oferta'] > 0): ?>
                    <span class="old">S/ <?= number_format((float)$producto['precio'], 2) ?></span>
                <?php endif; ?>
            </div>

            <?php if($esServicio): ?>
                <div class="stock">
                    ✔ Servicio disponible
                </div>
            <?php else: ?>
                <div class="stock <?= $producto['stock'] <= 0 ? 'low' : '' ?>">
                    <?= $producto['stock'] > 0 ? '✔ Stock disponible: '.(int)$producto['stock'] : 'Sin stock disponible' ?>
                </div>
            <?php endif; ?>

            <p><?= h($producto['descripcion_corta'] ?? '') ?></p>

            <?php if(!$esServicio): ?>
                <div class="qty">
                    <button type="button" onclick="cambiarQty(-1)">-</button>
                    <input id="cantidad" value="1" readonly>
                    <button type="button" onclick="cambiarQty(1)">+</button>
                </div>
            <?php endif; ?>

            <div class="actions">
                <button class="add" onclick="agregarCotizacionDetalle()"><?= $esServicio ? "Solicitar servicio" : "Agregar a cotización" ?></button>
                <a class="wa" target="_blank" href="<?= h($waUrl) ?>"><?= $esServicio ? "Solicitar por WhatsApp" : "Cotizar por WhatsApp" ?></a>
            </div>

            <div class="side-box">
                <?php if($esServicio): ?>
                    <?php if(!empty($producto['duracion_servicio'])): ?>
                        <div><strong>Duración</strong><span><?= h($producto['duracion_servicio']) ?></span></div>
                    <?php endif; ?>

                    <?php if(!empty($producto['modalidad_servicio'])): ?>
                        <div><strong>Modalidad</strong><span><?= h($producto['modalidad_servicio']) ?></span></div>
                    <?php endif; ?>

                    <div><strong>Condición</strong><span><?= h($producto['garantia'] ?: 'Previa coordinación') ?></span></div>
                    <div><strong>Atención</strong><span>Coordinada por WhatsApp</span></div>
                <?php else: ?>
                    <div><strong>Código</strong><span><?= h($producto['codigo'] ?? '-') ?></span></div>
                    <div><strong>SKU</strong><span><?= h($producto['sku'] ?? '-') ?></span></div>
                    <div><strong>Garantía</strong><span><?= h($producto['garantia'] ?: 'Consultar') ?></span></div>
                    <div><strong>Entrega</strong><span>Recojo / Envío coordinado</span></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="tabs">
        <div class="tab-head">
            <button class="active" onclick="abrirTab('desc', this)">Descripción</button>

            <?php if(!empty($producto['ficha_tecnica'])): ?>
                <button onclick="abrirTab('spec', this)">Ficha técnica</button>
            <?php endif; ?>

            <?php if(count($caracteristicas) > 0): ?>
                <button onclick="abrirTab('caracteristicas', this)">Características</button>
            <?php endif; ?>

            <?php if(count($preguntas) > 0): ?>
                <button onclick="abrirTab('preguntas', this)">Preguntas frecuentes</button>
            <?php endif; ?>

            <button onclick="abrirTab('garantia', this)">Garantía y entrega</button>
        </div>

        <div id="desc" class="tab-body active">
            <?= nl2br(h($producto['descripcion_larga'] ?: $producto['descripcion_corta'] ?: 'Descripción pendiente de completar.')) ?>
        </div>

        <?php if(!empty($producto['ficha_tecnica'])): ?>
            <div id="spec" class="tab-body">
                <?php $lineas = preg_split("/\r\n|\n|\r/", $producto['ficha_tecnica']); ?>
                <table class="spec-table">
                    <?php foreach($lineas as $linea): ?>
                        <?php
                            $partes = explode(":", $linea, 2);
                            $k = trim($partes[0] ?? '');
                            $v = trim($partes[1] ?? '');
                            if($k === '') continue;
                        ?>
                        <tr>
                            <td><?= h($k) ?></td>
                            <td><?= h($v ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <?php if(!empty($producto['pdf_ficha'])): ?>
                    <p style="margin-top:18px;">
                        <a target="_blank" style="color:#0057d9;font-weight:bold;" href="<?= h($producto['pdf_ficha']) ?>">
                            📄 Descargar ficha técnica PDF
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if(count($caracteristicas) > 0): ?>
            <div id="caracteristicas" class="tab-body">
                <ul class="feature-list">
                    <?php foreach($caracteristicas as $c): ?>
                        <li>✅ <?= h($c) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if(count($preguntas) > 0): ?>
            <div id="preguntas" class="tab-body">
                <?php foreach($preguntas as $p): ?>
                    <div class="faq-item">
                        <div class="faq-q">❓ <?= h($p['pregunta'] ?? '') ?></div>
                        <div class="faq-a"><?= nl2br(h($p['respuesta'] ?? '')) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div id="garantia" class="tab-body">
            <?php if($esServicio): ?>
                <p><strong>Condición:</strong> <?= h($producto['garantia'] ?: 'Servicio previa coordinación.') ?></p>
                <p>El precio, disponibilidad y horario se confirman por WhatsApp antes de agendar el servicio.</p>
                <p>La atención puede variar según ubicación, modalidad y disponibilidad.</p>
            <?php else: ?>
                <p><strong>Garantía:</strong> <?= h($producto['garantia'] ?: 'Consultar con el vendedor.') ?></p>
                <p>Los precios, stock y condiciones de entrega se confirman por WhatsApp antes de cerrar la cotización.</p>
                <p>Envíos a provincia previa coordinación.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if(count($relacionados) > 0): ?>
        <h2 class="section-title"><?= $esServicio ? "Servicios relacionados" : "Productos relacionados" ?></h2>
        <section class="rel-grid">
            <?php foreach($relacionados as $p): ?>
                <?= productoMiniCard($p) ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if(count($vistos) > 0): ?>
        <h2 class="section-title">Vistos recientemente</h2>
        <section class="rel-grid">
            <?php foreach($vistos as $p): ?>
                <?= productoMiniCard($p) ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<a class="float-wa" href="<?= h($waUrl) ?>" target="_blank">💬 Cotizar por WhatsApp</a>

<script>
const PRODUCTO_ID = <?= (int)$producto['id'] ?>;
const PRODUCTO_NOMBRE = "<?= h(addslashes($producto['nombre'])) ?>";
const ES_SERVICIO = <?= $esServicio ? 'true' : 'false' ?>;
let MICA_CART_KEY_DETALLE = "mica_cart_invitado";

async function resolverCartKeyDetalle(){
    try{
        const res = await fetch("cliente/session_api.php", {cache:"no-store"});
        const data = await res.json();
        if(data.ok && data.cart_key){
            MICA_CART_KEY_DETALLE = data.cart_key;
        }
    }catch(e){
        MICA_CART_KEY_DETALLE = "mica_cart_invitado";
    }
}

function cambiarImagen(src, btn){
    document.getElementById("imagenPrincipal").src = src;
    document.querySelectorAll(".thumbs button").forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
}

function cambiarQty(n){
    const input = document.getElementById("cantidad");
    let val = Number(input.value || 1) + n;
    if(val < 1) val = 1;
    input.value = val;
}

function actualizarContador(){
    let cart = JSON.parse(localStorage.getItem(MICA_CART_KEY_DETALLE) || "[]");
    let total = cart.reduce((s, p) => s + Number(p.qty || 0), 0);
    const c = document.getElementById("contadorCarrito");
    if(c) c.textContent = total;
}

function agregarCotizacionDetalle(){
    let qty = ES_SERVICIO ? 1 : Number((document.getElementById("cantidad") ? document.getElementById("cantidad").value : 1) || 1);
    let cart = JSON.parse(localStorage.getItem(MICA_CART_KEY_DETALLE) || "[]");
    const item = cart.find(p => Number(p.id) === PRODUCTO_ID);

    if(item){ item.qty += qty; }
    else{ cart.push({id: PRODUCTO_ID, nombre: PRODUCTO_NOMBRE, qty: qty}); }

    localStorage.setItem(MICA_CART_KEY_DETALLE, JSON.stringify(cart));
    actualizarContador();

    let toast = document.createElement("div");
    toast.className = "toast";
    toast.textContent = ES_SERVICIO ? "✅ Servicio agregado a cotización" : "✅ Producto agregado a cotización";
    document.body.appendChild(toast);
    setTimeout(()=>toast.remove(), 2200);
}

function abrirTab(id, btn){
    document.querySelectorAll(".tab-body").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".tab-head button").forEach(b => b.classList.remove("active"));
    document.getElementById(id).classList.add("active");
    btn.classList.add("active");
}

resolverCartKeyDetalle().then(actualizarContador);
</script>

</body>
</html>

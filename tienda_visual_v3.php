<?php
require_once "config/db.php";
require_once "config/erp_core.php";
require_once "includes/v3/common.php";
require_once "includes/v3/contacto_widget.php";

// Contexto de empresa: si la URL viene de /la-chacra/ (o ?empresa=la-chacra),
// esta página se vuelve el sitio propio de esa empresa: su config, sus tiendas,
// sus productos. Sin ese parámetro, sigue funcionando como el sitio global de siempre.
$empresaSlug = trim($_GET['empresa'] ?? '');
$empresaActual = $empresaSlug !== '' ? erp_empresa_por_slug($pdo, $empresaSlug) : null;
$empresaId = $empresaActual['id'] ?? 0;
$GLOBALS['empresaSlugActual'] = $empresaActual ? $empresaSlug : null;

$buscar = $_GET['buscar'] ?? '';
$categoria = $_GET['categoria'] ?? '';

$categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$productos = obtenerProductosV3($pdo, $categoria, 100, $buscar, $empresaId);

$sliders = [];
try{
    $sliders = $pdo->query("SELECT * FROM sliders WHERE activo = 1 ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

if(count($sliders) === 0){
    $sliders = [[
        'titulo'=>$empresaActual ? strtoupper($empresaActual['nombre']) : 'TECNOLOGÍA QUE',
        'titulo_resaltado'=>$empresaActual ? 'TE ESPERA' : 'CONECTA',
        'subtitulo'=>$empresaActual ? ('Descubre las tiendas de '.$empresaActual['nombre'].'.') : 'Laptops, licencias, tóners, redes y servicios técnicos.',
        'texto_boton'=>'Ver tecnología',
        'url_boton'=>'tienda_visual_v3.php?categoria=tecnologia',
        'imagen'=>'',
        'color_inicio'=>'#020817',
        'color_fin'=>'#001b47',
        'color_resaltado'=>'#37c5ff'
    ]];
}

$bloques = [];
try{
    $bloques = $pdo->query("SELECT * FROM home_bloques WHERE activo = 1 ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}


function micaHomeConfig($pdo, $clave, $default='', $empresaId=0){
    try{
        static $cache = [];
        $key = $empresaId.'|'.$clave;
        if(isset($cache[$key])) return $cache[$key];
        $cfg = erp_config_empresa($pdo, $empresaId);
        return $cache[$key] = ($cfg[$clave] ?? $default);
    }catch(Exception $e){ return $default; }
}

$tituloPagina = "Productos destacados";
foreach($categorias as $cat){
    if($cat['slug'] === $categoria){
        $tituloPagina = $cat['nombre'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= h($tituloPagina) ?> - <?= h($empresaActual['nombre'] ?? micaHomeConfig($pdo,'nombre_comercial','Mica Store',$empresaId)) ?></title>

<link rel="stylesheet" href="includes/v3/store_v3.css">
<link rel="stylesheet" href="includes/v3/login_modal.css">
<link rel="stylesheet" href="includes/v3/header_cliente.css">

</head>
<body>

<?php require "includes/v3/topbar.php"; ?>
<?php require "includes/v3/header.php"; ?>
<?php require "includes/v3/menu.php"; ?>
<?php require "includes/v3/slider.php"; ?>
<?php require "includes/v3/services.php"; ?>

<main class="v3-container">
    <div class="v3-section-head" id="productos">
        <h2><?= h($tituloPagina) ?></h2>
        <span><?= count($productos) ?> producto(s)</span>
    </div>


    <section class="v3-product-grid">
        <?php foreach($productos as $p): ?>
            <?= productoCardV3($p) ?>
        <?php endforeach; ?>

        <?php if(count($productos) === 0): ?>
            <p>No hay productos registrados todavía.</p>
        <?php endif; ?>
    </section>

    <section class="v3-stats">
        <div class="v3-stat"><strong>+500</strong><span>Productos referenciales</span></div>
        <div class="v3-stat"><strong><?= count($categorias) ?></strong><span>Líneas de negocio</span></div>
        <div class="v3-stat"><strong>24/7</strong><span>Atención por WhatsApp</span></div>
        <div class="v3-stat"><strong>Perú</strong><span>Envíos a provincia</span></div>
    </section>

    <?php if(count($bloques) > 0): ?>
        <section class="v3-blocks">
            <?php foreach($bloques as $b): ?>
                <?php if($b['tipo'] === 'html'): ?>
                    <?= $b['contenido'] ?>
                <?php elseif($b['tipo'] === 'banner'): ?>
                    <div class="v3-block-banner">
                        <h2><?= h($b['titulo']) ?></h2>
                        <p><?= h($b['contenido']) ?></p>
                    </div>
                <?php else: ?>
                    <div class="v3-block-info">
                        <h3><?= h($b['titulo']) ?></h3>
                        <p><?= h($b['contenido']) ?></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if(micaHomeConfig($pdo, 'newsletter_activo', '1', $empresaId) === '1'): ?>
        <section class="v3-newsletter-lite">
            <div>
                <h2><?= h(micaHomeConfig($pdo, 'newsletter_titulo', 'Novedades y ofertas', $empresaId)) ?></h2>
                <p><?= h(micaHomeConfig($pdo, 'newsletter_texto', 'Déjanos tu correo para recibir promociones y nuevos ingresos.', $empresaId)) ?></p>
            </div>
            <a href="contacto.php">Quiero recibir ofertas</a>
        </section>
    <?php endif; ?>

    <?php if(micaHomeConfig($pdo, 'home_mostrar_contacto', '1', $empresaId) === '1'): ?>
        <?= contactoWidgetV3($pdo, 'home') ?>
    <?php endif; ?>
</main>

<?php require "includes/v3/footer.php"; ?>

<?php require "includes/v3/login_modal.php"; ?>

<?php $waFlotante = preg_replace('/\D/','', micaHomeConfig($pdo,'whatsapp','',$empresaId)) ?: '51920137707'; ?>
<a class="v3-float-chat" target="_blank" href="https://wa.me/<?= h($waFlotante) ?>">💬</a>

<script>
window.MICA_CATEGORIA_ACTUAL = "<?= h($categoria) ?>";
</script>

<script src="includes/v3/store_v3.js"></script>
<script src="includes/v3/login_modal.js"></script>
<script src="includes/v3/header_cliente.js"></script>

</body>
</html>
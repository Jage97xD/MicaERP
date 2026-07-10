<?php
require_once __DIR__ . '/../../config/erp_core.php';
$empresaId = $empresaId ?? 0;
$empresaSlugActual = $GLOBALS['empresaSlugActual'] ?? null;
$configHeaderV3 = erp_config_empresa($pdo, $empresaId);

// Antes este ajuste no hacía nada: existía en Configuración pero el header
// ignoraba su valor. Ahora sí mueve el bloque logo+buscador+acciones de verdad.
$posicionHeader = $configHeaderV3['header_logo_posicion'] ?? 'izquierda';
$justifyHeader = ['izquierda'=>'flex-start','centro'=>'center','derecha'=>'flex-end'][$posicionHeader] ?? 'flex-start';

// Antes "Color principal / secundario" del admin no se aplicaba en ningún lado
// del sitio público (todo estaba en azul fijo). Ahora sí colorea la tienda real.
$colorPrincipal = trim($configHeaderV3['color_principal'] ?? '') ?: '#0057d9';
$colorSecundario = trim($configHeaderV3['color_secundario'] ?? '') ?: '#06b6d4';
echo '<style>:root{--v3-primary:'.h($colorPrincipal).';--v3-secondary:'.h($colorSecundario).';}</style>';

$builder = [];
try{
    $st = $pdo->prepare("
        SELECT *
        FROM store_builder
        WHERE visible = 1
        AND componente <> 'menu'
        AND empresa_id = ?
        ORDER BY orden ASC, id ASC
    ");
    $st->execute([$empresaId]);
    $builder = $st->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

function renderHeaderV3($b, $buscar){
    global $configHeaderV3, $empresaSlugActual;
    $comp = $b['componente'];
    $texto = h($b['texto'] ?? '');
    $url = trim($b['url'] ?? '');
    $home = erp_url_empresa($empresaSlugActual, 'tienda_visual_v3.php');

    $style = "
        left:".(int)$b['x']."px;
        top:".(int)$b['y']."px;
        width:".(int)$b['ancho']."px;
        height:".(int)$b['alto']."px;
        background:".h($b['color_fondo'] ?: 'transparent').";
        color:".h($b['color_texto'] ?: '#111827').";
    ";

    if($comp === 'buscador'){
        return '
        <form class="v3-header-item v3-search" style="'.$style.'" method="GET" action="'.h($home).'">
            <input name="buscar" value="'.h($buscar).'" placeholder="'.$texto.'">
            <button type="submit">🔍</button>
        </form>';
    }

    if($comp === 'logo'){
        $nombreTienda = trim($configHeaderV3['nombre_comercial'] ?? '') ?: trim($b['texto'] ?? '') ?: 'Mica Store';
        $logoRuta = trim($configHeaderV3['logo'] ?? '');
        $logoHtml = $logoRuta !== ''
            ? '<img class="v3-logo-img" src="'.h($logoRuta).'" alt="'.h($nombreTienda).'">'
            : '<span>'.h(mb_substr($nombreTienda,0,1)).'</span>';
        return '
        <a class="v3-header-item v3-logo" href="'.h($home).'" style="'.$style.'">
            '.$logoHtml.'
            <strong>'.h($nombreTienda).'</strong>
        </a>';
    }

    if($comp === 'cotizacion'){
        return '
        <a class="v3-header-item v3-link" href="'.h($url ?: 'cotizacion_mysql.php').'" style="'.$style.'">
            🛒 <span>'.$texto.'</span><strong id="contadorCarrito">0</strong>
        </a>';
    }

    if($comp === 'whatsapp'){
        $numero = preg_replace('/\D/','', $configHeaderV3['whatsapp'] ?? '') ?: '51920137707';
        $whatsappUrl = trim($url) !== '' ? $url : ('https://wa.me/'.$numero);
        return '
        <a class="v3-header-item v3-link v3-whatsapp" href="'.h($whatsappUrl).'" target="_blank" style="'.$style.'">
            💬 <span>'.$texto.'</span>
        </a>';
    }

    return '
    <a class="v3-header-item v3-link" href="'.h($url ?: '#').'" style="'.$style.'">
        <span>'.$texto.'</span>
    </a>';
}
?>

<header class="v3-header" style="justify-content:<?= $justifyHeader ?>">
    <?php foreach($builder as $b): ?>
        <?= renderHeaderV3($b, $buscar) ?>
    <?php endforeach; ?>
</header>

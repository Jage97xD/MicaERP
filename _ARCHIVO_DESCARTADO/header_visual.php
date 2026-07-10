<?php
if(!isset($pdo)){
    require_once __DIR__ . "/../config/db.php";
}

if (!function_exists('h')) {
    function h($v){
        return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
    }
}

$builder = [];
try{
    $stmt = $pdo->query("
        SELECT *
        FROM store_builder
        WHERE visible = 1
        AND componente <> 'menu'
        ORDER BY orden ASC, id ASC
    ");
    $builder = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){
    $builder = [];
}

function renderHeaderComponent($b, $buscarValor = ''){
    $comp = $b['componente'];
    $texto = h($b['texto'] ?? '');
    $url = trim($b['url'] ?? '');

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
        <form class="visual-comp visual-search" style="'.$style.'" method="GET" action="tienda_visual.php">
            <input name="buscar" value="'.h($buscarValor).'" placeholder="'.$texto.'">
            <button type="submit">🔍</button>
        </form>';
    }

    if($comp === 'logo'){
        $href = $url ?: 'tienda_visual.php';
        return '
        <a class="visual-comp visual-logo" href="'.h($href).'" style="'.$style.'">
            <span class="logo-square">M</span>
            <strong>'.h($b['texto'] ?: 'MICA STORE').'</strong>
        </a>';
    }

    if($comp === 'cotizacion'){
        $href = $url ?: 'cotizacion_mysql.php';
        return '
        <a class="visual-comp visual-link" href="'.h($href).'" style="'.$style.'">
            🛒 <span>'.$texto.'</span><strong id="contadorCarrito">0</strong>
        </a>';
    }

    if($comp === 'whatsapp'){
        $href = $url ?: 'https://wa.me/51920137707';
        return '
        <a class="visual-comp visual-link whatsapp-btn" target="_blank" href="'.h($href).'" style="'.$style.'">
            💬 <span>'.$texto.'</span>
        </a>';
    }

    if($comp === 'login'){
        $href = $url ?: '#';
        return '
        <a class="visual-comp visual-link" href="'.h($href).'" style="'.$style.'">
            <span>'.$texto.'</span>
        </a>';
    }

    $tag = $url ? 'a' : 'div';
    $href = $url ? ' href="'.h($url).'"' : '';
    return '<'.$tag.' class="visual-comp visual-link"'.$href.' style="'.$style.'">'.$texto.'</'.$tag.'>';
}
?>

<header class="builder-header">
    <?php foreach($builder as $b): ?>
        <?= renderHeaderComponent($b, $buscar ?? '') ?>
    <?php endforeach; ?>
</header>

<script>
function actualizarContadorBuilder(){
    let carrito = JSON.parse(localStorage.getItem("mica_cart_mysql") || "[]");
    let total = 0;
    carrito.forEach(i => total += Number(i.qty || 0));
    const c = document.getElementById("contadorCarrito");
    if(c) c.textContent = total;
}
actualizarContadorBuilder();
</script>

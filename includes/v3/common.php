<?php
require_once __DIR__ . '/../../config/erp_core.php';
if(!function_exists('h')){
    function h($v){
        return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
    }
}

if(!function_exists('slugDesdeUrl')){
    function slugDesdeUrl($url){
        if(!$url) return "";
        $parts = parse_url($url);
        if(empty($parts['query'])) return "";
        parse_str($parts['query'], $q);
        return $q['categoria'] ?? "";
    }
}

if(!function_exists('obtenerProductosV3')){
    function obtenerProductosV3($pdo, $catSlug = '', $limit = 100, $buscar = '', $empresaId = 0, $tiendaId = 0){
        $sql = "
        SELECT
            p.*,
            c.nombre AS categoria,
            c.slug AS categoria_slug,
            s.nombre AS subcategoria,
            m.nombre AS marca,
            t.nombre AS tienda_nombre,
            t.slug AS tienda_slug,
            t.whatsapp AS tienda_whatsapp
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN subcategorias s ON p.subcategoria_id = s.id
        LEFT JOIN marcas m ON p.marca_id = m.id
        LEFT JOIN marketplace_tiendas t ON p.tienda_id = t.id
        WHERE p.activo = 1
        ";

        $params = [];

        if($empresaId > 0){
            $sql .= " AND t.empresa_id = :empresa_id";
            $params[':empresa_id'] = $empresaId;
        }

        if($tiendaId > 0){
            $sql .= " AND p.tienda_id = :tienda_id";
            $params[':tienda_id'] = $tiendaId;
        }

        if($catSlug !== ''){
            $sql .= " AND c.slug = :categoria";
            $params[':categoria'] = $catSlug;
        }

        if($buscar !== ''){
            $sql .= " AND (
                p.nombre LIKE :buscar
                OR p.descripcion_corta LIKE :buscar
                OR m.nombre LIKE :buscar
                OR p.codigo LIKE :buscar
                OR p.sku LIKE :buscar
            )";
            $params[':buscar'] = "%$buscar%";
        }

        $sql .= " ORDER BY p.id DESC LIMIT " . (int)$limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if(!function_exists('productoCardV3')){
    function productoCardV3($p){
        ob_start(); ?>
        <article class="v3-product-card">
            <?php if(!empty($p['oferta'])): ?>
                <span class="v3-badge offer">OFERTA</span>
            <?php elseif(!empty($p['nuevo'])): ?>
                <span class="v3-badge">NUEVO</span>
            <?php endif; ?>

            <img src="<?= h($p['imagen_principal'] ?: 'img/banners/slide-tecnologia.svg') ?>" alt="<?= h($p['nombre']) ?>">

            <small><?= h($p['categoria'] ?? 'Sin categoría') ?><?= !empty($p['subcategoria']) ? ' · '.h($p['subcategoria']) : '' ?></small>
            <h3><?= h($p['nombre']) ?></h3>
            <?php if(!empty($p['tienda_nombre'])): ?><a href="tienda_publica.php?slug=<?= h($p['tienda_slug']) ?>" class="v3-vendedor">🏬 <?= h($p['tienda_nombre']) ?></a><?php endif; ?>
            <p><?= h($p['descripcion_corta'] ?? '') ?></p>

            <div class="v3-price">
                S/ <?= number_format((float)($p['precio_oferta'] > 0 ? $p['precio_oferta'] : $p['precio']), 2) ?>
                <?php if($p['precio_oferta'] > 0): ?>
                    <span>S/ <?= number_format((float)$p['precio'], 2) ?></span>
                <?php endif; ?>
            </div>

            <div class="v3-stock">✔ Stock: <?= (int)$p['stock'] ?></div>

            <div class="v3-product-actions">
                <a href="<?= h(erp_url_empresa($GLOBALS['empresaSlugActual'] ?? null, 'producto_mysql.php?id='.(int)$p['id'])) ?>">Ver detalle</a>
                <button type="button" onclick="agregarCotizacion(<?= (int)$p['id'] ?>, '<?= h(addslashes($p['nombre'])) ?>')">Agregar</button>
            </div>
        </article>
        <?php return ob_get_clean();
    }
}

if(!function_exists('micaConfigTodos')){
    function micaConfigTodos($pdo){
        $empresaId = $GLOBALS['empresaId'] ?? 0;
        return erp_config_empresa($pdo, $empresaId);
    }
}

if(!function_exists('micaConfigValor')){
    function micaConfigValor($pdo, $clave, $default=''){
        try{
            $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave=? LIMIT 1");
            $stmt->execute([$clave]);
            $valor = $stmt->fetchColumn();
            return $valor !== false ? $valor : $default;
        }catch(Exception $e){
            return $default;
        }
    }
}

if(!function_exists('micaCamposPersonalizados')){
    function micaCamposPersonalizados($pdo, $ubicacion){
        try{
            $stmt = $pdo->prepare("SELECT * FROM configuracion_campos WHERE ubicacion=? AND activo=1 ORDER BY orden ASC, id ASC");
            $stmt->execute([$ubicacion]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(Exception $e){
            return [];
        }
    }
}

?>
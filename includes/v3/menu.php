<?php
$empresaSlugActual = $GLOBALS['empresaSlugActual'] ?? null;
$menuItems = [];
try{
    $menuItems = $pdo->query("SELECT * FROM header_menu_items WHERE visible=1 ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

if(count($menuItems) === 0){
    $menuItems = [
        ['titulo'=>'Inicio','icono'=>'🏠','url'=>'tienda_visual_v3.php','tipo'=>'link','visible_desktop'=>1,'visible_mobile'=>1],
        ['titulo'=>'Productos','icono'=>'🛍','url'=>'tienda_visual_v3.php#productos','tipo'=>'link','visible_desktop'=>1,'visible_mobile'=>1],
        ['titulo'=>'Categorías','icono'=>'📂','url'=>'#categorias','tipo'=>'categorias','visible_desktop'=>1,'visible_mobile'=>1],
        ['titulo'=>'Marcas','icono'=>'🏷','url'=>'marcas.php','tipo'=>'link','visible_desktop'=>1,'visible_mobile'=>1],
        ['titulo'=>'Ofertas','icono'=>'⭐','url'=>'ofertas.php','tipo'=>'link','visible_desktop'=>1,'visible_mobile'=>1],
        ['titulo'=>'Mis pedidos','icono'=>'📦','url'=>'cliente/mis_pedidos.php','tipo'=>'link','visible_desktop'=>1,'visible_mobile'=>1],
        ['titulo'=>'Contáctenos','icono'=>'📞','url'=>'contacto.php','tipo'=>'link','visible_desktop'=>1,'visible_mobile'=>1],
    ];
}
?>
<nav class="v3-menu v3-menu-v2" id="menuPrincipal">
    <button class="v3-menu-toggle" type="button" onclick="toggleMenuV3()">☰ Menú</button>

    <div class="v3-menu-inner" id="menuV3Inner">
        <?php foreach($menuItems as $item): ?>
            <?php
                $tipo = $item['tipo'] ?? 'link';
                $titulo = $item['titulo'] ?? '';
                $icono = $item['icono'] ?? '';
                $url = $item['url'] ?? '#';
                $classes = [];
                if(empty($item['visible_desktop'])) $classes[] = 'hide-desktop';
                if(empty($item['visible_mobile'])) $classes[] = 'hide-mobile';
                $classAttr = implode(' ', $classes);
            ?>

            <?php if($tipo === 'categorias'): ?>
                <div class="v3-menu-dropdown <?= h($classAttr) ?>" id="categoriaDropdown">
                    <button class="v3-menu-dropbtn" type="button" onclick="toggleCategoriasV3(event)">
                        <?= h(trim(($icono ? $icono.' ' : '').$titulo)) ?> <span>▾</span>
                    </button>

                    <div class="v3-menu-dropdown-content">
                        <?php foreach($categorias as $cat): ?>
                            <a href="<?= h(erp_url_empresa($empresaSlugActual, 'tienda_visual_v3.php?categoria='.$cat['slug'])) ?>" data-slug="<?= h($cat['slug']) ?>" class="<?= $categoria === $cat['slug'] ? 'active' : '' ?>">
                                <?= h($cat['icono'] ?? '📦') ?> <?= h($cat['nombre']) ?>
                            </a>
                        <?php endforeach; ?>

                        <a href="<?= h(erp_url_empresa($empresaSlugActual, 'tienda_visual_v3.php#productos')) ?>" class="v3-menu-all">📦 Todas las categorías</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= h(erp_url_empresa($empresaSlugActual, $url)) ?>" class="<?= h($classAttr) ?> <?= ($url==='tienda_visual_v3.php' && empty($categoria)) ? 'active' : '' ?>">
                    <?= h(trim(($icono ? $icono.' ' : '').$titulo)) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</nav>

<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/configuracion.php";
verificarLogin();

function admin_brand_data($pdo){
    $nombre = trim(configValor($pdo, 'nombre_comercial', 'Mica Store'));
    $logo = trim(configValor($pdo, 'logo', ''));
    $inicial = mb_strtoupper(mb_substr($nombre ?: 'Mica Store', 0, 1, 'UTF-8'), 'UTF-8');
    return [$nombre ?: 'Mica Store', $logo, $inicial];
}

function admin_asset_url($ruta){
    $ruta = trim((string)$ruta);
    if($ruta === '') return '';
    if(preg_match('#^https?://#i', $ruta)) return $ruta;
    return '/micastore/' . ltrim($ruta, '/');
}

function menu_item($active,$key,$href,$label,$mod=null){
    $mod = $mod ?: $key;
    if(!rolPuede($mod,'ver')) return;
    echo '<a class="'.($active==$key?'active':'').'" href="'.$href.'">'.$label.'</a>';
}

function admin_header($title='Dashboard',$active='dashboard'){
    global $pdo;
    erp_requerir_permiso($pdo,$active==='builder'?'builder':$active,'ver');
    [$brandNombre, $brandLogo, $brandInicial] = admin_brand_data($pdo);
    $brandLogoUrl = admin_asset_url($brandLogo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($brandNombre) ?> Admin</title>
    <link rel="stylesheet" href="/micastore/admin/assets/admin.css">
</head>
<body>
<div class="admin-layout">
<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">
            <?php if($brandLogoUrl): ?>
                <img src="<?= htmlspecialchars($brandLogoUrl) ?>" alt="<?= htmlspecialchars($brandNombre) ?>">
            <?php else: ?>
                <?= htmlspecialchars($brandInicial) ?>
            <?php endif; ?>
        </div>
        <div>
            <h1><?= htmlspecialchars($brandNombre) ?></h1>
            <p>Panel administrador</p>
        </div>
    </div>
    <nav class="menu">
<?php
menu_item($active,'dashboard','/micastore/admin/dashboard.php','📊 Dashboard');
menu_item($active,'empresas','/micastore/admin/empresas.php','🏢 Empresas','empresas');
menu_item($active,'tiendas','/micastore/admin/tiendas.php','🏬 Tiendas / Vendedores','tiendas');
menu_item($active,'productos','/micastore/admin/productos.php','📦 Productos');
menu_item($active,'productos','/micastore/admin/productos_masivo.php','📥 Productos masivos','productos');
menu_item($active,'inventario','/micastore/admin/inventario.php','📋 Inventario');
menu_item($active,'categorias','/micastore/admin/categorias.php','📁 Categorías');
menu_item($active,'subcategorias','/micastore/admin/subcategorias.php','🗂️ Subcategorías');
menu_item($active,'marcas','/micastore/admin/marcas.php','🏷️ Marcas');
menu_item($active,'clientes','/micastore/admin/clientes.php','👥 Clientes');
menu_item($active,'cotizaciones','/micastore/admin/cotizaciones.php','🧾 Cotizaciones');
menu_item($active,'reclamaciones','/micastore/admin/reclamaciones.php','📕 Reclamaciones','reclamaciones');
menu_item($active,'sliders','/micastore/admin/sliders.php','🖼️ Carrusel');
menu_item($active,'configuracion','/micastore/admin/configuracion.php','⚙️ Configuración');
menu_item($active,'usuarios','/micastore/admin/usuarios.php','👤 Usuarios','usuarios');
menu_item($active,'roles','/micastore/admin/roles.php','🔐 Roles y permisos','roles');
menu_item($active,'auditoria','/micastore/admin/auditoria.php','🧾 Auditoría','auditoria');
menu_item($active,'accesos','/micastore/admin/accesos.php','🕒 Accesos','accesos');
menu_item($active,'builder','/micastore/admin/store_builder.php','🎨 Diseño de tienda','builder');
menu_item($active,'builder','/micastore/admin/header_menu.php','🧭 Menú público','builder');
menu_item($active,'topbar','/micastore/admin/topbar.php','📌 TopBar','topbar');
menu_item($active,'contenido','/micastore/admin/contenido_sitio.php','📝 Contenido del sitio','contenido');
menu_item($active,'rrhh','/micastore/admin/rrhh_puestos.php','💼 Trabaja con nosotros','rrhh');
menu_item($active,'bloques','/micastore/admin/home_bloques.php','🧩 Bloques inicio','bloques');
menu_item($active,'campos','/micastore/admin/configuracion_campos.php','🧩 Campos personalizados','campos');
?>
<div class="site-switch">
    <label for="siteSwitchSelect">🌐 Ver tienda:</label>
    <select id="siteSwitchSelect" onchange="if(this.value) window.open(this.value,'_blank')">
        <option value="">Elegir sitio...</option>
        <option value="../tienda_visual_v3.php">🏠 Mica Store (sitio base)</option>
        <?php
        try{
            $empresasSwitch = $pdo->query("SELECT nombre,slug FROM marketplace_empresas WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
            foreach($empresasSwitch as $es){
                echo '<option value="../tienda_visual_v3.php?empresa='.htmlspecialchars($es['slug']).'">🏢 '.htmlspecialchars($es['nombre']).'</option>';
            }
        }catch(Exception $e){}
        ?>
    </select>
</div>
<a href="/micastore/admin/logout.php">🚪 Cerrar sesión</a>
    </nav>
</aside>
<main class="main">
    <div class="topbar">
        <h2><?= htmlspecialchars($title) ?></h2>
        <div class="user-pill"><?= htmlspecialchars(adminNombre()) ?> · <?= htmlspecialchars(adminRol()) ?></div>
    </div>
<?php }

function admin_footer(){ ?></main></div></body></html><?php } ?>

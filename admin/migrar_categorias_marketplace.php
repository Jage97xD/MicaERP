<?php
/**
 * MicaERP - Migración: Categorías de Marketplace (Comida y Bebidas + otras)
 * Ejecutar UNA VEZ desde el navegador: /admin/migrar_categorias_marketplace.php
 * Es idempotente: si ya existe la categoría/subcategoría (por slug), la actualiza
 * en vez de duplicarla. No borra nada existente. Borra este archivo después de usarlo.
 */
require_once "../config/db.php";
require_once "../config/erp_core.php";

function slugify($t){ $t=strtolower(trim($t)); $t=iconv('UTF-8','ASCII//TRANSLIT',$t); $t=preg_replace('/[^a-z0-9]+/','-',$t); return trim($t,'-'); }

function upsertCategoria(PDO $pdo, array $c): int {
    $slug = slugify($c['nombre']);
    $st = $pdo->prepare("SELECT id FROM categorias WHERE slug=? LIMIT 1");
    $st->execute([$slug]);
    $id = (int)$st->fetchColumn();
    $campos = [
        'nombre'=>$c['nombre'], 'slug'=>$slug, 'color'=>$c['color'], 'icono'=>$c['icono'], 'activo'=>1,
        'tipo_categoria'=>$c['tipo_categoria'], 'usa_marca'=>$c['usa_marca'], 'usa_sku'=>$c['usa_sku'],
        'usa_codigo'=>$c['usa_codigo'], 'usa_peso'=>$c['usa_peso'], 'usa_vencimiento'=>$c['usa_vencimiento']
    ];
    if($id){
        $sets=[]; $vals=[];
        foreach($campos as $k=>$v){ if($k==='slug') continue; $sets[]="$k=?"; $vals[]=$v; }
        $vals[]=$id;
        $pdo->prepare("UPDATE categorias SET ".implode(',',$sets)." WHERE id=?")->execute($vals);
        echo "<li>Actualizada categoría: <b>{$c['nombre']}</b> (id $id)</li>";
    }else{
        $cols = implode(',', array_keys($campos));
        $qs = implode(',', array_fill(0,count($campos),'?'));
        $pdo->prepare("INSERT INTO categorias ($cols) VALUES ($qs)")->execute(array_values($campos));
        $id = (int)$pdo->lastInsertId();
        echo "<li>Creada categoría nueva: <b>{$c['nombre']}</b> (id $id)</li>";
    }
    return $id;
}

// Si existe una subcategoría "sub prueba" bajo esta categoría, la RENOMBRAMOS en vez de
// crear una nueva — así los productos ya asignados (como "Jugos") no pierden su subcategoría.
function upsertSubcategoria(PDO $pdo, int $categoriaId, string $nombre, ?string $renombrarDesde=null): void {
    $slug = slugify($nombre);
    if($renombrarDesde){
        $slugViejo = slugify($renombrarDesde);
        $st = $pdo->prepare("SELECT id FROM subcategorias WHERE categoria_id=? AND slug=? LIMIT 1");
        $st->execute([$categoriaId,$slugViejo]);
        $idViejo = (int)$st->fetchColumn();
        if($idViejo){
            $pdo->prepare("UPDATE subcategorias SET nombre=?, slug=? WHERE id=?")->execute([$nombre,$slug,$idViejo]);
            echo "<li>Subcategoría renombrada: <b>{$renombrarDesde}</b> → <b>{$nombre}</b> (los productos ya asignados se mantienen)</li>";
            return;
        }
    }
    $st = $pdo->prepare("SELECT id FROM subcategorias WHERE categoria_id=? AND slug=? LIMIT 1");
    $st->execute([$categoriaId,$slug]);
    if($st->fetchColumn()){ echo "<li>Subcategoría ya existía: {$nombre}</li>"; return; }
    $pdo->prepare("INSERT INTO subcategorias (categoria_id,nombre,slug) VALUES (?,?,?)")->execute([$categoriaId,$nombre,$slug]);
    echo "<li>Creada subcategoría: {$nombre}</li>";
}

echo "<h2>Migrando categorías del marketplace...</h2><ul>";

// ---- Comida y Bebidas (la que necesita Juguería Yola) ----
// Sin marca / sin SKU / sin código de barras: no tiene sentido para comida hecha al momento.
// Con vencimiento: útil para que el admin controle productos perecibles.
$idComida = upsertCategoria($pdo, [
    'nombre'=>'Comida y Bebidas','color'=>'#f59e0b','icono'=>'🍽️','tipo_categoria'=>'alimenticio',
    'usa_marca'=>0,'usa_sku'=>0,'usa_codigo'=>0,'usa_peso'=>0,'usa_vencimiento'=>1
]);
upsertSubcategoria($pdo, $idComida, 'Jugos y Batidos', 'sub prueba'); // recupera el producto "Jugos" ya creado
upsertSubcategoria($pdo, $idComida, 'Cremoladas y Helados');
upsertSubcategoria($pdo, $idComida, 'Bebidas calientes');
upsertSubcategoria($pdo, $idComida, 'Postres y dulces');
upsertSubcategoria($pdo, $idComida, 'Platos y menú');
upsertSubcategoria($pdo, $idComida, 'Piqueos y snacks');
upsertSubcategoria($pdo, $idComida, 'Panadería y pastelería');
upsertSubcategoria($pdo, $idComida, 'Combos y promociones');

// ---- Otras categorías sugeridas para un mercado/galería tipo marketplace ----
$idMascotas = upsertCategoria($pdo, [
    'nombre'=>'Mascotas','color'=>'#16a34a','icono'=>'🐾','tipo_categoria'=>'normal',
    'usa_marca'=>1,'usa_sku'=>1,'usa_codigo'=>1,'usa_peso'=>1,'usa_vencimiento'=>0
]);
upsertSubcategoria($pdo, $idMascotas, 'Alimento para mascotas');
upsertSubcategoria($pdo, $idMascotas, 'Accesorios');
upsertSubcategoria($pdo, $idMascotas, 'Veterinaria y cuidado');

$idRopa = upsertCategoria($pdo, [
    'nombre'=>'Ropa y Calzado','color'=>'#7c3aed','icono'=>'👕','tipo_categoria'=>'normal',
    'usa_marca'=>1,'usa_sku'=>1,'usa_codigo'=>1,'usa_peso'=>0,'usa_vencimiento'=>0
]);
upsertSubcategoria($pdo, $idRopa, 'Ropa de hombre');
upsertSubcategoria($pdo, $idRopa, 'Ropa de mujer');
upsertSubcategoria($pdo, $idRopa, 'Calzado');
upsertSubcategoria($pdo, $idRopa, 'Accesorios y bisutería');

$idLibreria = upsertCategoria($pdo, [
    'nombre'=>'Librería y Oficina','color'=>'#0891b2','icono'=>'📚','tipo_categoria'=>'normal',
    'usa_marca'=>1,'usa_sku'=>1,'usa_codigo'=>1,'usa_peso'=>0,'usa_vencimiento'=>0
]);
upsertSubcategoria($pdo, $idLibreria, 'Útiles escolares');
upsertSubcategoria($pdo, $idLibreria, 'Insumos de oficina');
upsertSubcategoria($pdo, $idLibreria, 'Impresiones y copias');

$idServicios = upsertCategoria($pdo, [
    'nombre'=>'Servicios Generales','color'=>'#475569','icono'=>'🛠️','tipo_categoria'=>'servicio',
    'usa_marca'=>0,'usa_sku'=>0,'usa_codigo'=>0,'usa_peso'=>0,'usa_vencimiento'=>0
]);
upsertSubcategoria($pdo, $idServicios, 'Delivery');
upsertSubcategoria($pdo, $idServicios, 'Instalaciones');
upsertSubcategoria($pdo, $idServicios, 'Reparaciones');
upsertSubcategoria($pdo, $idServicios, 'Limpieza');

echo "</ul><h3 style='color:#16a34a'>Listo. Puedes borrar este archivo: admin/migrar_categorias_marketplace.php</h3>";
echo "<p><a href='categorias.php'>Ir a Categorías</a> · <a href='producto_nuevo_pro.php'>Crear producto</a></p>";

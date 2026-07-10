<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
function slug_mica($texto){
    $texto = strtolower(trim((string)$texto));
    $texto = iconv('UTF-8','ASCII//TRANSLIT',$texto);
    $texto = preg_replace('/[^a-z0-9]+/','-',$texto);
    return trim($texto,'-') ?: 'item';
}
function columnas_mica($pdo,$tabla){
    $cols=[];
    try{ foreach($pdo->query("DESCRIBE $tabla")->fetchAll(PDO::FETCH_ASSOC) as $r){ $cols[$r['Field']]=true; } }catch(Exception $e){}
    return $cols;
}
function asegurar_categoria($pdo,$slug,$nombre=''){
    $slug = slug_mica($slug ?: $nombre);
    $stmt=$pdo->prepare("SELECT id FROM categorias WHERE slug=? LIMIT 1"); $stmt->execute([$slug]); $id=$stmt->fetchColumn();
    if($id) return (int)$id;
    $nombre = trim($nombre) ?: ucwords(str_replace('-',' ',$slug));
    $stmt=$pdo->prepare("INSERT INTO categorias (nombre,slug,activo) VALUES (?,?,1)"); $stmt->execute([$nombre,$slug]);
    return (int)$pdo->lastInsertId();
}
function asegurar_subcategoria($pdo,$categoriaId,$slug,$nombre=''){
    if(trim((string)$slug)==='' && trim((string)$nombre)==='') return null;
    $slug = slug_mica($slug ?: $nombre);
    $stmt=$pdo->prepare("SELECT id FROM subcategorias WHERE slug=? AND categoria_id=? LIMIT 1"); $stmt->execute([$slug,$categoriaId]); $id=$stmt->fetchColumn();
    if($id) return (int)$id;
    $nombre = trim($nombre) ?: ucwords(str_replace('-',' ',$slug));
    $stmt=$pdo->prepare("INSERT INTO subcategorias (categoria_id,nombre,slug,activo) VALUES (?,?,?,1)"); $stmt->execute([$categoriaId,$nombre,$slug]);
    return (int)$pdo->lastInsertId();
}
function asegurar_marca($pdo,$slug,$nombre=''){
    if(trim((string)$slug)==='' && trim((string)$nombre)==='') return null;
    $slug = slug_mica($slug ?: $nombre);
    $stmt=$pdo->prepare("SELECT id FROM marcas WHERE slug=? LIMIT 1"); $stmt->execute([$slug]); $id=$stmt->fetchColumn();
    if($id) return (int)$id;
    $nombre = trim($nombre) ?: ucwords(str_replace('-',' ',$slug));
    $stmt=$pdo->prepare("INSERT INTO marcas (nombre,slug,activo) VALUES (?,?,1)"); $stmt->execute([$nombre,$slug]);
    return (int)$pdo->lastInsertId();
}
function bool_mica($v){ return in_array(strtolower(trim((string)$v)), ['1','si','sí','true','x','activo','yes'], true) ? 1 : 0; }
function num_mica($v){ return (float)str_replace(',','.',preg_replace('/[^0-9,\.\-]/','',(string)$v)); }
function csv_out($filename,$rows,$headers){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    echo "\xEF\xBB\xBF";
    $out=fopen('php://output','w');
    fputcsv($out,$headers,';');
    foreach($rows as $r){ $line=[]; foreach($headers as $h){ $line[]=$r[$h] ?? ''; } fputcsv($out,$line,';'); }
    fclose($out); exit;
}

$headers = ['tipo_item','nombre','categoria_slug','categoria_nombre','subcategoria_slug','subcategoria_nombre','marca_slug','marca_nombre','codigo','sku','precio','precio_oferta','costo','stock','stock_minimo','descripcion_corta','descripcion_larga','garantia','oferta','nuevo','destacado','activo','imagen_principal','caracteristicas','ficha_tecnica'];

if(isset($_GET['plantilla'])){
    csv_out('plantilla_productos_micastore.csv', [[
        'tipo_item'=>'producto','nombre'=>'Ejemplo Pintura Látex','categoria_slug'=>'pintura','categoria_nombre'=>'Pintura','subcategoria_slug'=>'latex','subcategoria_nombre'=>'LATEX','marca_slug'=>'vencedor','marca_nombre'=>'Vencedor','codigo'=>'PINT-001','sku'=>'SKU-001','precio'=>'70.00','precio_oferta'=>'0','costo'=>'45.00','stock'=>'20','stock_minimo'=>'3','descripcion_corta'=>'Pintura lavable para interiores','descripcion_larga'=>'Descripción completa del producto.','garantia'=>'Garantía según proveedor.','oferta'=>'0','nuevo'=>'1','destacado'=>'1','activo'=>'1','imagen_principal'=>'uploads/productos_masivo/pintura.jpg','caracteristicas'=>'Lavable|Alta cobertura|Secado rápido','ficha_tecnica'=>'Color:Blanco|Presentación:Galón|Uso:Interior'
    ]], $headers);
}

if(isset($_GET['exportar'])){
    $sql="SELECT p.*,c.slug categoria_slug,c.nombre categoria_nombre,s.slug subcategoria_slug,s.nombre subcategoria_nombre,m.slug marca_slug,m.nombre marca_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id=c.id LEFT JOIN subcategorias s ON p.subcategoria_id=s.id LEFT JOIN marcas m ON p.marca_id=m.id ORDER BY p.id DESC";
    $rows=[]; foreach($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $p){
        $rows[]=[
            'tipo_item'=>$p['tipo_item'] ?? 'producto','nombre'=>$p['nombre'] ?? '','categoria_slug'=>$p['categoria_slug'] ?? '','categoria_nombre'=>$p['categoria_nombre'] ?? '','subcategoria_slug'=>$p['subcategoria_slug'] ?? '','subcategoria_nombre'=>$p['subcategoria_nombre'] ?? '','marca_slug'=>$p['marca_slug'] ?? '','marca_nombre'=>$p['marca_nombre'] ?? '','codigo'=>$p['codigo'] ?? '','sku'=>$p['sku'] ?? '','precio'=>$p['precio'] ?? 0,'precio_oferta'=>$p['precio_oferta'] ?? 0,'costo'=>$p['costo'] ?? 0,'stock'=>$p['stock'] ?? 0,'stock_minimo'=>$p['stock_minimo'] ?? 0,'descripcion_corta'=>$p['descripcion_corta'] ?? '','descripcion_larga'=>$p['descripcion_larga'] ?? '','garantia'=>$p['garantia'] ?? '','oferta'=>$p['oferta'] ?? 0,'nuevo'=>$p['nuevo'] ?? 0,'destacado'=>$p['destacado'] ?? 0,'activo'=>$p['activo'] ?? 1,'imagen_principal'=>$p['imagen_principal'] ?? '','caracteristicas'=>'','ficha_tecnica'=>$p['ficha_tecnica'] ?? ''
        ];
    }
    csv_out('reporte_productos_micastore_'.date('Ymd_His').'.csv',$rows,$headers);
}

$mensaje=''; $errores=[]; $insertados=0; $actualizados=0;
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['csv_productos'])){
    try{
        if($_FILES['csv_productos']['error'] !== UPLOAD_ERR_OK) throw new Exception('No se recibió el archivo CSV.');
        $cols = columnas_mica($pdo,'productos');
        $fh = fopen($_FILES['csv_productos']['tmp_name'],'r');
        if(!$fh) throw new Exception('No se pudo abrir el CSV.');
        $first = fgets($fh); if($first===false) throw new Exception('El CSV está vacío.');
        $first = preg_replace('/^\xEF\xBB\xBF/','',$first);
        $delim = substr_count($first,';') >= substr_count($first,',') ? ';' : ',';
        $head = str_getcsv($first,$delim);
        $head = array_map(fn($x)=>trim($x),$head);
        $linea=1;
        $pdo->beginTransaction();
        while(($row=fgetcsv($fh,0,$delim))!==false){
            $linea++;
            if(count(array_filter($row,fn($v)=>trim((string)$v)!==''))===0) continue;
            $d=[]; foreach($head as $i=>$k){ $d[$k]=trim((string)($row[$i] ?? '')); }
            if(($d['nombre'] ?? '')===''){ $errores[]="Línea $linea: producto sin nombre."; continue; }
            $catId = asegurar_categoria($pdo,$d['categoria_slug'] ?? '',$d['categoria_nombre'] ?? '');
            $subId = asegurar_subcategoria($pdo,$catId,$d['subcategoria_slug'] ?? '',$d['subcategoria_nombre'] ?? '');
            $marcaId = asegurar_marca($pdo,$d['marca_slug'] ?? '',$d['marca_nombre'] ?? '');
            $tipo = $d['tipo_item'] ?? 'producto'; if(!in_array($tipo,['producto','servicio','peso','alimenticio','digital'])) $tipo='producto';
            $slug = slug_mica($d['nombre']).'-'.substr(md5(($d['codigo'] ?? '').($d['sku'] ?? '').$linea.time()),0,6);
            $imagen = $d['imagen_principal'] ?? '';
            if($imagen && !file_exists('../'.$imagen)){
                $base = basename($imagen);
                if(file_exists('../uploads/productos_masivo/'.$base)) $imagen = 'uploads/productos_masivo/'.$base;
                elseif(file_exists('../uploads/productos/'.$base)) $imagen = 'uploads/productos/'.$base;
            }
            $datos=[
                'tipo_item'=>$tipo,'nombre'=>$d['nombre'],'slug'=>$slug,'codigo'=>$d['codigo'] ?? '','sku'=>$d['sku'] ?? '','categoria_id'=>$catId,'subcategoria_id'=>$subId,'marca_id'=>$marcaId,'precio'=>num_mica($d['precio'] ?? 0),'precio_oferta'=>num_mica($d['precio_oferta'] ?? 0),'costo'=>num_mica($d['costo'] ?? 0),'stock'=>(int)num_mica($d['stock'] ?? 0),'stock_minimo'=>(int)num_mica($d['stock_minimo'] ?? 0),'descripcion_corta'=>$d['descripcion_corta'] ?? '','descripcion_larga'=>$d['descripcion_larga'] ?? '','garantia'=>$d['garantia'] ?? '','ficha_tecnica'=>str_replace('|',"\n",$d['ficha_tecnica'] ?? ''),'imagen_principal'=>$imagen,'oferta'=>bool_mica($d['oferta'] ?? 0),'nuevo'=>bool_mica($d['nuevo'] ?? 0),'destacado'=>bool_mica($d['destacado'] ?? 0),'activo'=>($d['activo'] ?? '')===''?1:bool_mica($d['activo'])
            ];
            if($tipo==='servicio'){ $datos['stock']=0; $datos['stock_minimo']=0; $datos['costo']=0; }
            $insert=[]; foreach($datos as $k=>$v){ if(isset($cols[$k])) $insert[$k]=$v; }
            if(isset($cols['creado_en'])) $insert['creado_en']=date('Y-m-d H:i:s');
            $keys=array_keys($insert);
            $sql="INSERT INTO productos (".implode(',',$keys).") VALUES (".implode(',',array_fill(0,count($keys),'?')).")";
            $pdo->prepare($sql)->execute(array_values($insert));
            $pid=(int)$pdo->lastInsertId(); $insertados++;
            if(!empty($d['caracteristicas'])){
                $pdo->exec("CREATE TABLE IF NOT EXISTS producto_caracteristicas (id INT AUTO_INCREMENT PRIMARY KEY, producto_id INT NOT NULL, icono VARCHAR(20) DEFAULT '✔', texto VARCHAR(255) NOT NULL, orden INT DEFAULT 0, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(producto_id))");
                foreach(explode('|',$d['caracteristicas']) as $i=>$car){ $car=trim($car); if($car!=='') $pdo->prepare("INSERT INTO producto_caracteristicas (producto_id,texto,orden) VALUES (?,?,?)")->execute([$pid,$car,$i+1]); }
            }
        }
        fclose($fh); $pdo->commit();
        $mensaje="Importación finalizada. Productos creados: $insertados.";
    }catch(Exception $e){ if($pdo->inTransaction()) $pdo->rollBack(); $errores[]=$e->getMessage(); }
}

admin_header('Productos masivos','productos');
?>
<style>
.mass-grid{display:grid;grid-template-columns:1fr .9fr;gap:22px}.helpbox{background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:18px}.okmsg{background:#dcfce7;color:#166534;border:1px solid #86efac;padding:12px;border-radius:12px;margin-bottom:15px;font-weight:bold}.errmsg{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:12px;border-radius:12px;margin-bottom:10px;font-weight:bold}.steps li{margin:8px 0}.code{font-family:Consolas,monospace;background:#111827;color:white;border-radius:10px;padding:10px;display:block;overflow:auto}@media(max-width:900px){.mass-grid{grid-template-columns:1fr}}
</style>
<div class="mass-grid">
  <div class="panel">
    <div class="panel-header"><h3>Importar productos por CSV</h3><a class="btn gray" href="productos.php">Volver</a></div>
    <?php if($mensaje): ?><div class="okmsg"><?= h($mensaje) ?></div><?php endif; ?>
    <?php foreach($errores as $e): ?><div class="errmsg"><?= h($e) ?></div><?php endforeach; ?>
    <p style="color:#64748b;margin-bottom:16px;">Carga productos por categoría con precio, stock, descripciones, marca, subcategoría, imagen, características y ficha técnica.</p>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group full"><label>Archivo CSV</label><input type="file" name="csv_productos" accept=".csv,text/csv" required></div>
        </div>
        <button class="btn green" type="submit">Importar productos</button>
        <a class="btn" href="?plantilla=1">Descargar plantilla</a>
        <a class="btn gray" href="?exportar=1">Exportar reporte</a>
    </form>
  </div>
  <div class="helpbox">
    <h3>Cómo subir imágenes masivas</h3>
    <ol class="steps">
      <li>Crea la carpeta <span class="code">uploads/productos_masivo/</span></li>
      <li>Copia ahí tus imágenes: <span class="code">pintura.jpg, laptop.webp, perfume.png</span></li>
      <li>En la columna <strong>imagen_principal</strong> coloca la ruta: <span class="code">uploads/productos_masivo/pintura.jpg</span></li>
      <li>Importa el CSV.</li>
    </ol>
    <h3>Notas</h3>
    <p>Si la categoría, subcategoría o marca no existe, MicaStore la crea automáticamente usando el slug de la plantilla.</p>
    <p>Separadores especiales:</p>
    <span class="code">caracteristicas: Lavable|Alta cobertura|Secado rápido<br>ficha_tecnica: Color:Blanco|Uso:Interior</span>
  </div>
</div>
<?php admin_footer(); ?>

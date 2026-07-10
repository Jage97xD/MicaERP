<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

function columnas($pdo, $tabla){
    $cols = [];
    try{
        foreach($pdo->query("DESCRIBE $tabla")->fetchAll(PDO::FETCH_ASSOC) as $r){
            $cols[$r["Field"]] = true;
        }
    }catch(Exception $e){}
    return $cols;
}

function guardarImagenSlider($file){
    if(empty($file["name"])) return "";
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if(!in_array($ext, ["jpg","jpeg","png","webp","gif","svg"])) return "";

    $dir = "../uploads/sliders";
    if(!is_dir($dir)) mkdir($dir, 0777, true);

    $nombre = "slider_" . date("Ymd_His") . "_" . rand(1000,9999) . "." . $ext;
    $rutaRel = "uploads/sliders/" . $nombre;

    if(move_uploaded_file($file["tmp_name"], "../" . $rutaRel)) return $rutaRel;
    return "";
}

function crearUrlDestino($tipo, $valor, $urlManual){
    $tipo = trim($tipo);
    $valor = trim((string)$valor);
    $urlManual = trim((string)$urlManual);

    if($tipo === "inicio") return "tienda_visual_v3.php";
    if($tipo === "categoria" && $valor !== "") return "tienda_visual_v3.php?categoria=" . urlencode($valor);
    if($tipo === "producto" && $valor !== "") return "producto_mysql.php?id=" . (int)$valor;
    if($tipo === "catalogo") return "tienda_visual_v3.php#productos";
    if($tipo === "externo") return $urlManual;
    if($tipo === "manual") return $urlManual;
    return "#";
}

$id = (int)($_GET["id"] ?? 0);
$error = "";
$colsSliders = columnas($pdo, "sliders");

$slider = [
    "titulo" => "",
    "titulo_resaltado" => "",
    "subtitulo" => "",
    "texto_boton" => "Ver productos",
    "url_boton" => "",
    "imagen" => "",
    "color_inicio" => "#020817",
    "color_fin" => "#001b47",
    "color_resaltado" => "#37c5ff",
    "orden" => 0,
    "activo" => 1
];

if($id > 0){
    $stmt = $pdo->prepare("SELECT * FROM sliders WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$row) die("Carrusel no encontrado.");
    $slider = array_merge($slider, $row);
}

$categorias = [];
$productos = [];
try{ $categorias = $pdo->query("SELECT id,nombre,slug FROM categorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){}
try{
    $productos = $pdo->query("SELECT p.id,p.nombre,c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON p.categoria_id=c.id WHERE p.activo=1 ORDER BY p.nombre ASC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){}

$destinoTipoDetectado = "manual";
$destinoCategoria = "";
$destinoProducto = "";
$destinoUrlManual = $slider["url_boton"] ?? "";

if(!empty($slider["url_boton"])){
    $url = $slider["url_boton"];
    if($url === "tienda_visual_v3.php"){
        $destinoTipoDetectado = "inicio";
    }elseif($url === "tienda_visual_v3.php#productos"){
        $destinoTipoDetectado = "catalogo";
    }elseif(strpos($url, "tienda_visual_v3.php?categoria=") !== false){
        $destinoTipoDetectado = "categoria";
        $parts = parse_url($url);
        if(!empty($parts["query"])){
            parse_str($parts["query"], $q);
            $destinoCategoria = $q["categoria"] ?? "";
        }
    }elseif(strpos($url, "producto_mysql.php?id=") !== false){
        $destinoTipoDetectado = "producto";
        $parts = parse_url($url);
        if(!empty($parts["query"])){
            parse_str($parts["query"], $q);
            $destinoProducto = $q["id"] ?? "";
        }
    }elseif(strpos($url, "http://") === 0 || strpos($url, "https://") === 0){
        $destinoTipoDetectado = "externo";
    }
}

if($_SERVER["REQUEST_METHOD"] === "POST"){
    try{
        $titulo = trim($_POST["titulo"] ?? "");
        if($titulo === "") throw new Exception("El título es obligatorio.");

        $destinoTipo = $_POST["destino_tipo"] ?? "categoria";
        $valorDestino = "";
        if($destinoTipo === "categoria") $valorDestino = $_POST["destino_categoria"] ?? "";
        if($destinoTipo === "producto") $valorDestino = $_POST["destino_producto"] ?? "";
        $urlBoton = crearUrlDestino($destinoTipo, $valorDestino, $_POST["destino_url"] ?? "");

        $imagen = $slider["imagen"] ?? "";
        $nuevaImagen = guardarImagenSlider($_FILES["imagen"] ?? []);
        if($nuevaImagen !== "") $imagen = $nuevaImagen;

        $datos = [
            "titulo" => $titulo,
            "titulo_resaltado" => trim($_POST["titulo_resaltado"] ?? ""),
            "subtitulo" => trim($_POST["subtitulo"] ?? ""),
            "texto_boton" => trim($_POST["texto_boton"] ?? "Ver productos"),
            "url_boton" => $urlBoton,
            "imagen" => $imagen,
            "color_inicio" => trim($_POST["color_inicio"] ?? "#020817"),
            "color_fin" => trim($_POST["color_fin"] ?? "#001b47"),
            "color_resaltado" => trim($_POST["color_resaltado"] ?? "#37c5ff"),
            "orden" => (int)($_POST["orden"] ?? 0),
            "activo" => isset($_POST["activo"]) ? 1 : 0
        ];

        $filtrados = [];
        foreach($datos as $k=>$v){ if(isset($colsSliders[$k])) $filtrados[$k] = $v; }

        if($id > 0){
            $sets=[]; $vals=[];
            foreach($filtrados as $k=>$v){ $sets[]="$k=?"; $vals[]=$v; }
            $vals[]=$id;
            $pdo->prepare("UPDATE sliders SET ".implode(",",$sets)." WHERE id=?")->execute($vals);
        }else{
            $campos=array_keys($filtrados);
            $sql="INSERT INTO sliders (".implode(",",$campos).") VALUES (".implode(",",array_fill(0,count($campos),"?")).")";
            $pdo->prepare($sql)->execute(array_values($filtrados));
        }

        header("Location: sliders.php?ok=1");
        exit;
    }catch(Exception $e){ $error = $e->getMessage(); }
}

admin_header($id ? "Editar carrusel" : "Nuevo carrusel", "sliders");
?>

<style>
.carousel-layout{display:grid;grid-template-columns:1.3fr .9fr;gap:22px}.help{color:#64748b;font-size:13px;margin-top:6px}.destino-box{background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:18px}.destino-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.destino-preview{margin-top:12px;background:#eef2ff;color:#1e3a8a;border-radius:12px;padding:12px;font-weight:800}.preview-slide{min-height:340px;border-radius:22px;overflow:hidden;position:relative;color:white;padding:35px;display:flex;align-items:center;background:linear-gradient(120deg,#020817,#001b47);box-shadow:0 16px 35px rgba(15,23,42,.22)}.preview-slide::before{content:"";position:absolute;inset:0;background:rgba(0,0,0,.16)}.preview-content{position:relative;z-index:1}.preview-content h1{font-size:42px;line-height:.95;margin:0 0 18px}.preview-content p{font-size:18px;color:#e5e7eb}.preview-btn{display:inline-block;background:#0057d9;color:white;padding:13px 22px;border-radius:12px;font-weight:900;margin-top:14px}.color-row{display:grid;grid-template-columns:60px 1fr;gap:10px;align-items:center}.color-row input[type="color"]{height:45px;padding:4px}@media(max-width:900px){.carousel-layout,.destino-grid{grid-template-columns:1fr}}
</style>

<div class="carousel-layout">
    <div class="panel">
        <div class="panel-header">
            <h3><?= $id ? "Editar carrusel" : "Nuevo carrusel" ?></h3>
            <a class="btn gray" href="sliders.php">Volver</a>
        </div>

        <?php if($error): ?><div style="background:#fee2e2;color:#991b1b;padding:14px;border-radius:12px;margin-bottom:18px;font-weight:bold;"><?= h($error) ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group"><label>Título principal</label><input name="titulo" id="titulo" required value="<?= h($slider["titulo"] ?? "") ?>" placeholder="Ejemplo: PROTEÍNAS"></div>
                <div class="form-group"><label>Título resaltado</label><input name="titulo_resaltado" id="titulo_resaltado" value="<?= h($slider["titulo_resaltado"] ?? "") ?>" placeholder="Ejemplo: PREMIUM"></div>
                <div class="form-group full"><label>Subtítulo</label><textarea name="subtitulo" id="subtitulo" placeholder="Ejemplo: Las mejores proteínas del mercado"><?= h($slider["subtitulo"] ?? "") ?></textarea></div>
                <div class="form-group"><label>Texto del botón</label><input name="texto_boton" id="texto_boton" value="<?= h($slider["texto_boton"] ?? "Ver productos") ?>" placeholder="Ver productos"></div>
                <div class="form-group"><label>Orden</label><input type="number" name="orden" value="<?= h($slider["orden"] ?? 0) ?>"></div>

                <div class="form-group full">
                    <label>Destino del botón</label>
                    <div class="destino-box">
                        <div class="destino-grid">
                            <div>
                                <label>¿Qué abrirá el botón?</label>
                                <select name="destino_tipo" id="destino_tipo" onchange="cambiarDestino()">
                                    <option value="categoria" <?= $destinoTipoDetectado === "categoria" ? "selected" : "" ?>>Categoría</option>
                                    <option value="producto" <?= $destinoTipoDetectado === "producto" ? "selected" : "" ?>>Producto</option>
                                    <option value="catalogo" <?= $destinoTipoDetectado === "catalogo" ? "selected" : "" ?>>Catálogo completo</option>
                                    <option value="inicio" <?= $destinoTipoDetectado === "inicio" ? "selected" : "" ?>>Inicio</option>
                                    <option value="externo" <?= $destinoTipoDetectado === "externo" ? "selected" : "" ?>>Link externo</option>
                                    <option value="manual" <?= $destinoTipoDetectado === "manual" ? "selected" : "" ?>>Ruta manual avanzada</option>
                                </select>
                                <div class="help">Ya no necesitas escribir rutas como tienda_visual_v3.php?categoria=proteina.</div>
                            </div>
                            <div class="destino-categoria"><label>Seleccionar categoría</label><select name="destino_categoria" id="destino_categoria" onchange="actualizarDestinoPreview()"><option value="">Seleccione categoría</option><?php foreach($categorias as $c): ?><option value="<?= h($c["slug"]) ?>" <?= $destinoCategoria === $c["slug"] ? "selected" : "" ?>><?= h($c["nombre"]) ?></option><?php endforeach; ?></select></div>
                            <div class="destino-producto"><label>Seleccionar producto</label><select name="destino_producto" id="destino_producto" onchange="actualizarDestinoPreview()"><option value="">Seleccione producto</option><?php foreach($productos as $p): ?><option value="<?= (int)$p["id"] ?>" <?= (string)$destinoProducto === (string)$p["id"] ? "selected" : "" ?>><?= h($p["nombre"]) ?> <?= !empty($p["categoria"]) ? "· ".h($p["categoria"]) : "" ?></option><?php endforeach; ?></select></div>
                            <div class="destino-url"><label>Link externo o ruta avanzada</label><input name="destino_url" id="destino_url" value="<?= h($destinoUrlManual) ?>" oninput="actualizarDestinoPreview()" placeholder="https://..."></div>
                        </div>
                        <div class="destino-preview">Destino generado: <span id="destinoPreview">-</span></div>
                    </div>
                </div>

                <div class="form-group"><label>Color inicio</label><div class="color-row"><input type="color" name="color_inicio" id="color_inicio" value="<?= h($slider["color_inicio"] ?? "#020817") ?>" oninput="actualizarPreview()"><input type="text" value="<?= h($slider["color_inicio"] ?? "#020817") ?>" readonly></div></div>
                <div class="form-group"><label>Color fin</label><div class="color-row"><input type="color" name="color_fin" id="color_fin" value="<?= h($slider["color_fin"] ?? "#001b47") ?>" oninput="actualizarPreview()"><input type="text" value="<?= h($slider["color_fin"] ?? "#001b47") ?>" readonly></div></div>
                <div class="form-group"><label>Color resaltado</label><div class="color-row"><input type="color" name="color_resaltado" id="color_resaltado" value="<?= h($slider["color_resaltado"] ?? "#37c5ff") ?>" oninput="actualizarPreview()"><input type="text" value="<?= h($slider["color_resaltado"] ?? "#37c5ff") ?>" readonly></div></div>
                <div class="form-group"><label>Imagen de fondo</label><input type="file" name="imagen" accept="image/*,.svg"><?php if(!empty($slider["imagen"])): ?><div class="help">Actual: <?= h($slider["imagen"]) ?></div><?php endif; ?></div>
                <div class="form-group full"><label><input type="checkbox" name="activo" <?= !empty($slider["activo"]) ? "checked" : "" ?>> Carrusel activo</label></div>
            </div>
            <button class="btn green" type="submit">Guardar carrusel</button>
        </form>
    </div>

    <div class="panel">
        <h3>Vista previa</h3><br>
        <div class="preview-slide" id="previewSlide"><div class="preview-content"><h1><span id="previewTitulo"><?= h($slider["titulo"] ?: "PROTEÍNAS") ?></span><br><span id="previewResaltado" style="color:<?= h($slider["color_resaltado"] ?? "#37c5ff") ?>"><?= h($slider["titulo_resaltado"] ?: "PREMIUM") ?></span></h1><p id="previewSubtitulo"><?= h($slider["subtitulo"] ?: "Las mejores proteínas del mercado") ?></p><span class="preview-btn" id="previewBoton"><?= h($slider["texto_boton"] ?: "Ver productos") ?></span></div></div>
    </div>
</div>

<script>
const titulo=document.getElementById("titulo"),resaltado=document.getElementById("titulo_resaltado"),subtitulo=document.getElementById("subtitulo"),textoBoton=document.getElementById("texto_boton"),colorInicio=document.getElementById("color_inicio"),colorFin=document.getElementById("color_fin"),colorResaltado=document.getElementById("color_resaltado");
function actualizarPreview(){document.getElementById("previewTitulo").textContent=titulo.value||"PROTEÍNAS";document.getElementById("previewResaltado").textContent=resaltado.value||"PREMIUM";document.getElementById("previewSubtitulo").textContent=subtitulo.value||"Las mejores proteínas del mercado";document.getElementById("previewBoton").textContent=textoBoton.value||"Ver productos";document.getElementById("previewResaltado").style.color=colorResaltado.value;document.getElementById("previewSlide").style.background="linear-gradient(120deg, "+colorInicio.value+", "+colorFin.value+")";}
function cambiarDestino(){const tipo=document.getElementById("destino_tipo").value;document.querySelector(".destino-categoria").style.display=tipo==="categoria"?"":"none";document.querySelector(".destino-producto").style.display=tipo==="producto"?"":"none";document.querySelector(".destino-url").style.display=(tipo==="externo"||tipo==="manual")?"":"none";actualizarDestinoPreview();}
function actualizarDestinoPreview(){const tipo=document.getElementById("destino_tipo").value,categoria=document.getElementById("destino_categoria").value,producto=document.getElementById("destino_producto").value,url=document.getElementById("destino_url").value;let destino="#";if(tipo==="inicio")destino="tienda_visual_v3.php";if(tipo==="catalogo")destino="tienda_visual_v3.php#productos";if(tipo==="categoria")destino=categoria?"tienda_visual_v3.php?categoria="+categoria:"Seleccione una categoría";if(tipo==="producto")destino=producto?"producto_mysql.php?id="+producto:"Seleccione un producto";if(tipo==="externo"||tipo==="manual")destino=url||"Ingrese el link";document.getElementById("destinoPreview").textContent=destino;}
[titulo,resaltado,subtitulo,textoBoton,colorInicio,colorFin,colorResaltado].forEach(el=>{if(el)el.addEventListener("input",actualizarPreview)});actualizarPreview();cambiarDestino();
</script>
<?php admin_footer(); ?>

<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

function crearSlugProducto($texto){
    $texto = strtolower(trim($texto));
    $texto = iconv("UTF-8", "ASCII//TRANSLIT", $texto);
    $texto = preg_replace("/[^a-z0-9]+/", "-", $texto);
    $texto = trim($texto, "-");
    return $texto ?: "producto";
}

function tablaExiste($pdo, $tabla){
    try{
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabla]);
        return (bool)$stmt->fetchColumn();
    }catch(Exception $e){
        return false;
    }
}

function columnas($pdo, $tabla){
    $cols = [];
    try{
        foreach($pdo->query("DESCRIBE $tabla")->fetchAll(PDO::FETCH_ASSOC) as $r){
            $cols[$r["Field"]] = true;
        }
    }catch(Exception $e){}
    return $cols;
}

function normalizarLineas($nombres, $valores, $separador=": "){
    $out = [];
    foreach($nombres as $i => $n){
        $n = trim((string)$n);
        $v = trim((string)($valores[$i] ?? ""));
        if($n !== "" && $v !== ""){
            $out[] = $n . $separador . $v;
        }
    }
    return implode("\n", $out);
}

$id = (int)($_GET["id"] ?? 0);
if($id <= 0){
    header("Location: productos.php");
    exit;
}

$colsProductos = columnas($pdo, "productos");

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$producto){
    die("Producto no encontrado.");
}
if(!erp_producto_en_scope($pdo, $id)){ http_response_code(403); die('Acceso restringido. Este producto no pertenece a tus categorías permitidas.'); }

$categorias = $pdo->query("SELECT id,nombre FROM categorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $pdo->query("SELECT id,nombre FROM marcas WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcategorias = $pdo->query("SELECT id,nombre,categoria_id FROM subcategorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$tiendas = $pdo->query("SELECT id,nombre,categoria_id FROM marketplace_tiendas WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = "";
$ok = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $accion = $_POST["accion"] ?? "basicos";

    try{
        if($accion === "basicos"){
            $nombre = trim($_POST["nombre"] ?? "");
            if($nombre === ""){
                throw new Exception("El nombre del producto es obligatorio.");
            }

            $datos = [
                "nombre" => $nombre,
                "codigo" => trim($_POST["codigo"] ?? ""),
                "sku" => trim($_POST["sku"] ?? ""),
                "categoria_id" => (int)($_POST["categoria_id"] ?? 0) ?: null,
                "subcategoria_id" => (int)($_POST["subcategoria_id"] ?? 0) ?: null,
                "marca_id" => (int)($_POST["marca_id"] ?? 0) ?: null,
                "tienda_id" => erp_es_vendedor_tienda() ? erp_tienda_id_actual() : ((int)($_POST["tienda_id"] ?? 0) ?: null),
                "precio" => (float)($_POST["precio"] ?? 0),
                "precio_oferta" => (float)($_POST["precio_oferta"] ?? 0),
                "costo" => (float)($_POST["costo"] ?? 0),
                "stock" => (int)($_POST["stock"] ?? 0),
                "stock_minimo" => (int)($_POST["stock_minimo"] ?? 0),
                "descripcion_corta" => trim($_POST["descripcion_corta"] ?? ""),
                "destacado" => isset($_POST["destacado"]) ? 1 : 0,
                "nuevo" => isset($_POST["nuevo"]) ? 1 : 0,
                "oferta" => isset($_POST["oferta"]) ? 1 : 0,
                "activo" => isset($_POST["activo"]) ? 1 : 0
            ];

            if(isset($colsProductos["slug"]) && trim($producto["slug"] ?? "") === ""){
                $datos["slug"] = crearSlugProducto($nombre) . "-" . time();
            }

            if(isset($_FILES["imagen_principal"]) && $_FILES["imagen_principal"]["error"] === UPLOAD_ERR_OK){
                $dir = "../uploads/productos/";
                if(!is_dir($dir)){
                    mkdir($dir, 0777, true);
                }

                $ext = strtolower(pathinfo($_FILES["imagen_principal"]["name"], PATHINFO_EXTENSION));
                if(!in_array($ext, ["jpg","jpeg","png","webp","gif"])){
                    throw new Exception("La imagen principal debe ser JPG, PNG, WEBP o GIF.");
                }

                $archivo = "prod_" . $id . "_" . time() . "." . $ext;
                if(move_uploaded_file($_FILES["imagen_principal"]["tmp_name"], $dir . $archivo)){
                    $datos["imagen_principal"] = "uploads/productos/" . $archivo;
                }
            }

            $sets = [];
            $vals = [];

            foreach($datos as $campo => $valor){
                if(isset($colsProductos[$campo])){
                    $sets[] = "$campo=?";
                    $vals[] = $valor;
                }
            }

            $vals[] = $id;
            $pdo->prepare("UPDATE productos SET ".implode(", ", $sets)." WHERE id=?")->execute($vals);

            header("Location: producto_editar.php?id=$id&ok=basicos");
            exit;
        }

        if($accion === "descripcion"){
            $datos = [
                "descripcion_larga" => trim($_POST["descripcion_larga"] ?? ""),
                "garantia" => trim($_POST["garantia"] ?? "")
            ];

            if(isset($_FILES["pdf_ficha"]) && $_FILES["pdf_ficha"]["error"] === UPLOAD_ERR_OK){
                $dir = "../uploads/fichas/";
                if(!is_dir($dir)){
                    mkdir($dir, 0777, true);
                }

                $ext = strtolower(pathinfo($_FILES["pdf_ficha"]["name"], PATHINFO_EXTENSION));
                if($ext !== "pdf"){
                    throw new Exception("La ficha técnica debe ser PDF.");
                }

                $archivo = "ficha_" . $id . "_" . time() . ".pdf";
                if(move_uploaded_file($_FILES["pdf_ficha"]["tmp_name"], $dir . $archivo)){
                    $datos["pdf_ficha"] = "uploads/fichas/" . $archivo;
                }
            }

            $sets = [];
            $vals = [];

            foreach($datos as $campo => $valor){
                if(isset($colsProductos[$campo])){
                    $sets[] = "$campo=?";
                    $vals[] = $valor;
                }
            }

            if($sets){
                $vals[] = $id;
                $pdo->prepare("UPDATE productos SET ".implode(", ", $sets)." WHERE id=?")->execute($vals);
            }

            header("Location: producto_editar.php?id=$id&ok=descripcion#tab-descripcion");
            exit;
        }

        if($accion === "ficha"){
            $texto = normalizarLineas($_POST["ficha_nombre"] ?? [], $_POST["ficha_valor"] ?? []);

            if(isset($colsProductos["ficha_tecnica"])){
                $stmt = $pdo->prepare("UPDATE productos SET ficha_tecnica=? WHERE id=?");
                $stmt->execute([$texto, $id]);
            }

            if(tablaExiste($pdo, "producto_especificaciones")){
                $cols = columnas($pdo, "producto_especificaciones");
                $pdo->prepare("DELETE FROM producto_especificaciones WHERE producto_id=?")->execute([$id]);

                foreach(($_POST["ficha_nombre"] ?? []) as $i => $n){
                    $n = trim((string)$n);
                    $v = trim((string)($_POST["ficha_valor"][$i] ?? ""));
                    if($n === "" || $v === "") continue;

                    if(isset($cols["nombre"]) && isset($cols["valor"])){
                        $pdo->prepare("INSERT INTO producto_especificaciones (producto_id,nombre,valor) VALUES (?,?,?)")
                            ->execute([$id,$n,$v]);
                    }
                }
            }

            header("Location: producto_editar.php?id=$id&ok=ficha#tab-ficha");
            exit;
        }

        if($accion === "caracteristicas"){
    if(tablaExiste($pdo, "producto_caracteristicas")){
        $cols = columnas($pdo, "producto_caracteristicas");

        $campoProducto = isset($cols["producto_id"]) ? "producto_id" : "id_producto";

        $campoTexto = null;
        foreach(["caracteristica", "nombre", "descripcion", "texto", "valor"] as $campo){
            if(isset($cols[$campo])){
                $campoTexto = $campo;
                break;
            }
        }

        if($campoTexto){
            $pdo->prepare("DELETE FROM producto_caracteristicas WHERE $campoProducto=?")->execute([$id]);

            foreach(($_POST["caracteristica"] ?? []) as $car){
                $car = trim((string)$car);
                if($car === "") continue;

                $sql = "INSERT INTO producto_caracteristicas ($campoProducto, $campoTexto) VALUES (?, ?)";
                $pdo->prepare($sql)->execute([$id, $car]);
            }
        }
    }

    header("Location: producto_editar.php?id=$id&ok=caracteristicas#tab-caracteristicas");
    exit;
}

        if($accion === "preguntas"){
            if(tablaExiste($pdo, "producto_preguntas")){
                $cols = columnas($pdo, "producto_preguntas");
                $pdo->prepare("DELETE FROM producto_preguntas WHERE producto_id=?")->execute([$id]);

                foreach(($_POST["pregunta"] ?? []) as $i => $preg){
                    $preg = trim((string)$preg);
                    $resp = trim((string)($_POST["respuesta"][$i] ?? ""));
                    if($preg === "" || $resp === "") continue;

                    if(isset($cols["pregunta"]) && isset($cols["respuesta"])){
                        $pdo->prepare("INSERT INTO producto_preguntas (producto_id,pregunta,respuesta) VALUES (?,?,?)")
                            ->execute([$id,$preg,$resp]);
                    }
                }
            }

            header("Location: producto_editar.php?id=$id&ok=preguntas#tab-preguntas");
            exit;
        }

    }catch(Exception $e){
        $error = $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

$fichaLineas = [];
if(!empty($producto["ficha_tecnica"])){
    foreach(explode("\n", $producto["ficha_tecnica"]) as $linea){
        $linea = trim($linea);
        if($linea === "") continue;
        $partes = explode(":", $linea, 2);
        $fichaLineas[] = [
            "nombre" => trim($partes[0] ?? ""),
            "valor" => trim($partes[1] ?? "")
        ];
    }
}

if(!$fichaLineas && tablaExiste($pdo, "producto_especificaciones")){
    $tmp = $pdo->prepare("SELECT * FROM producto_especificaciones WHERE producto_id=? ORDER BY id ASC");
    $tmp->execute([$id]);
    foreach($tmp->fetchAll(PDO::FETCH_ASSOC) as $r){
        $fichaLineas[] = [
            "nombre" => $r["nombre"] ?? "",
            "valor" => $r["valor"] ?? ""
        ];
    }
}

$caracteristicas = [];

if(tablaExiste($pdo,"producto_caracteristicas")){

    $cols = columnas($pdo,"producto_caracteristicas");

    $campoProducto = isset($cols["producto_id"])
        ? "producto_id"
        : "id_producto";

    $tmp = $pdo->prepare("
        SELECT *
        FROM producto_caracteristicas
        WHERE $campoProducto=?
        ORDER BY id ASC
    ");

    $tmp->execute([$id]);

    foreach($tmp->fetchAll(PDO::FETCH_ASSOC) as $r){

        if(isset($r["texto"])){
            $caracteristicas[] = $r["texto"];
        }
        elseif(isset($r["caracteristica"])){
            $caracteristicas[] = $r["caracteristica"];
        }
        elseif(isset($r["nombre"])){
            $caracteristicas[] = $r["nombre"];
        }
        elseif(isset($r["descripcion"])){
            $caracteristicas[] = $r["descripcion"];
        }
        elseif(isset($r["valor"])){
            $caracteristicas[] = $r["valor"];
        }

    }

}

$preguntas = [];
if(tablaExiste($pdo, "producto_preguntas")){
    $tmp = $pdo->prepare("SELECT * FROM producto_preguntas WHERE producto_id=? ORDER BY id ASC");
    $tmp->execute([$id]);
    $preguntas = $tmp->fetchAll(PDO::FETCH_ASSOC);
}

admin_header("Editar producto", "productos");
?>

<style>
.product-edit-card{background:#fff;border-radius:22px;padding:26px;box-shadow:0 10px 30px rgba(15,23,42,.08)}
.product-edit-head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:18px}
.product-edit-head h3{margin:0;font-size:24px}
.product-edit-head p{margin:6px 0 0;color:#64748b}
.product-actions{display:flex;gap:10px;flex-wrap:wrap}
.tabs-unified{display:flex;gap:10px;flex-wrap:wrap;margin:20px 0}
.tab-unified{border:0;background:#edf2ff;color:#07162f;padding:14px 18px;border-radius:13px;font-weight:900;cursor:pointer;font-size:15px}
.tab-unified.active{background:#2563eb;color:white}
.tab-panel{display:none}
.tab-panel.active{display:block}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.form-group{display:flex;flex-direction:column;gap:7px}
.form-group.full{grid-column:1/-1}
.form-group label{font-weight:900}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:14px;border:1px solid #d8dee9;border-radius:12px;font-size:15px}
.form-group textarea{min-height:160px}
.checks{display:flex;gap:20px;flex-wrap:wrap;padding:12px 0}
.preview-img{max-width:160px;max-height:160px;border-radius:14px;border:1px solid #e5e7eb;object-fit:cover;background:#f8fafc}
.row-flex{display:grid;grid-template-columns:180px 1fr 95px;gap:10px;align-items:center;margin-bottom:10px}
.row-flex.simple{grid-template-columns:1fr 95px}
.row-flex input{padding:13px;border:1px solid #d8dee9;border-radius:12px;font-size:15px}
.btn-mini{border:0;border-radius:12px;padding:13px 16px;font-weight:900;cursor:pointer}
.btn-red{background:#ef4444;color:#fff}
.btn-blue{background:#2563eb;color:#fff}
.btn-green{background:#16a34a;color:#fff}
.notice-ok{background:#dcfce7;color:#166534;padding:14px;border-radius:12px;margin-bottom:18px;font-weight:900}
.notice-error{background:#fee2e2;color:#991b1b;padding:14px;border-radius:12px;margin-bottom:18px;font-weight:900}
.quick-box{display:grid;grid-template-columns:260px 1fr;gap:25px;align-items:start}
.quick-box img{width:100%;border-radius:16px;border:1px solid #e5e7eb;background:#f8fafc}
.quick-price{font-size:28px;color:#0057d9;font-weight:900}
@media(max-width:900px){.form-grid,.quick-box{grid-template-columns:1fr}.product-edit-head{flex-direction:column}.row-flex{grid-template-columns:1fr}}
</style>

<div class="product-edit-card">
    <div class="product-edit-head">
        <div>
            <h3>Editar producto: <?= h($producto["nombre"] ?? "") ?></h3>
            <p>Un solo lugar para editar datos básicos, descripción, ficha técnica, características y preguntas.</p>
        </div>
        <div class="product-actions">
            <a class="btn gray" href="productos.php">Volver</a>
            <a class="btn blue" target="_blank" href="../producto_mysql.php?id=<?= (int)$id ?>">Ver producto</a>
        </div>
    </div>

    <?php if(isset($_GET["ok"])): ?>
        <div class="notice-ok">Cambios guardados correctamente.</div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="notice-error"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="tabs-unified">
        <button class="tab-unified active" data-tab="basicos">📦 Datos básicos</button>
        <button class="tab-unified" data-tab="descripcion">📝 Descripción</button>
        <button class="tab-unified" data-tab="ficha">📋 Ficha técnica</button>
        <button class="tab-unified" data-tab="caracteristicas">✅ Características</button>
        <button class="tab-unified" data-tab="preguntas">❓ Preguntas</button>
        <button class="tab-unified" data-tab="vista">👁 Vista rápida</button>
    </div>

    <section id="tab-basicos" class="tab-panel active">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="basicos">

            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre</label>
                    <input name="nombre" value="<?= h($producto["nombre"] ?? "") ?>" required>
                </div>

                <div class="form-group">
                    <label>Código</label>
                    <input name="codigo" value="<?= h($producto["codigo"] ?? "") ?>">
                </div>

                <div class="form-group">
                    <label>SKU</label>
                    <input name="sku" value="<?= h($producto["sku"] ?? "") ?>">
                </div>

                <div class="form-group">
                    <label>Marca</label>
                    <select name="marca_id">
                        <option value="0">Sin marca</option>
                        <?php foreach($marcas as $m): ?>
                            <option value="<?= (int)$m["id"] ?>" <?= (int)($producto["marca_id"] ?? 0) === (int)$m["id"] ? "selected" : "" ?>>
                                <?= h($m["nombre"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tienda / vendedor</label>
                    <?php if(erp_es_vendedor_tienda()): ?>
                        <input value="Tienda asignada a tu usuario" readonly>
                    <?php else: ?>
                        <select name="tienda_id">
                            <option value="0">Marketplace general</option>
                            <?php foreach($tiendas as $t): ?>
                                <option value="<?= (int)$t['id'] ?>" <?= (int)($producto['tienda_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= h($t['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Categoría</label>
                    <select name="categoria_id" id="categoria_id">
                        <option value="0">Sin categoría</option>
                        <?php foreach($categorias as $c): ?>
                            <option value="<?= (int)$c["id"] ?>" <?= (int)($producto["categoria_id"] ?? 0) === (int)$c["id"] ? "selected" : "" ?>>
                                <?= h($c["nombre"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Subcategoría</label>
                    <select name="subcategoria_id" id="subcategoria_id">
                        <option value="0">Sin subcategoría</option>
                        <?php foreach($subcategorias as $s): ?>
                            <option value="<?= (int)$s["id"] ?>" data-cat="<?= (int)$s["categoria_id"] ?>" <?= (int)($producto["subcategoria_id"] ?? 0) === (int)$s["id"] ? "selected" : "" ?>>
                                <?= h($s["nombre"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Precio</label>
                    <input type="number" step="0.01" name="precio" value="<?= h($producto["precio"] ?? 0) ?>">
                </div>

                <div class="form-group">
                    <label>Precio oferta</label>
                    <input type="number" step="0.01" name="precio_oferta" value="<?= h($producto["precio_oferta"] ?? 0) ?>">
                </div>

                <?php if(isset($colsProductos["costo"])): ?>
                <div class="form-group campo-costo">
                    <label>Costo</label>
                    <input type="number" step="0.01" name="costo" value="<?= h($producto["costo"] ?? 0) ?>">
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" value="<?= h($producto["stock"] ?? 0) ?>">
                </div>

                <div class="form-group">
                    <label>Stock mínimo</label>
                    <input type="number" name="stock_minimo" value="<?= h($producto["stock_minimo"] ?? 0) ?>">
                </div>

                <div class="form-group full">
                    <label>Descripción corta</label>
                    <input name="descripcion_corta" value="<?= h($producto["descripcion_corta"] ?? "") ?>">
                </div>

                <div class="form-group">
                    <label>Imagen actual</label>
                    <?php if(!empty($producto["imagen_principal"])): ?>
                        <img class="preview-img" src="../<?= h($producto["imagen_principal"]) ?>">
                    <?php else: ?>
                        <p>Sin imagen.</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Cambiar imagen principal</label>
                    <input type="file" name="imagen_principal" accept="image/*">
                </div>

                <div class="form-group full">
                    <label>Estados</label>
                    <div class="checks">
                        <label><input type="checkbox" name="destacado" <?= !empty($producto["destacado"]) ? "checked" : "" ?>> Destacado</label>
                        <label><input type="checkbox" name="nuevo" <?= !empty($producto["nuevo"]) ? "checked" : "" ?>> Nuevo</label>
                        <label><input type="checkbox" name="oferta" <?= !empty($producto["oferta"]) ? "checked" : "" ?>> Oferta</label>
                        <label><input type="checkbox" name="activo" <?= !empty($producto["activo"]) ? "checked" : "" ?>> Activo</label>
                    </div>
                </div>
            </div>

            <br>
            <button class="btn green" type="submit">Guardar datos básicos</button>
        </form>
    </section>

    <section id="tab-descripcion" class="tab-panel">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="descripcion">

            <div class="form-grid">
                <div class="form-group full">
                    <label>Descripción larga</label>
                    <textarea name="descripcion_larga" placeholder="Describe el producto con más detalle..."><?= h($producto["descripcion_larga"] ?? "") ?></textarea>
                    <small>Ejemplo: beneficios, uso recomendado, condiciones, contenido de caja, etc.</small>
                </div>

                <div class="form-group">
                    <label>Garantía</label>
                    <input name="garantia" value="<?= h($producto["garantia"] ?? "") ?>">
                </div>

                <div class="form-group">
                    <label>PDF de ficha técnica</label>
                    <input type="file" name="pdf_ficha" accept="application/pdf">
                    <?php if(!empty($producto["pdf_ficha"])): ?>
                        <small>Actual: <a target="_blank" href="../<?= h($producto["pdf_ficha"]) ?>">Ver PDF</a></small>
                    <?php endif; ?>
                </div>
            </div>

            <br>
            <button class="btn green" type="submit">Guardar descripción</button>
        </form>
    </section>

    <section id="tab-ficha" class="tab-panel">
        <form method="POST">
            <input type="hidden" name="accion" value="ficha">

            <div id="fichaRows">
                <?php if(count($fichaLineas) === 0): ?>
                    <div class="row-flex">
                        <input name="ficha_nombre[]" placeholder="Ejemplo: Material">
                        <input name="ficha_valor[]" placeholder="Ejemplo: Plástico / metal / etc.">
                        <button type="button" class="btn-mini btn-red" onclick="this.parentElement.remove()">Quitar</button>
                    </div>
                <?php else: ?>
                    <?php foreach($fichaLineas as $f): ?>
                        <div class="row-flex">
                            <input name="ficha_nombre[]" value="<?= h($f["nombre"]) ?>" placeholder="Nombre">
                            <input name="ficha_valor[]" value="<?= h($f["valor"]) ?>" placeholder="Valor">
                            <button type="button" class="btn-mini btn-red" onclick="this.parentElement.remove()">Quitar</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="btn-mini btn-blue" onclick="agregarFicha()">+ Agregar fila</button>
            <button class="btn green" type="submit">Guardar ficha técnica</button>
        </form>
    </section>

    <section id="tab-caracteristicas" class="tab-panel">
        <form method="POST">
            <input type="hidden" name="accion" value="caracteristicas">

            <div id="carRows">
                <?php if(count($caracteristicas) === 0): ?>
                    <div class="row-flex simple">
                        <input name="caracteristica[]" placeholder="Ejemplo: Resistente al agua">
                        <button type="button" class="btn-mini btn-red" onclick="this.parentElement.remove()">Quitar</button>
                    </div>
                <?php else: ?>
                    <?php foreach($caracteristicas as $c): ?>
                        <div class="row-flex simple">
                            <input name="caracteristica[]" value="<?= h($c) ?>" placeholder="Característica">
                            <button type="button" class="btn-mini btn-red" onclick="this.parentElement.remove()">Quitar</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="btn-mini btn-blue" onclick="agregarCaracteristica()">+ Agregar característica</button>
            <button class="btn green" type="submit">Guardar características</button>
        </form>
    </section>

    <section id="tab-preguntas" class="tab-panel">
        <form method="POST">
            <input type="hidden" name="accion" value="preguntas">

            <div id="pregRows">
                <?php if(count($preguntas) === 0): ?>
                    <div class="row-flex">
                        <input name="pregunta[]" placeholder="Pregunta frecuente">
                        <input name="respuesta[]" placeholder="Respuesta">
                        <button type="button" class="btn-mini btn-red" onclick="this.parentElement.remove()">Quitar</button>
                    </div>
                <?php else: ?>
                    <?php foreach($preguntas as $p): ?>
                        <div class="row-flex">
                            <input name="pregunta[]" value="<?= h($p["pregunta"] ?? "") ?>" placeholder="Pregunta">
                            <input name="respuesta[]" value="<?= h($p["respuesta"] ?? "") ?>" placeholder="Respuesta">
                            <button type="button" class="btn-mini btn-red" onclick="this.parentElement.remove()">Quitar</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="btn-mini btn-blue" onclick="agregarPregunta()">+ Agregar pregunta</button>
            <button class="btn green" type="submit">Guardar preguntas</button>
        </form>
    </section>

    <section id="tab-vista" class="tab-panel">
        <div class="quick-box">
            <div>
                <?php if(!empty($producto["imagen_principal"])): ?>
                    <img src="../<?= h($producto["imagen_principal"]) ?>">
                <?php else: ?>
                    <img src="../img/banners/slide-tecnologia.svg">
                <?php endif; ?>
            </div>
            <div>
                <h2><?= h($producto["nombre"] ?? "") ?></h2>
                <p><?= h($producto["descripcion_corta"] ?? "") ?></p>
                <div class="quick-price">
                    S/ <?= number_format((float)(($producto["precio_oferta"] ?? 0) > 0 ? $producto["precio_oferta"] : $producto["precio"]), 2) ?>
                </div>
                <p><strong>Stock:</strong> <?= (int)($producto["stock"] ?? 0) ?></p>
                <p><strong>Garantía:</strong> <?= h($producto["garantia"] ?? "") ?></p>
                <br>
                <a class="btn blue" target="_blank" href="../producto_mysql.php?id=<?= (int)$id ?>">Abrir producto público</a>
            </div>
        </div>
    </section>
</div>

<script>
function activarTab(nombre){
    document.querySelectorAll(".tab-unified").forEach(b => b.classList.remove("active"));
    document.querySelectorAll(".tab-panel").forEach(p => p.classList.remove("active"));

    const btn = document.querySelector('.tab-unified[data-tab="'+nombre+'"]');
    const panel = document.getElementById("tab-" + nombre);

    if(btn) btn.classList.add("active");
    if(panel) panel.classList.add("active");
}

document.querySelectorAll(".tab-unified").forEach(btn => {
    btn.addEventListener("click", () => {
        activarTab(btn.dataset.tab);
        history.replaceState(null, "", "#tab-" + btn.dataset.tab);
    });
});

if(location.hash){
    const t = location.hash.replace("#tab-", "");
    if(t) activarTab(t);
}

function filtrarSubcategorias(){
    const cat = document.getElementById("categoria_id").value;
    const sub = document.getElementById("subcategoria_id");

    [...sub.options].forEach(opt => {
        if(opt.value === "0"){
            opt.hidden = false;
            return;
        }

        opt.hidden = opt.dataset.cat !== cat;
    });

    const selected = sub.options[sub.selectedIndex];
    if(selected && selected.value !== "0" && selected.dataset.cat !== cat){
        sub.value = "0";
    }
}

document.getElementById("categoria_id").addEventListener("change", filtrarSubcategorias);
filtrarSubcategorias();

function agregarFicha(){
    document.getElementById("fichaRows").insertAdjacentHTML("beforeend", `
        <div class="row-flex">
            <input name="ficha_nombre[]" placeholder="Nombre">
            <input name="ficha_valor[]" placeholder="Valor">
            <button type="button" class="btn-mini btn-red" onclick="this.parentElement.remove()">Quitar</button>
        </div>
    `);
}

function agregarCaracteristica(){
    document.getElementById("carRows").insertAdjacentHTML("beforeend", `
        <div class="row-flex simple">
            <input name="caracteristica[]" placeholder="Característica">
            <button type="button" class="btn-mini btn-red" onclick="this.parentElement.remove()">Quitar</button>
        </div>
    `);
}

function agregarPregunta(){
    document.getElementById("pregRows").insertAdjacentHTML("beforeend", `
        <div class="row-flex">
            <input name="pregunta[]" placeholder="Pregunta frecuente">
            <input name="respuesta[]" placeholder="Respuesta">
            <button type="button" class="btn-mini btn-red" onclick="this.parentElement.remove()">Quitar</button>
        </div>
    `);
}
</script>


<script>
function aplicarServicioSinCostoStockEditar(){
    const tipoSelect = document.querySelector('[name="tipo_item"]');
    const tipo = tipoSelect ? tipoSelect.value : "producto";
    const esServicio = tipo === "servicio";

    document.querySelectorAll('input[name="costo"], input[name="stock"], input[name="stock_minimo"]').forEach(input => {
        const group = input.closest(".form-group");
        if(group) group.style.display = esServicio ? "none" : "";
        if(esServicio) input.value = "0";
    });
}
document.addEventListener("DOMContentLoaded", aplicarServicioSinCostoStockEditar);
document.addEventListener("change", function(e){
    if(e.target && e.target.name === "tipo_item"){
        aplicarServicioSinCostoStockEditar();
    }
});
</script>

<?php admin_footer(); ?>

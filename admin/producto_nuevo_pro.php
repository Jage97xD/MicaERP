<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

function columnasTabla($pdo, $tabla){
    $cols = [];
    try{
        foreach($pdo->query("DESCRIBE $tabla")->fetchAll(PDO::FETCH_ASSOC) as $r){
            $cols[$r["Field"]] = true;
        }
    }catch(Exception $e){}
    return $cols;
}

function guardarArchivo($file, $dirRel, $permitidos){
    if(empty($file['name'])) return '';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, $permitidos)) return '';

    $dir = "../" . $dirRel;
    if(!is_dir($dir)){ mkdir($dir, 0777, true); }

    $nombre = $dirRel . "/" . date("Ymd_His") . "_" . rand(1000,9999) . "." . $ext;
    move_uploaded_file($file['tmp_name'], "../" . $nombre);
    return $nombre;
}

function crearSlug($texto){
    $texto = strtolower(trim($texto));
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    $texto = trim($texto, '-');
    return $texto ?: 'producto';
}

$scopeCats = erp_scope_sql_producto($pdo, 'id');
$categorias = $pdo->query("SELECT * FROM categorias WHERE activo=1 $scopeCats ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcategorias = $pdo->query("SELECT * FROM subcategorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $pdo->query("SELECT * FROM marcas WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$tiendas = $pdo->query("SELECT id,nombre,categoria_id FROM marketplace_tiendas WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    try{
        $cols = columnasTabla($pdo, "productos");

        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        if($categoriaId <= 0){
            throw new Exception("Primero selecciona una categoría.");
        }
        $catsPermitidas = erp_categorias_permitidas($pdo);
        if($catsPermitidas !== null && !in_array($categoriaId, $catsPermitidas, true)){
            throw new Exception("No tienes permiso para crear productos en esta categoría.");
        }

        $tipoItem = $_POST['tipo_item'] ?? 'producto';
        if(!in_array($tipoItem, ['producto','servicio','peso','alimenticio','digital'])){
            $tipoItem = 'producto';
        }

        $stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE id=? LIMIT 1");
        $stmtCat->execute([$categoriaId]);
        $cat = $stmtCat->fetch(PDO::FETCH_ASSOC);

        if(!$cat){
            throw new Exception("La categoría seleccionada no existe.");
        }

        $usaMarca = (int)($cat["usa_marca"] ?? 1);
        $usaSku = (int)($cat["usa_sku"] ?? 1);
        $usaCodigo = (int)($cat["usa_codigo"] ?? 1);
        $usaPeso = (int)($cat["usa_peso"] ?? 0);
        $usaVencimiento = (int)($cat["usa_vencimiento"] ?? 0);
        $usaStock = 1;
        $esServicio = false;

        if($tipoItem === "servicio"){
            $usaMarca = 0;
            $usaSku = 0;
            $usaCodigo = 0;
            $usaPeso = 0;
            $usaVencimiento = 0;
            $usaStock = 0;
            $esServicio = true;
        }

        if($tipoItem === "peso"){
            $usaMarca = 0;
            $usaSku = 0;
            $usaCodigo = 0;
            $usaPeso = 1;
            $usaVencimiento = 0;
            $usaStock = 1;
        }

        if($tipoItem === "alimenticio"){
            $usaSku = 0;
            $usaCodigo = 0;
            $usaPeso = 1;
            $usaVencimiento = 1;
            $usaStock = 1;
        }

        if($tipoItem === "digital"){
            $usaMarca = 0;
            $usaSku = 1;
            $usaCodigo = 1;
            $usaPeso = 0;
            $usaVencimiento = 0;
            $usaStock = 0;
        }

        $nombreProducto = trim($_POST['nombre'] ?? '');
        if($nombreProducto === ''){
            throw new Exception($esServicio ? "El nombre del servicio es obligatorio." : "El nombre del producto es obligatorio.");
        }

        $imagenPrincipal = guardarArchivo($_FILES['imagen_principal'] ?? [], "uploads/productos", ["jpg","jpeg","png","webp","gif"]);
        $pdfFicha = guardarArchivo($_FILES['pdf_ficha'] ?? [], "uploads/fichas", ["pdf"]);

        $specNombres = $_POST['spec_nombre'] ?? [];
        $specValores = $_POST['spec_valor'] ?? [];
        $lineasFicha = [];

        foreach($specNombres as $i => $n){
            $n = trim((string)$n);
            $v = trim((string)($specValores[$i] ?? ''));
            if($n !== '' && $v !== '') $lineasFicha[] = $n . ": " . $v;
        }

        $datos = [
            'tipo_item' => $tipoItem,
            'nombre' => $nombreProducto,
            'slug' => crearSlug($nombreProducto) . '-' . time(),
            'codigo' => $usaCodigo ? trim($_POST['codigo'] ?? '') : '',
            'sku' => $usaSku ? trim($_POST['sku'] ?? '') : '',
            'categoria_id' => $categoriaId,
            'subcategoria_id' => !empty($_POST['subcategoria_id']) ? (int)$_POST['subcategoria_id'] : null,
            'marca_id' => ($usaMarca && !empty($_POST['marca_id'])) ? (int)$_POST['marca_id'] : null,
            'tienda_id' => erp_es_vendedor_tienda() ? erp_tienda_id_actual() : (!empty($_POST['tienda_id']) ? (int)$_POST['tienda_id'] : null),
            'precio' => (float)($_POST['precio'] ?? 0),
            'precio_oferta' => (float)($_POST['precio_oferta'] ?? 0),
            'costo' => $esServicio ? 0 : (float)($_POST['costo'] ?? 0),
            'stock' => $usaStock ? (int)($_POST['stock'] ?? 0) : 0,
            'stock_minimo' => $usaStock ? (int)($_POST['stock_minimo'] ?? 0) : 0,
            'descripcion_corta' => trim($_POST['descripcion_corta'] ?? ''),
            'descripcion_larga' => trim($_POST['descripcion_larga'] ?? ''),
            'ficha_tecnica' => implode("\n", $lineasFicha),
            'garantia' => trim($_POST['garantia'] ?? ''),
            'pdf_ficha' => $pdfFicha,
            'imagen_principal' => $imagenPrincipal,
            'peso_unidad' => $usaPeso ? trim($_POST['peso_unidad'] ?? '') : '',
            'fecha_vencimiento' => $usaVencimiento ? trim($_POST['fecha_vencimiento'] ?? '') : null,
            'duracion_servicio' => $esServicio ? trim($_POST['duracion_servicio'] ?? '') : '',
            'modalidad_servicio' => $esServicio ? trim($_POST['modalidad_servicio'] ?? '') : '',
            'destacado' => isset($_POST['destacado']) ? 1 : 0,
            'nuevo' => isset($_POST['nuevo']) ? 1 : 0,
            'oferta' => isset($_POST['oferta']) ? 1 : 0,
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        $insert = [];
        foreach($datos as $k=>$v){
            if(isset($cols[$k])){
                $insert[$k] = $v;
            }
        }

        if(isset($cols['creado_en'])) $insert['creado_en'] = date("Y-m-d H:i:s");

        $campos = array_keys($insert);
        $sql = "INSERT INTO productos (" . implode(",", $campos) . ") VALUES (" . implode(",", array_fill(0, count($campos), "?")) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($insert));
        $productoId = (int)$pdo->lastInsertId();

        $pdo->exec("CREATE TABLE IF NOT EXISTS producto_especificaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            producto_id INT NOT NULL,
            nombre VARCHAR(120) NOT NULL,
            valor VARCHAR(255) NOT NULL,
            orden INT DEFAULT 0,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(producto_id)
        )");

        foreach($specNombres as $i => $n){
            $n = trim((string)$n);
            $v = trim((string)($specValores[$i] ?? ''));
            if($n !== '' && $v !== ''){
                $stmt = $pdo->prepare("INSERT INTO producto_especificaciones (producto_id,nombre,valor,orden) VALUES (?,?,?,?)");
                $stmt->execute([$productoId,$n,$v,$i+1]);
            }
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS producto_caracteristicas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            producto_id INT NOT NULL,
            icono VARCHAR(20) DEFAULT '✔',
            texto VARCHAR(255) NOT NULL,
            orden INT DEFAULT 0,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(producto_id)
        )");

        $carIconos = $_POST['car_icono'] ?? [];
        $carTextos = $_POST['car_texto'] ?? [];
        foreach($carTextos as $i => $t){
            $t = trim((string)$t);
            $ic = trim((string)($carIconos[$i] ?? '✔'));
            if($t !== ''){
                $stmt = $pdo->prepare("INSERT INTO producto_caracteristicas (producto_id,icono,texto,orden) VALUES (?,?,?,?)");
                $stmt->execute([$productoId,$ic ?: '✔',$t,$i+1]);
            }
        }

        header("Location: producto_detalle_admin.php?id=".$productoId."&ok=".urlencode("Creado correctamente."));
        exit;

    }catch(Exception $e){
        $error = $e->getMessage();
    }
}

admin_header("Nuevo producto PRO", "productos");
?>

<style>
.step-card{background:#fff;border:1px solid #e5e7eb;border-radius:22px;padding:24px;margin-bottom:22px;box-shadow:0 8px 24px rgba(15,23,42,.06)}
.step-title{font-size:24px;font-weight:900;margin:0 0 8px}
.step-help{color:#64748b;margin:0 0 18px}
.item-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.item-card{border:2px solid #e5e7eb;border-radius:18px;padding:18px;background:#f8fafc;cursor:pointer}
.item-card input{margin-right:8px}
.item-card strong{display:block;margin-bottom:6px}
.item-card span{font-size:13px;color:#64748b}
.item-card:has(input:checked){border-color:#2563eb;background:#eff6ff}
.category-row{display:grid;grid-template-columns:1fr auto;gap:14px;align-items:end}
.category-row select{width:100%;padding:16px;border-radius:14px;border:1px solid #d8dee9;font-size:16px}
.config-msg{display:none;margin-top:15px;background:#dcfce7;color:#166534;border-radius:14px;padding:14px;font-weight:900}
.form-producto{display:none}
.pro-tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.pro-tabs button{border:0;border-radius:12px;padding:12px 18px;font-weight:bold;background:#eef2ff;cursor:pointer}
.pro-tabs button.active{background:#2563eb;color:white}
.pro-section{display:none}
.pro-section.active{display:block}
.full{grid-column:1/-1}
textarea.editor{min-height:170px;font-family:Arial;font-size:15px;line-height:1.6}
.row-list{display:flex;flex-direction:column;gap:10px}
.dyn-row{display:grid;grid-template-columns:170px 1fr 90px;gap:10px;align-items:center;background:#f8fafc;padding:12px;border-radius:14px}
.dyn-row.car{grid-template-columns:90px 1fr 90px}
.dyn-row input{width:100%;padding:12px;border:1px solid #d8dee9;border-radius:10px}
.remove-row{background:#ef4444;color:white;border:0;border-radius:10px;padding:10px;font-weight:bold;cursor:pointer}
.add-row{background:#2563eb;color:white;border:0;border-radius:12px;padding:12px 16px;font-weight:bold;cursor:pointer}
.campo-oculto{display:none!important}
.help{color:#64748b;font-size:14px;margin-top:6px}
@media(max-width:1000px){.item-grid{grid-template-columns:1fr 1fr}}
@media(max-width:650px){.item-grid,.category-row{grid-template-columns:1fr}}
</style>

<div class="panel">
    <div class="panel-header">
        <div>
            <h3>Nuevo producto PRO</h3>
            <p class="help">Primero elige qué vas a crear. Luego selecciona la categoría. El formulario se adapta automáticamente.</p>
        </div>
        <a class="btn gray" href="productos.php">Volver</a>
    </div>

    <?php if($error): ?>
        <div style="background:#fee2e2;color:#991b1b;padding:14px;border-radius:12px;margin-bottom:18px;font-weight:bold;">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="step-card">
            <p class="step-title">1. ¿Qué vas a crear?</p>
            <p class="step-help">Esto permite que Ferretería pueda tener productos físicos y servicios como gasfitería.</p>

            <div class="item-grid">
                <label class="item-card">
                    <input type="radio" name="tipo_item" value="producto" checked onchange="actualizarFormulario()">
                    <strong>📦 Producto físico</strong>
                    <span>Fierros, pintura, herramientas, laptops.</span>
                </label>

                <label class="item-card">
                    <input type="radio" name="tipo_item" value="servicio" onchange="actualizarFormulario()">
                    <strong>🛠 Servicio</strong>
                    <span>Gasfitería, peluquería, instalación.</span>
                </label>

                <label class="item-card">
                    <input type="radio" name="tipo_item" value="peso" onchange="actualizarFormulario()">
                    <strong>⚖️ Por peso</strong>
                    <span>Carnes, quesos, frutas por kilo.</span>
                </label>

                <label class="item-card">
                    <input type="radio" name="tipo_item" value="digital" onchange="actualizarFormulario()">
                    <strong>💻 Digital</strong>
                    <span>Licencias, cursos, archivos digitales.</span>
                </label>
            </div>
        </div>

        <div class="step-card">
            <p class="step-title">2. Selecciona la categoría</p>
            <p class="step-help">Después de elegir la categoría recién aparecerán los campos necesarios.</p>

            <div class="category-row">
                <div>
                    <label><strong>Categoría</strong></label>
                    <select name="categoria_id" id="categoria_id" required onchange="actualizarFormulario()">
                        <option value="">Seleccione una categoría</option>
                        <?php foreach($categorias as $c): ?>
                            <option
                                value="<?= (int)$c['id'] ?>"
                                data-tipo="<?= h($c['tipo_categoria'] ?? 'normal') ?>"
                                data-usa-marca="<?= (int)($c['usa_marca'] ?? 1) ?>"
                                data-usa-sku="<?= (int)($c['usa_sku'] ?? 1) ?>"
                                data-usa-codigo="<?= (int)($c['usa_codigo'] ?? 1) ?>"
                                data-usa-peso="<?= (int)($c['usa_peso'] ?? 0) ?>"
                                data-usa-vencimiento="<?= (int)($c['usa_vencimiento'] ?? 0) ?>"
                            >
                                <?= h($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn blue" onclick="actualizarFormulario()">Continuar</button>
            </div>

            <div class="config-msg" id="configMsg"></div>
        </div>

        <div class="form-producto" id="formProducto">
            <div class="pro-tabs">
                <button type="button" class="active" onclick="abrirTab('basico', this)">📦 Datos básicos</button>
                <button type="button" onclick="abrirTab('detalle', this)">📝 Descripción</button>
                <button type="button" onclick="abrirTab('specs', this)">📋 Ficha técnica</button>
                <button type="button" onclick="abrirTab('cars', this)">✅ Características</button>
                <button type="button" onclick="abrirTab('media', this)">🖼️ Archivos</button>
            </div>

            <section id="basico" class="pro-section active">
                <div class="form-grid">
                    <div class="form-group">
                        <label id="labelNombre">Nombre</label>
                        <input name="nombre" required placeholder="Ejemplo: Instalación de grifería">
                    </div>

                    <div class="form-group campo-codigo">
                        <label>Código</label>
                        <input name="codigo" placeholder="Ejemplo: 123456">
                    </div>

                    <div class="form-group campo-sku">
                        <label>SKU</label>
                        <input name="sku" placeholder="Ejemplo: SKU-001">
                    </div>

                    <div class="form-group">
                        <label>Categoría seleccionada</label>
                        <input id="categoriaVisual" readonly>
                    </div>

                    <div class="form-group">
                        <label>Subcategoría</label>
                        <select name="subcategoria_id" id="subcategoria_id">
                            <option value="">Seleccione</option>
                            <?php foreach($subcategorias as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" data-cat="<?= (int)$s['categoria_id'] ?>"><?= h($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group campo-marca">
                        <label>Marca</label>
                        <select name="marca_id">
                            <option value="">Seleccione</option>
                            <?php foreach($marcas as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= h($m['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Precio</label>
                        <input type="number" step="0.01" name="precio" value="0">
                    </div>

                    <div class="form-group">
                        <label>Precio oferta</label>
                        <input type="number" step="0.01" name="precio_oferta" value="0">
                    </div>

                    <div class="form-group campo-stock">
                        <label>Stock</label>
                        <input type="number" name="stock" value="0">
                    </div>

                    <div class="form-group campo-stock">
                        <label>Stock mínimo</label>
                        <input type="number" name="stock_minimo" value="0">
                    </div>

                    <div class="form-group campo-peso">
                        <label>Peso / unidad de venta</label>
                        <input name="peso_unidad" placeholder="Ejemplo: 1 kg, 500 g, unidad">
                    </div>

                    <div class="form-group campo-vencimiento">
                        <label>Fecha de vencimiento</label>
                        <input type="date" name="fecha_vencimiento">
                    </div>

                    <div class="form-group campo-servicio">
                        <label>Duración del servicio</label>
                        <input name="duracion_servicio" placeholder="Ejemplo: 2 horas">
                    </div>

                    <div class="form-group campo-servicio">
                        <label>Modalidad del servicio</label>
                        <input name="modalidad_servicio" placeholder="Ejemplo: En local / a domicilio">
                    </div>

                    <div class="form-group full">
                        <label>Descripción corta</label>
                        <textarea name="descripcion_corta" placeholder="Resumen corto para la tarjeta"></textarea>
                    </div>

                    <div class="form-group"><label><input type="checkbox" name="destacado"> Destacado</label></div>
                    <div class="form-group"><label><input type="checkbox" name="nuevo" checked> Nuevo</label></div>
                    <div class="form-group"><label><input type="checkbox" name="oferta"> Oferta</label></div>
                    <div class="form-group"><label><input type="checkbox" name="activo" checked> Activo</label></div>
                </div>
            </section>

            <section id="detalle" class="pro-section">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Descripción larga</label>
                        <textarea class="editor" name="descripcion_larga" placeholder="Describe beneficios, uso, condiciones, contenido, etc."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Garantía / Condición</label>
                        <input name="garantia" placeholder="Ejemplo: Garantía según producto / Servicio previa cita">
                    </div>
                </div>
            </section>

            <section id="specs" class="pro-section">
                <div class="row-list" id="specList">
                    <div class="dyn-row">
                        <input name="spec_nombre[]" placeholder="Campo">
                        <input name="spec_valor[]" placeholder="Valor">
                        <button type="button" class="remove-row" onclick="this.parentElement.remove()">Quitar</button>
                    </div>
                </div>
                <br>
                <button type="button" class="add-row" onclick="agregarSpec()">+ Agregar fila</button>
            </section>

            <section id="cars" class="pro-section">
                <div class="row-list" id="carList">
                    <div class="dyn-row car"><input name="car_icono[]" value="✔"><input name="car_texto[]" value="Atención coordinada"><button type="button" class="remove-row" onclick="this.parentElement.remove()">Quitar</button></div>
                </div>
                <br>
                <button type="button" class="add-row" onclick="agregarCar()">+ Agregar característica</button>
            </section>

            <section id="media" class="pro-section">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Imagen principal</label>
                        <input type="file" name="imagen_principal" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label>PDF de ficha técnica</label>
                        <input type="file" name="pdf_ficha" accept="application/pdf">
                    </div>
                </div>
            </section>

            <br>
            <button class="btn green" type="submit">Crear</button>
        </div>
    </form>
</div>

<script>
function tipoItem(){
    const checked = document.querySelector('input[name="tipo_item"]:checked');
    return checked ? checked.value : 'producto';
}

function abrirTab(id, btn){
    document.querySelectorAll('.pro-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.pro-tabs button').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}

function mostrar(selector, visible){
    document.querySelectorAll(selector).forEach(el => {
        el.classList.toggle('campo-oculto', !visible);
        el.querySelectorAll('input,select,textarea').forEach(input => {
            if(!visible){
                if(input.type === 'checkbox') input.checked = false;
                else if(input.tagName === 'SELECT') input.value = '';
                else input.value = '';
            }
        });
    });
}

function filtrarSubcategorias(){
    const cat = document.getElementById('categoria_id').value;
    const sub = document.getElementById('subcategoria_id');
    if(!sub) return;

    [...sub.options].forEach(opt => {
        if(opt.value === ''){
            opt.hidden = false;
            return;
        }
        opt.hidden = opt.dataset.cat !== cat;
    });

    const actual = sub.options[sub.selectedIndex];
    if(actual && actual.value !== '' && actual.dataset.cat !== cat){
        sub.value = '';
    }
}

function actualizarFormulario(){
    const select = document.getElementById('categoria_id');
    const form = document.getElementById('formProducto');
    const msg = document.getElementById('configMsg');

    if(!select.value){
        form.style.display = 'none';
        msg.style.display = 'none';
        return;
    }

    const opt = select.options[select.selectedIndex];
    const tipo = tipoItem();

    let usaMarca = opt.dataset.usaMarca !== '0';
    let usaSku = opt.dataset.usaSku !== '0';
    let usaCodigo = opt.dataset.usaCodigo !== '0';
    let usaPeso = opt.dataset.usaPeso === '1';
    let usaVencimiento = opt.dataset.usaVencimiento === '1';
    let usaStock = true;
    let esServicio = false;

    if(tipo === 'servicio'){
        usaMarca = false;
        usaSku = false;
        usaCodigo = false;
        usaPeso = false;
        usaVencimiento = false;
        usaStock = false;
        esServicio = true;
    }

    if(tipo === 'peso'){
        usaMarca = false;
        usaSku = false;
        usaCodigo = false;
        usaPeso = true;
        usaVencimiento = false;
        usaStock = true;
    }

    if(tipo === 'digital'){
        usaMarca = false;
        usaSku = true;
        usaCodigo = true;
        usaPeso = false;
        usaVencimiento = false;
        usaStock = false;
    }

    mostrar('.campo-marca', usaMarca);
    mostrar('.campo-sku', usaSku);
    mostrar('.campo-codigo', usaCodigo);
    mostrar('.campo-peso', usaPeso);
    mostrar('.campo-vencimiento', usaVencimiento);
    mostrar('.campo-stock', usaStock);
    mostrar('.campo-costo', !esServicio);
    mostrar('.campo-servicio', esServicio);

    document.getElementById('labelNombre').textContent = esServicio ? 'Nombre del servicio' : 'Nombre del producto';
    document.getElementById('categoriaVisual').value = opt.text;

    msg.style.display = 'block';
    msg.innerHTML = 'Formulario listo para <strong>' + tipo.toUpperCase() + '</strong> dentro de <strong>' + opt.text + '</strong>';

    form.style.display = 'block';
    filtrarSubcategorias();
}

function agregarSpec(){
    document.getElementById('specList').insertAdjacentHTML('beforeend', `
        <div class="dyn-row">
            <input name="spec_nombre[]" placeholder="Campo">
            <input name="spec_valor[]" placeholder="Valor">
            <button type="button" class="remove-row" onclick="this.parentElement.remove()">Quitar</button>
        </div>
    `);
}

function agregarCar(){
    document.getElementById('carList').insertAdjacentHTML('beforeend', `
        <div class="dyn-row car">
            <input name="car_icono[]" value="✔">
            <input name="car_texto[]" placeholder="Característica">
            <button type="button" class="remove-row" onclick="this.parentElement.remove()">Quitar</button>
        </div>
    `);
}

document.addEventListener('DOMContentLoaded', actualizarFormulario);
</script>

<?php admin_footer(); ?>
<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
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

$id = $_GET['id'] ?? 0;

$categoria = [
    'nombre' => '',
    'slug' => '',
    'color' => '#2563eb',
    'icono' => '📦',
    'activo' => 1,
    'tipo_categoria' => 'normal',
    'usa_marca' => 1,
    'usa_sku' => 1,
    'usa_codigo' => 1,
    'usa_peso' => 0,
    'usa_vencimiento' => 0
];

$colsCategorias = columnas($pdo, "categorias");

if($id){
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    $categoriaBD = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$categoriaBD){ die("Categoría no encontrada"); }
    $categoria = array_merge($categoria, $categoriaBD);
}

function crearSlug($texto){
    $texto = strtolower(trim($texto));
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    $texto = preg_replace('/[^a-z0-9]+/i', '-', $texto);
    return trim($texto, '-');
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre']);
    $slug = trim($_POST['slug']) ?: crearSlug($nombre);
    $color = $_POST['color'] ?? '#2563eb';
    $icono = trim($_POST['icono'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    $tipo = $_POST['tipo_categoria'] ?? 'normal';

    $usaMarca = isset($_POST['usa_marca']) ? 1 : 0;
    $usaSku = isset($_POST['usa_sku']) ? 1 : 0;
    $usaCodigo = isset($_POST['usa_codigo']) ? 1 : 0;
    $usaPeso = isset($_POST['usa_peso']) ? 1 : 0;
    $usaVencimiento = isset($_POST['usa_vencimiento']) ? 1 : 0;

    $datos = [
        'nombre' => $nombre,
        'slug' => $slug,
        'color' => $color,
        'icono' => $icono,
        'activo' => $activo,
        'tipo_categoria' => $tipo,
        'usa_marca' => $usaMarca,
        'usa_sku' => $usaSku,
        'usa_codigo' => $usaCodigo,
        'usa_peso' => $usaPeso,
        'usa_vencimiento' => $usaVencimiento
    ];

    $filtrados = [];
    foreach($datos as $k=>$v){
        if(isset($colsCategorias[$k])){
            $filtrados[$k] = $v;
        }
    }

    if($id){
        $sets = [];
        $vals = [];
        foreach($filtrados as $k=>$v){
            $sets[] = "$k=?";
            $vals[] = $v;
        }
        $vals[] = $id;
        $stmt = $pdo->prepare("UPDATE categorias SET ".implode(",", $sets)." WHERE id=?");
        $stmt->execute($vals);
    }else{
        $campos = array_keys($filtrados);
        $sql = "INSERT INTO categorias (".implode(",", $campos).") VALUES (".implode(",", array_fill(0, count($campos), "?")).")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($filtrados));
    }

    header("Location: categorias.php");
    exit;
}

admin_header($id ? "Editar categoría" : "Nueva categoría", "categorias");
?>

<style>
.category-layout{display:grid;grid-template-columns:1.5fr .8fr;gap:22px}
.color-row{display:grid;grid-template-columns:90px 1fr;gap:12px;align-items:center}
.color-row input[type="color"]{width:90px;height:52px;padding:4px;cursor:pointer}
.preview-card{border-radius:18px;padding:24px;color:white;min-height:180px;display:flex;flex-direction:column;justify-content:space-between;box-shadow:0 10px 25px rgba(15,23,42,.18)}
.preview-icon{font-size:44px}
.preview-title{font-size:28px;font-weight:900}
.preview-slug{opacity:.9;font-size:14px}
.help{color:#6b7280;font-size:13px;margin-top:6px}
.tipo-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
.tipo-card{border:1px solid #d8dee9;border-radius:14px;padding:14px;background:#f8fafc;cursor:pointer}
.tipo-card strong{display:block;margin-bottom:4px}
.tipo-card input{margin-right:8px}
.config-box{background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:18px;margin-top:15px}
.config-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
.config-grid label{background:white;border:1px solid #e5e7eb;border-radius:12px;padding:13px;font-weight:800}
.badge-demo{display:inline-block;margin-top:8px;background:#e0f2fe;color:#075985;border-radius:999px;padding:5px 10px;font-weight:800;font-size:12px}
@media(max-width:900px){.category-layout{grid-template-columns:1fr}.tipo-grid,.config-grid{grid-template-columns:1fr}}
</style>

<div class="category-layout">
    <div class="panel">
        <div class="panel-header">
            <h3><?= $id ? 'Editar categoría' : 'Nueva categoría' ?></h3>
            <a class="btn gray" href="categorias.php">Volver</a>
        </div>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre de categoría</label>
                    <input name="nombre" id="nombreCategoria" required value="<?= h($categoria['nombre']) ?>" placeholder="Ejemplo: Servicios">
                    <div class="help">Nombre que verá el cliente en la tienda.</div>
                </div>

                <div class="form-group">
                    <label>Slug / URL</label>
                    <input name="slug" id="slugCategoria" value="<?= h($categoria['slug']) ?>" placeholder="Ejemplo: servicios">
                    <div class="help">Ejemplo: tienda_visual_v3.php?categoria=servicios</div>
                </div>

                <div class="form-group">
                    <label>Icono</label>
                    <input name="icono" id="iconoCategoria" value="<?= h($categoria['icono'] ?? '') ?>" placeholder="Ejemplo: 💇">
                </div>

                <div class="form-group">
                    <label>Color del tema</label>
                    <div class="color-row">
                        <input type="color" name="color" id="colorCategoria" value="<?= h($categoria['color'] ?? '#2563eb') ?>">
                        <input type="text" id="colorTexto" value="<?= h($categoria['color'] ?? '#2563eb') ?>" readonly>
                    </div>
                </div>

                <div class="form-group full">
                    <label>Tipo de categoría</label>
                    <div class="tipo-grid">
                        <label class="tipo-card">
                            <input type="radio" name="tipo_categoria" value="normal" <?= ($categoria['tipo_categoria'] ?? 'normal') === 'normal' ? 'checked' : '' ?> onchange="aplicarTipoCategoria()">
                            <strong>📦 Producto normal</strong>
                            <span>Tecnología, ferretería, hogar, belleza.</span>
                        </label>

                        <label class="tipo-card">
                            <input type="radio" name="tipo_categoria" value="simple" <?= ($categoria['tipo_categoria'] ?? '') === 'simple' ? 'checked' : '' ?> onchange="aplicarTipoCategoria()">
                            <strong>🛒 Producto simple</strong>
                            <span>Productos sin marca, SKU ni código.</span>
                        </label>

                        <label class="tipo-card">
                            <input type="radio" name="tipo_categoria" value="alimenticio" <?= ($categoria['tipo_categoria'] ?? '') === 'alimenticio' ? 'checked' : '' ?> onchange="aplicarTipoCategoria()">
                            <strong>🥫 Alimenticio</strong>
                            <span>Jugos, quesos, comida preparada.</span>
                        </label>

                        <label class="tipo-card">
                            <input type="radio" name="tipo_categoria" value="peso" <?= ($categoria['tipo_categoria'] ?? '') === 'peso' ? 'checked' : '' ?> onchange="aplicarTipoCategoria()">
                            <strong>⚖️ Por peso</strong>
                            <span>Carnes, quesos por kilo, frutas.</span>
                        </label>

                        <label class="tipo-card">
                            <input type="radio" name="tipo_categoria" value="servicio" <?= ($categoria['tipo_categoria'] ?? '') === 'servicio' ? 'checked' : '' ?> onchange="aplicarTipoCategoria()">
                            <strong>💇 Servicio</strong>
                            <span>Peluquería, reparación, asesoría.</span>
                            <span class="badge-demo">Nuevo</span>
                        </label>

                        <label class="tipo-card">
                            <input type="radio" name="tipo_categoria" value="digital" <?= ($categoria['tipo_categoria'] ?? '') === 'digital' ? 'checked' : '' ?> onchange="aplicarTipoCategoria()">
                            <strong>💻 Digital</strong>
                            <span>Licencias, cursos, archivos digitales.</span>
                            <span class="badge-demo">Nuevo</span>
                        </label>

                        <label class="tipo-card">
                            <input type="radio" name="tipo_categoria" value="personalizado" <?= ($categoria['tipo_categoria'] ?? '') === 'personalizado' ? 'checked' : '' ?> onchange="aplicarTipoCategoria()">
                            <strong>⚙️ Personalizado</strong>
                            <span>Tú decides qué campos usa.</span>
                        </label>
                    </div>
                </div>

                <div class="form-group full">
                    <label>Campos que usarán los productos/servicios de esta categoría</label>
                    <div class="config-box">
                        <div class="config-grid">
                            <label><input type="checkbox" id="usa_marca" name="usa_marca" <?= !empty($categoria['usa_marca']) ? 'checked' : '' ?>> Usa marca</label>
                            <label><input type="checkbox" id="usa_sku" name="usa_sku" <?= !empty($categoria['usa_sku']) ? 'checked' : '' ?>> Usa SKU</label>
                            <label><input type="checkbox" id="usa_codigo" name="usa_codigo" <?= !empty($categoria['usa_codigo']) ? 'checked' : '' ?>> Usa código</label>
                            <label><input type="checkbox" id="usa_peso" name="usa_peso" <?= !empty($categoria['usa_peso']) ? 'checked' : '' ?>> Usa peso / unidad</label>
                            <label><input type="checkbox" id="usa_vencimiento" name="usa_vencimiento" <?= !empty($categoria['usa_vencimiento']) ? 'checked' : '' ?>> Usa vencimiento</label>
                        </div>
                        <div class="help">Para Servicios se ocultan marca, SKU, código, peso y vencimiento. El producto funcionará como servicio cotizable.</div>
                    </div>
                </div>

                <div class="form-group full">
                    <label>
                        <input type="checkbox" name="activo" <?= !empty($categoria['activo']) ? 'checked' : '' ?>>
                        Categoría activa
                    </label>
                </div>
            </div>

            <button class="btn green" type="submit">Guardar categoría</button>
        </form>
    </div>

    <div class="panel">
        <h3>Vista previa</h3>
        <br>
        <div class="preview-card" id="previewCard">
            <div class="preview-icon" id="previewIcon"><?= h($categoria['icono'] ?? '📦') ?></div>
            <div>
                <div class="preview-title" id="previewTitle"><?= h($categoria['nombre'] ?: 'Nombre categoría') ?></div>
                <div class="preview-slug" id="previewSlug"><?= h($categoria['slug'] ?: 'slug-categoria') ?></div>
            </div>
        </div>
    </div>
</div>

<script>
const nombre = document.getElementById("nombreCategoria");
const slug = document.getElementById("slugCategoria");
const icono = document.getElementById("iconoCategoria");
const color = document.getElementById("colorCategoria");
const colorTexto = document.getElementById("colorTexto");

const previewCard = document.getElementById("previewCard");
const previewIcon = document.getElementById("previewIcon");
const previewTitle = document.getElementById("previewTitle");
const previewSlug = document.getElementById("previewSlug");

function setCampo(id, valor){
    const el = document.getElementById(id);
    if(el) el.checked = valor;
}

function tipoActual(){
    const checked = document.querySelector('input[name="tipo_categoria"]:checked');
    return checked ? checked.value : "normal";
}

function aplicarTipoCategoria(){
    const tipo = tipoActual();

    if(tipo === "normal"){
        setCampo("usa_marca", true);
        setCampo("usa_sku", true);
        setCampo("usa_codigo", true);
        setCampo("usa_peso", false);
        setCampo("usa_vencimiento", false);
    }

    if(tipo === "simple"){
        setCampo("usa_marca", false);
        setCampo("usa_sku", false);
        setCampo("usa_codigo", false);
        setCampo("usa_peso", false);
        setCampo("usa_vencimiento", false);
    }

    if(tipo === "alimenticio"){
        setCampo("usa_marca", false);
        setCampo("usa_sku", false);
        setCampo("usa_codigo", false);
        setCampo("usa_peso", true);
        setCampo("usa_vencimiento", true);
    }

    if(tipo === "peso"){
        setCampo("usa_marca", false);
        setCampo("usa_sku", false);
        setCampo("usa_codigo", false);
        setCampo("usa_peso", true);
        setCampo("usa_vencimiento", false);
    }

    if(tipo === "servicio"){
        setCampo("usa_marca", false);
        setCampo("usa_sku", false);
        setCampo("usa_codigo", false);
        setCampo("usa_peso", false);
        setCampo("usa_vencimiento", false);
    }

    if(tipo === "digital"){
        setCampo("usa_marca", false);
        setCampo("usa_sku", true);
        setCampo("usa_codigo", true);
        setCampo("usa_peso", false);
        setCampo("usa_vencimiento", false);
    }
}

function actualizarPreview(){
    previewCard.style.background = color.value;
    colorTexto.value = color.value;
    previewIcon.textContent = icono.value || "📦";
    previewTitle.textContent = nombre.value || "Nombre categoría";
    previewSlug.textContent = slug.value || "slug-categoria";
}

[nombre, slug, icono, color].forEach(el => el.addEventListener("input", actualizarPreview));
actualizarPreview();
</script>

<?php admin_footer(); ?>
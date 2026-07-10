<?php
require_once "../config/db.php";
require_once "layout.php";

$id = $_GET['id'] ?? 0;

$categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$subcategoria = [
    'categoria_id' => '',
    'nombre' => '',
    'slug' => '',
    'activo' => 1
];

if($id){
    $stmt = $pdo->prepare("SELECT * FROM subcategorias WHERE id = ?");
    $stmt->execute([$id]);
    $subcategoria = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$subcategoria){ die("Subcategoría no encontrada"); }
}

function crearSlug($texto){
    $texto = strtolower(trim($texto));
    $texto = preg_replace('/[^a-z0-9]+/i', '-', $texto);
    return trim($texto, '-');
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $categoria_id = $_POST['categoria_id'] ?: null;
    $nombre = trim($_POST['nombre']);
    $slug = trim($_POST['slug']) ?: crearSlug($nombre);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if($id){
        $stmt = $pdo->prepare("UPDATE subcategorias SET categoria_id=?, nombre=?, slug=?, activo=? WHERE id=?");
        $stmt->execute([$categoria_id, $nombre, $slug, $activo, $id]);
    }else{
        $stmt = $pdo->prepare("INSERT INTO subcategorias (categoria_id, nombre, slug, activo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$categoria_id, $nombre, $slug, $activo]);
    }

    header("Location: subcategorias.php");
    exit;
}

admin_header($id ? "Editar subcategoría" : "Nueva subcategoría", "categorias");
?>

<div class="panel">
    <div class="panel-header">
        <h3><?= $id ? 'Editar subcategoría' : 'Nueva subcategoría' ?></h3>
        <a class="btn gray" href="subcategorias.php">Volver</a>
    </div>

    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Categoría padre</label>
                <select name="categoria_id" required>
                    <option value="">Seleccione categoría</option>
                    <?php foreach($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $subcategoria['categoria_id']==$c['id']?'selected':'' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Nombre</label>
                <input name="nombre" required value="<?= htmlspecialchars($subcategoria['nombre']) ?>">
            </div>

            <div class="form-group">
                <label>Slug / URL</label>
                <input name="slug" value="<?= htmlspecialchars($subcategoria['slug']) ?>" placeholder="Ejemplo: laptops">
            </div>

            <div class="form-group full">
                <label><input type="checkbox" name="activo" <?= $subcategoria['activo'] ? 'checked' : '' ?>> Activo</label>
            </div>
        </div>

        <button class="btn green" type="submit">Guardar</button>
    </form>
</div>

<?php admin_footer(); ?>
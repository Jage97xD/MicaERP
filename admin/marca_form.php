<?php
require_once "../config/db.php";
require_once "layout.php";

$id = $_GET['id'] ?? 0;
$marca = [
    'nombre' => '',
    'slug' => '',
    'activo' => 1
];

if($id){
    $stmt = $pdo->prepare("SELECT * FROM marcas WHERE id = ?");
    $stmt->execute([$id]);
    $marca = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$marca){ die("Marca no encontrada"); }
}

function crearSlug($texto){
    $texto = strtolower(trim($texto));
    $texto = preg_replace('/[^a-z0-9]+/i', '-', $texto);
    return trim($texto, '-');
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre']);
    $slug = trim($_POST['slug']) ?: crearSlug($nombre);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if($id){
        $stmt = $pdo->prepare("UPDATE marcas SET nombre=?, slug=?, activo=? WHERE id=?");
        $stmt->execute([$nombre, $slug, $activo, $id]);
    }else{
        $stmt = $pdo->prepare("INSERT INTO marcas (nombre, slug, activo) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $slug, $activo]);
    }

    header("Location: marcas.php");
    exit;
}

admin_header($id ? "Editar marca" : "Nueva marca", "marcas");
?>

<div class="panel">
    <div class="panel-header">
        <h3><?= $id ? 'Editar marca' : 'Nueva marca' ?></h3>
        <a class="btn gray" href="marcas.php">Volver</a>
    </div>

    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre</label>
                <input name="nombre" required value="<?= htmlspecialchars($marca['nombre']) ?>">
            </div>

            <div class="form-group">
                <label>Slug / URL</label>
                <input name="slug" value="<?= htmlspecialchars($marca['slug']) ?>" placeholder="Ejemplo: hp">
            </div>

            <div class="form-group full">
                <label><input type="checkbox" name="activo" <?= $marca['activo'] ? 'checked' : '' ?>> Activo</label>
            </div>
        </div>

        <button class="btn green" type="submit">Guardar</button>
    </form>
</div>

<?php admin_footer(); ?>
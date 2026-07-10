<?php
require_once "../config/db.php";
require_once "layout.php";

$pdo->exec("
CREATE TABLE IF NOT EXISTS home_bloques (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(180) NOT NULL,
  subtitulo VARCHAR(255),
  contenido TEXT,
  tipo ENUM('info','banner','html') DEFAULT 'info',
  orden INT DEFAULT 0,
  activo TINYINT DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = $_POST['id'] ?? '';
    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $tipo = $_POST['tipo'] ?? 'info';
    $orden = (int)($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if($id){
        $stmt = $pdo->prepare("UPDATE home_bloques SET titulo=?, subtitulo=?, contenido=?, tipo=?, orden=?, activo=? WHERE id=?");
        $stmt->execute([$titulo,$subtitulo,$contenido,$tipo,$orden,$activo,$id]);
    }else{
        $stmt = $pdo->prepare("INSERT INTO home_bloques (titulo, subtitulo, contenido, tipo, orden, activo) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$titulo,$subtitulo,$contenido,$tipo,$orden,$activo]);
    }

    header("Location: home_bloques.php");
    exit;
}

if(isset($_GET['delete'])){
    $stmt = $pdo->prepare("DELETE FROM home_bloques WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: home_bloques.php");
    exit;
}

$editar = null;
if(isset($_GET['id'])){
    $stmt = $pdo->prepare("SELECT * FROM home_bloques WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$bloques = $pdo->query("SELECT * FROM home_bloques ORDER BY orden ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

admin_header("Bloques de inicio", "bloques");
?>

<style>
.grid-bloques{display:grid;grid-template-columns:1fr 1.4fr;gap:22px}
.preview-box{border:1px solid #e5e7eb;background:#f8fafc;border-radius:14px;padding:14px;margin-top:12px}
@media(max-width:900px){.grid-bloques{grid-template-columns:1fr}}
</style>

<div class="grid-bloques">
    <div class="panel">
        <div class="panel-header">
            <h3><?= $editar ? 'Editar bloque' : 'Nuevo bloque' ?></h3>
        </div>

        <form method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editar['id'] ?? '') ?>">

            <div class="form-grid">
                <div class="form-group full">
                    <label>Título</label>
                    <input name="titulo" required value="<?= htmlspecialchars($editar['titulo'] ?? '') ?>" placeholder="Ejemplo: Promoción de la semana">
                </div>

                <div class="form-group full">
                    <label>Subtítulo</label>
                    <input name="subtitulo" value="<?= htmlspecialchars($editar['subtitulo'] ?? '') ?>" placeholder="Ejemplo: Solo por tiempo limitado">
                </div>

                <div class="form-group full">
                    <label>Contenido</label>
                    <textarea name="contenido" rows="6" placeholder="Texto o HTML según el tipo"><?= htmlspecialchars($editar['contenido'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="info" <?= ($editar['tipo'] ?? 'info')==='info'?'selected':'' ?>>Cuadro informativo</option>
                        <option value="banner" <?= ($editar['tipo'] ?? '')==='banner'?'selected':'' ?>>Banner ancho</option>
                        <option value="html" <?= ($editar['tipo'] ?? '')==='html'?'selected':'' ?>>HTML libre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Orden</label>
                    <input type="number" name="orden" value="<?= htmlspecialchars($editar['orden'] ?? '0') ?>">
                </div>

                <div class="form-group full">
                    <label><input type="checkbox" name="activo" <?= ($editar['activo'] ?? 1) ? 'checked' : '' ?>> Activo</label>
                </div>
            </div>

            <button class="btn green" type="submit">Guardar bloque</button>
            <?php if($editar): ?><a class="btn gray" href="home_bloques.php">Cancelar</a><?php endif; ?>
        </form>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>Bloques registrados</h3>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($bloques as $b): ?>
                <tr>
                    <td><?= $b['orden'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($b['titulo']) ?></strong><br>
                        <small><?= htmlspecialchars($b['subtitulo'] ?? '') ?></small>
                    </td>
                    <td><?= htmlspecialchars($b['tipo']) ?></td>
                    <td><span class="badge <?= $b['activo']?'ok':'warn' ?>"><?= $b['activo']?'Activo':'Inactivo' ?></span></td>
                    <td>
                        <a class="btn gray" href="home_bloques.php?id=<?= $b['id'] ?>">Editar</a>
                        <a class="btn red" onclick="return confirm('¿Eliminar bloque?')" href="home_bloques.php?delete=<?= $b['id'] ?>">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($bloques)===0): ?>
                <tr><td colspan="5">Todavía no hay bloques.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
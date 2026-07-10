<?php
require_once "../config/db.php";
require_once "layout.php";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = $_POST['id'] ?? '';
    $nombre = trim($_POST['nombre']);
    $clave = strtolower(trim($_POST['clave']));
    $clave = preg_replace('/[^a-z0-9_]+/i', '_', $clave);
    $valor = trim($_POST['valor']);
    $ubicacion = $_POST['ubicacion'] ?? 'footer';
    $orden = (int)($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if($id){
        $stmt = $pdo->prepare("UPDATE configuracion_campos SET nombre=?, clave=?, valor=?, ubicacion=?, orden=?, activo=? WHERE id=?");
        $stmt->execute([$nombre,$clave,$valor,$ubicacion,$orden,$activo,$id]);
    }else{
        $stmt = $pdo->prepare("INSERT INTO configuracion_campos (nombre, clave, valor, ubicacion, orden, activo) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$nombre,$clave,$valor,$ubicacion,$orden,$activo]);
    }

    header("Location: configuracion_campos.php");
    exit;
}

if(isset($_GET['delete'])){
    $stmt = $pdo->prepare("DELETE FROM configuracion_campos WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: configuracion_campos.php");
    exit;
}

$editar = null;
if(isset($_GET['id'])){
    $stmt = $pdo->prepare("SELECT * FROM configuracion_campos WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$campos = $pdo->query("SELECT * FROM configuracion_campos ORDER BY ubicacion, orden, id")->fetchAll(PDO::FETCH_ASSOC);

admin_header("Campos personalizados", "campos");
?>

<style>
.grid-campos{display:grid;grid-template-columns:1fr 1.4fr;gap:22px}
@media(max-width:900px){.grid-campos{grid-template-columns:1fr}}
</style>

<div class="panel" style="margin-bottom:18px;background:#eef6ff;border-color:#bfdbfe">
    <strong>¿Dónde aparece?</strong>
    <p style="margin-top:8px;color:#334155">Los campos con ubicación <b>Footer</b> aparecen en el pie de página. Los de <b>Contacto</b> aparecen en la página Contáctanos. Los de <b>Oculto</b> quedan guardados para uso interno.</p>
</div>

<div class="grid-campos">
    <div class="panel">
        <div class="panel-header"><div><h3><?= $editar ? 'Editar campo' : 'Nuevo campo' ?></h3><p style="margin:5px 0 0;color:#64748b">Sirve para agregar datos extra sin tocar código. Ejemplo: otro WhatsApp, tienda disponible, correo de ventas, nota legal o información adicional para footer/contacto.</p></div></div>
        <form method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editar['id'] ?? '') ?>">
            <div class="form-grid">
                <div class="form-group full"><label>Nombre visible</label><input name="nombre" required value="<?= htmlspecialchars($editar['nombre'] ?? '') ?>" placeholder="Ejemplo: WhatsApp ventas 2"></div>
                <div class="form-group full"><label>Clave interna</label><input name="clave" required value="<?= htmlspecialchars($editar['clave'] ?? '') ?>" placeholder="whatsapp_ventas_2"></div>
                <div class="form-group full"><label>Valor</label><textarea name="valor"><?= htmlspecialchars($editar['valor'] ?? '') ?></textarea></div>
                <div class="form-group"><label>Ubicación</label><select name="ubicacion"><option value="header" <?= ($editar['ubicacion']??'')==='header'?'selected':'' ?>>Header</option><option value="footer" <?= ($editar['ubicacion']??'footer')==='footer'?'selected':'' ?>>Footer</option><option value="contacto" <?= ($editar['ubicacion']??'')==='contacto'?'selected':'' ?>>Contacto</option><option value="oculto" <?= ($editar['ubicacion']??'')==='oculto'?'selected':'' ?>>Oculto</option></select></div>
                <div class="form-group"><label>Orden</label><input type="number" name="orden" value="<?= htmlspecialchars($editar['orden'] ?? '0') ?>"></div>
                <div class="form-group full"><label><input type="checkbox" name="activo" <?= ($editar['activo'] ?? 1) ? 'checked' : '' ?>> Activo</label></div>
            </div>
            <button class="btn green" type="submit">Guardar campo</button>
        </form>
    </div>

    <div class="panel">
        <div class="panel-header"><h3>Campos registrados</h3></div>
        <table class="table">
            <thead><tr><th>Nombre</th><th>Clave</th><th>Ubicación</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach($campos as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nombre']) ?></td>
                    <td><?= htmlspecialchars($c['clave']) ?></td>
                    <td><?= htmlspecialchars($c['ubicacion']) ?></td>
                    <td><span class="badge <?= $c['activo']?'ok':'warn' ?>"><?= $c['activo']?'Activo':'Inactivo' ?></span></td>
                    <td>
                        <a class="btn gray" href="configuracion_campos.php?id=<?= $c['id'] ?>">Editar</a>
                        <a class="btn red" onclick="return confirm('¿Eliminar campo?')" href="configuracion_campos.php?delete=<?= $c['id'] ?>">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($campos)===0): ?><tr><td colspan="5">No hay campos personalizados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
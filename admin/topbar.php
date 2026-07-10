<?php
require_once "../config/db.php";
require_once "layout.php";

function htop($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function topbarEnsure($pdo){
    $pdo->exec("CREATE TABLE IF NOT EXISTS topbar_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grupo ENUM('izquierda','derecha') DEFAULT 'izquierda',
        icono VARCHAR(20) NULL,
        texto VARCHAR(180) NOT NULL,
        tipo_enlace ENUM('ninguno','url','maps','contacto','interno') DEFAULT 'ninguno',
        url VARCHAR(255) NULL,
        visible TINYINT(1) DEFAULT 1,
        nueva_pestana TINYINT(1) DEFAULT 0,
        orden INT DEFAULT 0,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $count = (int)$pdo->query("SELECT COUNT(*) FROM topbar_items")->fetchColumn();
    if($count === 0){
        $ins = $pdo->prepare("INSERT INTO topbar_items (grupo,icono,texto,tipo_enlace,url,visible,nueva_pestana,orden) VALUES (?,?,?,?,?,?,?,?)");
        $ins->execute(['izquierda','🚚','Envíos a todo el Perú','ninguno','',1,0,1]);
        $ins->execute(['izquierda','🛡️','Garantía en productos','ninguno','',1,0,2]);
        $ins->execute(['izquierda','📍','Mercado La Chacra - Lurigancho','maps','',1,1,3]);
        $ins->execute(['derecha','','Facebook','url','',1,1,10]);
        $ins->execute(['derecha','','Instagram','url','',1,1,11]);
        $ins->execute(['derecha','','TikTok','url','',1,1,12]);
    }
}

topbarEnsure($pdo);
$mensaje = '';

if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    if($id > 0){
        $stmt = $pdo->prepare("DELETE FROM topbar_items WHERE id=?");
        $stmt->execute([$id]);
        erp_auditoria($pdo,'topbar','eliminar','Eliminó item de TopBar','topbar_items',$id);
    }
    header("Location: topbar.php"); exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['guardar_items'])){
        foreach($_POST['items'] ?? [] as $id => $data){
            $id = (int)$id;
            if($id <= 0) continue;
            $grupo = in_array(($data['grupo'] ?? 'izquierda'), ['izquierda','derecha'], true) ? $data['grupo'] : 'izquierda';
            $tipo = in_array(($data['tipo_enlace'] ?? 'ninguno'), ['ninguno','url','maps','contacto','interno'], true) ? $data['tipo_enlace'] : 'ninguno';
            $stmt = $pdo->prepare("UPDATE topbar_items SET grupo=?, icono=?, texto=?, tipo_enlace=?, url=?, visible=?, nueva_pestana=?, orden=? WHERE id=?");
            $stmt->execute([
                $grupo,
                trim($data['icono'] ?? ''),
                trim($data['texto'] ?? ''),
                $tipo,
                trim($data['url'] ?? ''),
                isset($data['visible']) ? 1 : 0,
                isset($data['nueva_pestana']) ? 1 : 0,
                (int)($data['orden'] ?? 0),
                $id
            ]);
        }
        erp_auditoria($pdo,'topbar','editar','Actualizó elementos de TopBar');
        $mensaje = 'TopBar actualizada correctamente.';
    }

    if(isset($_POST['nuevo_item'])){
        $grupo = in_array(($_POST['grupo'] ?? 'izquierda'), ['izquierda','derecha'], true) ? $_POST['grupo'] : 'izquierda';
        $tipo = in_array(($_POST['tipo_enlace'] ?? 'ninguno'), ['ninguno','url','maps','contacto','interno'], true) ? $_POST['tipo_enlace'] : 'ninguno';
        $texto = trim($_POST['texto'] ?? '');
        if($texto !== ''){
            $stmt = $pdo->prepare("INSERT INTO topbar_items (grupo,icono,texto,tipo_enlace,url,visible,nueva_pestana,orden) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $grupo,
                trim($_POST['icono'] ?? ''),
                $texto,
                $tipo,
                trim($_POST['url'] ?? ''),
                isset($_POST['visible']) ? 1 : 0,
                isset($_POST['nueva_pestana']) ? 1 : 0,
                (int)($_POST['orden'] ?? 0)
            ]);
            erp_auditoria($pdo,'topbar','crear','Creó item de TopBar','topbar_items',$pdo->lastInsertId());
            header("Location: topbar.php?ok=1"); exit;
        }
    }
}

$items = $pdo->query("SELECT * FROM topbar_items ORDER BY grupo ASC, orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
admin_header("TopBar", "topbar");
?>
<style>
.topbar-admin-grid{display:grid;grid-template-columns:1.4fr .8fr;gap:22px}.topbar-item-card{background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:16px;margin-bottom:14px}.topbar-item-head{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:12px}.topbar-admin-fields{display:grid;grid-template-columns:70px 1fr 130px 1fr 70px 120px;gap:10px;align-items:end}.topbar-admin-fields input,.topbar-admin-fields select{width:100%;padding:11px;border:1px solid #d8dee9;border-radius:10px}.topbar-checks{display:flex;gap:16px;flex-wrap:wrap;margin-top:10px}.preview-topbar{background:#07162f;color:white;border-radius:14px;padding:12px 16px;display:flex;justify-content:space-between;gap:18px;flex-wrap:wrap}.preview-topbar span,.preview-topbar a{color:white;text-decoration:none;margin-right:18px;font-weight:bold}.notice-ok{background:#dcfce7;color:#166534;border:1px solid #86efac;padding:12px;border-radius:12px;margin-bottom:15px;font-weight:bold}.help{color:#64748b;font-size:13px;margin-top:6px}@media(max-width:1100px){.topbar-admin-grid,.topbar-admin-fields{grid-template-columns:1fr}}
</style>

<?php if($mensaje || isset($_GET['ok'])): ?><div class="notice-ok"><?= htop($mensaje ?: 'Item creado correctamente.') ?></div><?php endif; ?>

<div class="topbar-admin-grid">
    <div class="panel">
        <div class="panel-header">
            <div><h3>TopBar administrable</h3><p class="help">Administra los textos de la barra superior. El tipo “Google Maps” usa automáticamente el enlace guardado en Configuración.</p></div>
            <button class="btn green" form="formTopbar" type="submit">Guardar cambios</button>
        </div>

        <div class="preview-topbar" style="margin-bottom:18px;">
            <div><?php foreach($items as $it){ if($it['visible'] && $it['grupo']==='izquierda') echo '<span>'.htop(($it['icono']? $it['icono'].' ':'').$it['texto']).'</span>'; } ?></div>
            <div><?php foreach($items as $it){ if($it['visible'] && $it['grupo']==='derecha') echo '<span>'.htop(($it['icono']? $it['icono'].' ':'').$it['texto']).'</span>'; } ?></div>
        </div>

        <form method="POST" id="formTopbar">
            <input type="hidden" name="guardar_items" value="1">
            <?php foreach($items as $it): ?>
                <div class="topbar-item-card">
                    <div class="topbar-item-head">
                        <strong><?= htop(($it['icono'] ? $it['icono'].' ' : '').$it['texto']) ?></strong>
                        <a class="btn red" href="topbar.php?delete=<?= (int)$it['id'] ?>" onclick="return confirm('¿Eliminar este item?')">Eliminar</a>
                    </div>
                    <div class="topbar-admin-fields">
                        <div><label>Icono</label><input name="items[<?= (int)$it['id'] ?>][icono]" value="<?= htop($it['icono']) ?>"></div>
                        <div><label>Texto</label><input name="items[<?= (int)$it['id'] ?>][texto]" value="<?= htop($it['texto']) ?>"></div>
                        <div><label>Grupo</label><select name="items[<?= (int)$it['id'] ?>][grupo]"><option value="izquierda" <?= $it['grupo']==='izquierda'?'selected':'' ?>>Izquierda</option><option value="derecha" <?= $it['grupo']==='derecha'?'selected':'' ?>>Derecha</option></select></div>
                        <div><label>Tipo enlace</label><select name="items[<?= (int)$it['id'] ?>][tipo_enlace]"><option value="ninguno" <?= $it['tipo_enlace']==='ninguno'?'selected':'' ?>>Sin enlace</option><option value="url" <?= $it['tipo_enlace']==='url'?'selected':'' ?>>URL</option><option value="maps" <?= $it['tipo_enlace']==='maps'?'selected':'' ?>>Google Maps</option><option value="contacto" <?= $it['tipo_enlace']==='contacto'?'selected':'' ?>>Contacto</option><option value="interno" <?= $it['tipo_enlace']==='interno'?'selected':'' ?>>Página interna</option></select></div>
                        <div><label>Orden</label><input type="number" name="items[<?= (int)$it['id'] ?>][orden]" value="<?= (int)$it['orden'] ?>"></div>
                        <div><label>URL</label><input name="items[<?= (int)$it['id'] ?>][url]" value="<?= htop($it['url']) ?>"></div>
                    </div>
                    <div class="topbar-checks">
                        <label><input type="checkbox" name="items[<?= (int)$it['id'] ?>][visible]" <?= $it['visible']?'checked':'' ?>> Visible</label>
                        <label><input type="checkbox" name="items[<?= (int)$it['id'] ?>][nueva_pestana]" <?= $it['nueva_pestana']?'checked':'' ?>> Abrir en nueva pestaña</label>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>
    </div>

    <div class="panel">
        <div class="panel-header"><h3>Nuevo item</h3></div>
        <form method="POST">
            <input type="hidden" name="nuevo_item" value="1">
            <div class="form-grid">
                <div class="form-group"><label>Icono</label><input name="icono" placeholder="📍"></div>
                <div class="form-group"><label>Orden</label><input type="number" name="orden" value="20"></div>
                <div class="form-group full"><label>Texto</label><input name="texto" required placeholder="Ejemplo: Libro de reclamaciones"></div>
                <div class="form-group"><label>Grupo</label><select name="grupo"><option value="izquierda">Izquierda</option><option value="derecha">Derecha</option></select></div>
                <div class="form-group"><label>Tipo enlace</label><select name="tipo_enlace"><option value="ninguno">Sin enlace</option><option value="url">URL</option><option value="maps">Google Maps</option><option value="contacto">Contacto</option><option value="interno">Página interna</option></select></div>
                <div class="form-group full"><label>URL</label><input name="url" placeholder="https://... o contacto.php"></div>
                <div class="form-group full"><label><input type="checkbox" name="visible" checked> Visible</label></div>
                <div class="form-group full"><label><input type="checkbox" name="nueva_pestana"> Abrir en nueva pestaña</label></div>
                <div class="form-group full"><button class="btn green" type="submit">Agregar item</button></div>
            </div>
        </form>
    </div>
</div>

<?php admin_footer(); ?>

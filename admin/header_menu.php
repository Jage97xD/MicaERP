<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$pdo->exec("CREATE TABLE IF NOT EXISTS header_menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(120) NOT NULL,
  icono VARCHAR(20) DEFAULT '',
  url VARCHAR(255) NOT NULL,
  tipo VARCHAR(40) DEFAULT 'link',
  visible TINYINT DEFAULT 1,
  visible_desktop TINYINT DEFAULT 1,
  visible_mobile TINYINT DEFAULT 1,
  orden INT DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$count = (int)$pdo->query("SELECT COUNT(*) FROM header_menu_items")->fetchColumn();
if($count === 0){
    $seed = [
        ['Inicio','🏠','tienda_visual_v3.php','link',1],
        ['Productos','🛍','tienda_visual_v3.php#productos','link',2],
        ['Categorías','📂','#categorias','categorias',3],
        ['Marcas','🏷','marcas.php','link',4],
        ['Ofertas','⭐','ofertas.php','link',5],
        ['Mis pedidos','📦','cliente/mis_pedidos.php','link',6],
        ['Contáctenos','📞','contacto.php','link',7],
    ];
    $stmt = $pdo->prepare("INSERT INTO header_menu_items (titulo,icono,url,tipo,visible,visible_desktop,visible_mobile,orden) VALUES (?,?,?,?,1,1,1,?)");
    foreach($seed as $s){ $stmt->execute($s); }
}

if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    if($id > 0){
        $stmt = $pdo->prepare("DELETE FROM header_menu_items WHERE id=?");
        $stmt->execute([$id]);
    }
    header("Location: header_menu.php");
    exit;
}

if(isset($_GET['toggle'])){
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE header_menu_items SET visible = IF(visible=1,0,1) WHERE id=?");
    $stmt->execute([$id]);
    header("Location: header_menu.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = (int)($_POST['id'] ?? 0);
    $datos = [
        trim($_POST['titulo'] ?? ''),
        trim($_POST['icono'] ?? ''),
        trim($_POST['url'] ?? ''),
        $_POST['tipo'] ?? 'link',
        isset($_POST['visible']) ? 1 : 0,
        isset($_POST['visible_desktop']) ? 1 : 0,
        isset($_POST['visible_mobile']) ? 1 : 0,
        (int)($_POST['orden'] ?? 0)
    ];

    if($datos[0] !== '' && $datos[2] !== ''){
        if($id > 0){
            $stmt = $pdo->prepare("UPDATE header_menu_items SET titulo=?,icono=?,url=?,tipo=?,visible=?,visible_desktop=?,visible_mobile=?,orden=? WHERE id=?");
            $stmt->execute([...$datos, $id]);
        }else{
            $stmt = $pdo->prepare("INSERT INTO header_menu_items (titulo,icono,url,tipo,visible,visible_desktop,visible_mobile,orden) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute($datos);
        }
    }

    header("Location: header_menu.php?ok=1");
    exit;
}

$editar = null;
if(isset($_GET['id'])){
    $stmt = $pdo->prepare("SELECT * FROM header_menu_items WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$items = $pdo->query("SELECT * FROM header_menu_items ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

admin_header("Menú público", "builder");
?>
<style>
.menu-builder{display:grid;grid-template-columns:1fr 1.4fr;gap:22px}.help{color:#64748b;font-size:13px;margin-top:6px}.menu-preview{display:flex;gap:10px;flex-wrap:wrap;background:#111827;padding:14px;border-radius:16px}.menu-preview span{background:#1f2937;color:white;padding:10px 13px;border-radius:12px;font-weight:900}.muted{color:#64748b}.status{padding:6px 10px;border-radius:999px;font-size:12px;font-weight:900}.status.on{background:#dcfce7;color:#166534}.status.off{background:#fee2e2;color:#991b1b}@media(max-width:900px){.menu-builder{grid-template-columns:1fr}}
</style>

<div class="menu-builder">
    <div class="panel">
        <div class="panel-header"><h3><?= $editar ? 'Editar item' : 'Agregar item' ?></h3></div>
        <?php if(isset($_GET['ok'])): ?><div style="background:#dcfce7;color:#166534;padding:12px;border-radius:12px;margin-bottom:14px;font-weight:900">Menú guardado correctamente.</div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="id" value="<?= h($editar['id'] ?? '') ?>">
            <div class="form-grid">
                <div class="form-group"><label>Icono</label><input name="icono" value="<?= h($editar['icono'] ?? '') ?>" placeholder="📦"></div>
                <div class="form-group"><label>Orden</label><input type="number" name="orden" value="<?= h($editar['orden'] ?? '0') ?>"></div>
                <div class="form-group full"><label>Título</label><input name="titulo" required value="<?= h($editar['titulo'] ?? '') ?>" placeholder="Ejemplo: Mis pedidos"></div>
                <div class="form-group full"><label>URL</label><input name="url" required value="<?= h($editar['url'] ?? '') ?>" placeholder="cliente/mis_pedidos.php"></div>
                <div class="form-group"><label>Tipo</label><select name="tipo"><option value="link" <?= ($editar['tipo']??'link')==='link'?'selected':'' ?>>Link normal</option><option value="categorias" <?= ($editar['tipo']??'')==='categorias'?'selected':'' ?>>Desplegable categorías</option></select></div>
                <div class="form-group"><label>Visibilidad</label><label><input type="checkbox" name="visible" <?= ($editar['visible']??1)?'checked':'' ?>> Visible</label><label><input type="checkbox" name="visible_desktop" <?= ($editar['visible_desktop']??1)?'checked':'' ?>> Escritorio</label><label><input type="checkbox" name="visible_mobile" <?= ($editar['visible_mobile']??1)?'checked':'' ?>> Móvil</label></div>
                <div class="form-group full"><button class="btn green" type="submit">Guardar item</button> <a class="btn gray" href="header_menu.php">Nuevo</a></div>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-header"><div><h3>Items del menú</h3><p class="help">Este menú alimenta la barra pública de la tienda. Puedes agregar “Mis pedidos”, “Estado de pedido”, campañas o páginas futuras.</p></div></div>
        <div class="menu-preview">
            <?php foreach($items as $it): ?><?php if($it['visible']): ?><span><?= h(($it['icono'] ? $it['icono'].' ' : '').$it['titulo']) ?></span><?php endif; ?><?php endforeach; ?>
        </div>
        <br>
        <table class="table">
            <thead><tr><th>Orden</th><th>Item</th><th>URL</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach($items as $it): ?>
                <tr>
                    <td><?= (int)$it['orden'] ?></td>
                    <td><strong><?= h(($it['icono'] ? $it['icono'].' ' : '').$it['titulo']) ?></strong><br><span class="muted"><?= h($it['tipo']) ?></span></td>
                    <td><?= h($it['url']) ?></td>
                    <td><span class="status <?= $it['visible']?'on':'off' ?>"><?= $it['visible']?'Visible':'Oculto' ?></span></td>
                    <td class="actions"><a class="btn gray" href="header_menu.php?id=<?= (int)$it['id'] ?>">Editar</a> <a class="btn" href="header_menu.php?toggle=<?= (int)$it['id'] ?>">On/Off</a> <a class="btn red" onclick="return confirm('¿Eliminar item?')" href="header_menu.php?delete=<?= (int)$it['id'] ?>">Eliminar</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_footer(); ?>

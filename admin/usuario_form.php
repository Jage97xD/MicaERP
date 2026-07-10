<?php
require_once "../config/db.php";
require_once "layout.php";

$id = (int)($_GET['id'] ?? 0);
requerirPermiso('usuarios', $id ? 'editar' : 'crear');

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$roles = $pdo->query("SELECT * FROM admin_roles WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT id,nombre,icono FROM categorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$tiendas = $pdo->query("SELECT t.id,t.nombre,t.empresa_id,c.nombre AS categoria FROM marketplace_tiendas t LEFT JOIN categorias c ON c.id=t.categoria_id WHERE t.activo=1 ORDER BY t.nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$empresas = $pdo->query("SELECT id,nombre FROM marketplace_empresas WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$usuario = [
    'nombre'=>'', 'usuario'=>'', 'correo'=>'', 'rol_id'=>'', 'rol'=>'Ventas',
    'activo'=>1, 'debe_cambiar_password'=>0, 'tienda_id'=>'', 'empresa_id'=>''
];
$categoriasActuales = [];

if($id > 0){
    $stmt = $pdo->prepare("SELECT * FROM admin_usuarios WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$row) die('Usuario no encontrado.');
    $usuario = array_merge($usuario, $row);

    $stmt = $pdo->prepare("SELECT categoria_id FROM admin_usuario_categorias WHERE usuario_id=?");
    $stmt->execute([$id]);
    $categoriasActuales = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    try{
        $nombre = trim($_POST['nombre'] ?? '');
        $user = trim($_POST['usuario'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $rolId = (int)($_POST['rol_id'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $debe = isset($_POST['debe_cambiar_password']) ? 1 : 0;
        $tiendaId = (int)($_POST['tienda_id'] ?? 0);
        $empresaId = (int)($_POST['empresa_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $catsPost = array_values(array_unique(array_map('intval', $_POST['categoria_ids'] ?? [])));

        if($nombre === '') throw new Exception('El nombre es obligatorio.');
        if($user === '') throw new Exception('El usuario es obligatorio.');
        if($rolId <= 0) throw new Exception('Selecciona un rol.');
        if($password !== '' && strlen($password) < 8) throw new Exception('La contraseña debe tener mínimo 8 caracteres.');
        if($password !== $password2) throw new Exception('Las contraseñas no coinciden.');
        if($id === 0 && $password === '') throw new Exception('La contraseña es obligatoria para nuevos usuarios.');

        $stmt = $pdo->prepare("SELECT id FROM admin_usuarios WHERE usuario=? AND id<>? LIMIT 1");
        $stmt->execute([$user, $id]);
        if($stmt->fetchColumn()) throw new Exception('Ese usuario ya existe.');

        $st = $pdo->prepare("SELECT nombre FROM admin_roles WHERE id=?");
        $st->execute([$rolId]);
        $rolNombre = $st->fetchColumn() ?: 'Ventas';

        $pdo->beginTransaction();

        if($id > 0){
            $datos = [$nombre,$user,$correo,$rolNombre,$rolId,($rolNombre==='Vendedor'?$tiendaId:null),(in_array($rolNombre,['Supervisor','Vendedor'],true)?$empresaId:null),$activo,$debe];
            $sql = "UPDATE admin_usuarios SET nombre=?, usuario=?, correo=?, rol=?, rol_id=?, tienda_id=?, empresa_id=?, activo=?, debe_cambiar_password=?, intentos=0, bloqueado_hasta=NULL";
            if($password !== ''){
                $sql .= ", password_hash=?";
                $datos[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id=?";
            $datos[] = $id;
            $pdo->prepare($sql)->execute($datos);
            $usuarioId = $id;
            erp_auditoria($pdo,'usuarios','editar','Editó usuario '.$user,'admin_usuarios',$id);
        }else{
            $stmt = $pdo->prepare("INSERT INTO admin_usuarios (nombre,usuario,correo,password_hash,rol,rol_id,tienda_id,empresa_id,activo,debe_cambiar_password) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$nombre,$user,$correo,password_hash($password,PASSWORD_DEFAULT),$rolNombre,$rolId,($rolNombre==='Vendedor'?$tiendaId:null),(in_array($rolNombre,['Supervisor','Vendedor'],true)?$empresaId:null),$activo,$debe]);
            $usuarioId = (int)$pdo->lastInsertId();
            erp_auditoria($pdo,'usuarios','crear','Creó usuario '.$user,'admin_usuarios',$usuarioId);
        }

        $pdo->prepare("DELETE FROM admin_usuario_categorias WHERE usuario_id=?")->execute([$usuarioId]);
        if($rolNombre !== 'Administrador'){
            $insCat = $pdo->prepare("INSERT IGNORE INTO admin_usuario_categorias (usuario_id,categoria_id) VALUES (?,?)");
            foreach($catsPost as $catId){ if($catId > 0) $insCat->execute([$usuarioId,$catId]); }
        }

        $pdo->commit();
        header('Location: usuarios.php?ok=1');
        exit;
    }catch(Exception $e){
        if($pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
        $usuario = array_merge($usuario, $_POST);
        $categoriasActuales = array_map('intval', $_POST['categoria_ids'] ?? []);
    }
}

admin_header($id ? 'Editar usuario' : 'Nuevo usuario', 'usuarios');
?>
<style>
.user-form-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:22px}.help{color:#64748b;font-size:13px;margin-top:6px}.role-info{background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:18px}.role-info h4{margin:0 0 8px}.role-info ul{margin-left:18px;color:#475569;line-height:1.7}.notice-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:12px;border-radius:12px;margin-bottom:15px;font-weight:bold}.scope-box{background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:16px;margin-top:8px}.scope-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.scope-item{background:white;border:1px solid #e5e7eb;border-radius:12px;padding:12px;font-weight:800;display:flex;gap:8px;align-items:center}.scope-tools{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0}.scope-tools button{border:1px solid #d8dee9;background:white;border-radius:10px;padding:8px 10px;font-weight:800;cursor:pointer}@media(max-width:900px){.user-form-grid,.scope-grid{grid-template-columns:1fr}}
</style>
<div class="user-form-grid">
    <div class="panel">
        <div class="panel-header"><h3><?= $id ? 'Editar usuario' : 'Nuevo usuario' ?></h3><a class="btn gray" href="usuarios.php">Volver</a></div>
        <?php if($error): ?><div class="notice-error"><?= h($error) ?></div><?php endif; ?>
        <form method="POST" id="formUsuario">
            <div class="form-grid">
                <div class="form-group"><label>Nombre completo</label><input name="nombre" required value="<?= h($usuario['nombre'] ?? '') ?>"></div>
                <div class="form-group"><label>Usuario</label><input name="usuario" required value="<?= h($usuario['usuario'] ?? '') ?>" placeholder="ej. vendedor01"></div>
                <div class="form-group full"><label>Correo</label><input type="email" name="correo" value="<?= h($usuario['correo'] ?? '') ?>"></div>
                <div class="form-group"><label>Rol</label><select name="rol_id" id="rol_id" required onchange="toggleScopeHelp()"><option value="">Seleccione rol</option><?php foreach($roles as $r): ?><option data-name="<?= h($r['nombre']) ?>" value="<?= (int)$r['id'] ?>" <?= (int)($usuario['rol_id'] ?? 0)===(int)$r['id'] || ($usuario['rol']??'')===$r['nombre']?'selected':'' ?>><?= h($r['nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group" id="empresaUsuarioBox"><label>Empresa asignada</label><select name="empresa_id" id="empresaUsuarioSelect" onchange="filtrarTiendasPorEmpresa()"><option value="">Sin empresa</option><?php foreach($empresas as $e): ?><option value="<?= (int)$e['id'] ?>" <?= (int)($usuario['empresa_id'] ?? 0)===(int)$e['id']?'selected':'' ?>><?= h($e['nombre']) ?></option><?php endforeach; ?></select><div class="help" id="empresaUsuarioHelp">Para <b>Vendedor</b>: elige primero la empresa, para que abajo solo aparezcan sus tiendas. Para <b>Supervisor</b>: verá todas las tiendas de esta empresa.</div></div>
                <div class="form-group" id="tiendaUsuarioBox"><label>Tienda asignada</label><select name="tienda_id" id="tiendaUsuarioSelect"><option value="">Sin tienda</option><?php foreach($tiendas as $t): ?><option value="<?= (int)$t['id'] ?>" data-empresa="<?= (int)($t['empresa_id'] ?? 0) ?>" <?= (int)($usuario['tienda_id'] ?? 0)===(int)$t['id']?'selected':'' ?>><?= h($t['nombre'].' · '.($t['categoria'] ?? 'Sin categoría')) ?></option><?php endforeach; ?></select><div class="help">Úsalo para usuarios con rol Vendedor: solo verá productos y ventas de su tienda.</div></div>
                <div class="form-group"><label>Estado</label><label style="margin-top:14px"><input type="checkbox" name="activo" <?= !empty($usuario['activo'])?'checked':'' ?>> Usuario activo</label><label style="margin-top:10px"><input type="checkbox" name="debe_cambiar_password" <?= !empty($usuario['debe_cambiar_password'])?'checked':'' ?>> Forzar cambio de contraseña</label></div>
                <div class="form-group"><label><?= $id ? 'Nueva contraseña' : 'Contraseña' ?></label><input type="password" name="password" <?= $id ? '' : 'required' ?>><div class="help"><?= $id ? 'Déjalo vacío si no deseas cambiarla.' : 'Mínimo 8 caracteres.' ?></div></div>
                <div class="form-group"><label>Confirmar contraseña</label><input type="password" name="password2" <?= $id ? '' : 'required' ?>></div>
                <div class="form-group full">
                    <label>Alcance por categoría</label>
                    <div class="help">Define qué líneas de negocio verá este usuario en productos, inventario, cotizaciones, pedidos y dashboard. El rol Administrador siempre ve todo.</div>
                    <div class="scope-box" id="scopeBox">
                        <div class="scope-tools"><button type="button" onclick="checkAllCats(true)">Seleccionar todo</button><button type="button" onclick="checkAllCats(false)">Quitar todo</button></div>
                        <div class="scope-grid">
                            <?php foreach($categorias as $c): ?>
                                <label class="scope-item"><input type="checkbox" class="cat-check" name="categoria_ids[]" value="<?= (int)$c['id'] ?>" <?= in_array((int)$c['id'],$categoriasActuales,true)?'checked':'' ?>> <?= h(($c['icono'] ?? '').' '.$c['nombre']) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="form-group full"><button class="btn green" type="submit">Guardar usuario</button></div>
            </div>
        </form>
    </div>
    <div class="role-info"><h4>Permisos + alcance</h4><ul><li>El <strong>rol</strong> define qué módulos puede usar.</li><li>El <strong>alcance por categoría</strong> define qué datos puede ver.</li><li>Si un usuario no administrador no tiene categorías, no verá datos comerciales.</li><li>El administrador global ve todas las categorías.</li><li>El rol Vendedor puede asociarse a una tienda específica como Jaimito o Pepito.</li></ul></div>
</div>
<script>
function checkAllCats(val){ document.querySelectorAll('.cat-check').forEach(c=>c.checked=val); }
function toggleScopeHelp(){
    const sel=document.getElementById('rol_id'); const opt=sel.options[sel.selectedIndex];
    const role=opt ? opt.dataset.name : '';
    const isAdmin=role==='Administrador';
    document.getElementById('scopeBox').style.opacity=(isAdmin || role==='Vendedor')?'.45':'1';
    document.getElementById('tiendaUsuarioBox').style.display=role==='Vendedor'?'flex':'none';
    document.getElementById('empresaUsuarioBox').style.display=(role==='Supervisor'||role==='Vendedor')?'flex':'none';
    document.getElementById('empresaUsuarioHelp').innerHTML = role==='Vendedor'
        ? 'Para <b>Vendedor</b>: elige primero la empresa, para que abajo solo aparezcan sus tiendas.'
        : 'Para <b>Supervisor</b>: verá todas las tiendas de esta empresa.';
    filtrarTiendasPorEmpresa();
}
function filtrarTiendasPorEmpresa(){
    const empresaId = document.getElementById('empresaUsuarioSelect').value;
    const selectTienda = document.getElementById('tiendaUsuarioSelect');
    let algunaVisible = false;
    Array.from(selectTienda.options).forEach(opt=>{
        if(opt.value===''){ opt.hidden=false; return; }
        const visible = !empresaId || opt.dataset.empresa === empresaId;
        opt.hidden = !visible;
        if(visible) algunaVisible = true;
    });
    if(selectTienda.selectedOptions[0] && selectTienda.selectedOptions[0].hidden){
        selectTienda.value = '';
    }
}
toggleScopeHelp();
</script>
<?php admin_footer(); ?>

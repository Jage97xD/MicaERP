<?php
require_once "../config/db.php";
require_once "../config/auth.php";
requerirPermiso('usuarios','editar');
$id=(int)($_GET['id'] ?? 0);
if($id>0 && $id!==adminId()){
    $st=$pdo->prepare("SELECT activo, usuario FROM admin_usuarios WHERE id=?"); $st->execute([$id]); $u=$st->fetch(PDO::FETCH_ASSOC);
    if($u){ $nuevo=$u['activo']?0:1; $pdo->prepare("UPDATE admin_usuarios SET activo=?, intentos=0, bloqueado_hasta=NULL WHERE id=?")->execute([$nuevo,$id]); erp_auditoria($pdo,'usuarios','editar',($nuevo?'Activó':'Desactivó').' usuario '.$u['usuario'],'admin_usuarios',$id); }
}
header('Location: usuarios.php?ok=1'); exit;
?>

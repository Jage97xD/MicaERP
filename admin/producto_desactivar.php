<?php
require_once "../config/db.php";
require_once "../config/auth.php";
verificarLogin();
requerirPermiso('productos','editar');
$id = (int)($_GET['id'] ?? 0);
if($id > 0){
    if(!erp_producto_en_scope($pdo,$id)){ http_response_code(403); die('Acceso restringido.'); }
    $stmt = $pdo->prepare("UPDATE productos SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
    erp_auditoria($pdo,'productos','editar','Desactivó producto','productos',$id);
}
header("Location: productos.php");
exit;
?>

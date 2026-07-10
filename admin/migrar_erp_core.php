<?php
require_once "../config/db.php";
require_once "../config/erp_core.php";
erp_ensure_core($pdo);

$hash = password_hash('Admin123*', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("SELECT id FROM admin_usuarios WHERE usuario='admin' LIMIT 1");
$stmt->execute();
$adminId = $stmt->fetchColumn();
$rolId = $pdo->query("SELECT id FROM admin_roles WHERE nombre='Administrador' LIMIT 1")->fetchColumn();
if($adminId){
    $pdo->prepare("UPDATE admin_usuarios SET nombre='Administrador', password_hash=?, rol='Administrador', rol_id=?, activo=1, intentos=0, bloqueado_hasta=NULL WHERE id=?")->execute([$hash,$rolId,$adminId]);
}else{
    $pdo->prepare("INSERT INTO admin_usuarios (nombre,usuario,correo,password_hash,rol,rol_id,activo) VALUES ('Administrador','admin','',?,'Administrador',?,1)")->execute([$hash,$rolId]);
}
erp_auditoria($pdo,'sistema','migracion','Instaló núcleo ERP, páginas institucionales, libro de reclamaciones y publicidad web');
echo "<h2>MicaStore ERP Core + Institucional instalado correctamente</h2>";
echo "<p>Usuario: <strong>admin</strong></p>";
echo "<p>Contraseña: <strong>Admin123*</strong></p>";
echo "<p><a href='login.php'>Ir al login</a></p>";
echo "<p style='color:#991b1b'><strong>Importante:</strong> elimina este archivo después de usarlo: admin/migrar_erp_core.php</p>";
?>

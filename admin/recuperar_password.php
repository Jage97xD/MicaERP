<?php
require_once "../config/db.php";
require_once "../config/erp_core.php";
erp_ensure_core($pdo);
$msg=''; $link=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $usuario=trim($_POST['usuario'] ?? '');
  $st=$pdo->prepare("SELECT id,correo,usuario FROM admin_usuarios WHERE usuario=? OR correo=? LIMIT 1"); $st->execute([$usuario,$usuario]); $u=$st->fetch(PDO::FETCH_ASSOC);
  if($u){
    $token=bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE admin_usuarios SET token_recuperacion=?, token_expira=DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id=?")->execute([$token,$u['id']]);
    erp_registrar_acceso($pdo,(int)$u['id'],$u['usuario'],false,'Solicitud de recuperación de contraseña');
    $link='restablecer_password.php?token='.$token;
    $msg='Se generó un enlace temporal por 30 minutos. En producción esto se enviará por correo.';
  }else{ $error='No se encontró el usuario o correo.'; }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Recuperar contraseña</title><style>body{font-family:Arial;background:#eef4fb;min-height:100vh;display:flex;align-items:center;justify-content:center}.card{background:white;border-radius:22px;padding:30px;width:430px;box-shadow:0 12px 35px rgba(15,23,42,.14)}input,button{width:100%;padding:14px;border-radius:12px;border:1px solid #d8dee9;margin-top:10px}button{background:#0057d9;color:white;font-weight:bold;border:0}.ok{background:#dcfce7;color:#166534;padding:12px;border-radius:12px;margin:12px 0}.err{background:#fee2e2;color:#991b1b;padding:12px;border-radius:12px;margin:12px 0}a{color:#0057d9;font-weight:bold}</style></head><body><div class="card"><h1>Recuperar contraseña</h1><p>Ingresa tu usuario o correo.</p><?php if($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?><?php if($msg): ?><div class="ok"><?= htmlspecialchars($msg) ?><br><br><a href="<?= htmlspecialchars($link) ?>">Abrir enlace de recuperación</a></div><?php endif; ?><form method="POST"><input name="usuario" required placeholder="Usuario o correo"><button>Generar enlace</button></form><p><a href="login.php">Volver al login</a></p></div></body></html>

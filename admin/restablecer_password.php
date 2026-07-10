<?php
require_once "../config/db.php";
require_once "../config/erp_core.php";
erp_ensure_core($pdo);
$token=trim($_GET['token'] ?? $_POST['token'] ?? ''); $error=''; $ok='';
$st=$pdo->prepare("SELECT * FROM admin_usuarios WHERE token_recuperacion=? AND token_expira>NOW() LIMIT 1"); $st->execute([$token]); $u=$st->fetch(PDO::FETCH_ASSOC);
if(!$u){ $error='El enlace no existe o ya expiró.'; }
elseif($_SERVER['REQUEST_METHOD']==='POST'){
  $p=$_POST['password'] ?? ''; $p2=$_POST['password2'] ?? '';
  if(strlen($p)<8) $error='La contraseña debe tener mínimo 8 caracteres.';
  elseif($p!==$p2) $error='Las contraseñas no coinciden.';
  else{ $pdo->prepare("UPDATE admin_usuarios SET password_hash=?, token_recuperacion=NULL, token_expira=NULL, intentos=0, bloqueado_hasta=NULL WHERE id=?")->execute([password_hash($p,PASSWORD_DEFAULT),$u['id']]); erp_registrar_acceso($pdo,(int)$u['id'],$u['usuario'],true,'Contraseña restablecida'); $ok='Contraseña actualizada. Ya puedes iniciar sesión.'; }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Restablecer contraseña</title><style>body{font-family:Arial;background:#eef4fb;min-height:100vh;display:flex;align-items:center;justify-content:center}.card{background:white;border-radius:22px;padding:30px;width:430px;box-shadow:0 12px 35px rgba(15,23,42,.14)}input,button{width:100%;padding:14px;border-radius:12px;border:1px solid #d8dee9;margin-top:10px}button{background:#0057d9;color:white;font-weight:bold;border:0}.ok{background:#dcfce7;color:#166534;padding:12px;border-radius:12px;margin:12px 0}.err{background:#fee2e2;color:#991b1b;padding:12px;border-radius:12px;margin:12px 0}a{color:#0057d9;font-weight:bold}</style></head><body><div class="card"><h1>Nueva contraseña</h1><?php if($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?><?php if($ok): ?><div class="ok"><?= htmlspecialchars($ok) ?><br><br><a href="login.php">Ir al login</a></div><?php elseif($u): ?><form method="POST"><input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"><input type="password" name="password" required placeholder="Nueva contraseña"><input type="password" name="password2" required placeholder="Confirmar contraseña"><button>Guardar contraseña</button></form><?php endif; ?></div></body></html>

<?php
require_once "../config/db.php";
require_once "../config/erp_core.php";
require_once "../config/configuracion.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
erp_ensure_core($pdo);
$error = "";

$totalAdmins = $pdo->query("SELECT COUNT(*) FROM admin_usuarios")->fetchColumn();
if ($totalAdmins == 0) {
    $hash = password_hash("Admin123*", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admin_usuarios (nombre, usuario, correo, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute(["Administrador", "admin", "", $hash, "Administrador"]);
    $pdo->exec("UPDATE admin_usuarios u INNER JOIN admin_roles r ON r.nombre=u.rol SET u.rol_id=r.id WHERE u.usuario='admin'");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare("SELECT u.*, r.nombre AS rol_nombre FROM admin_usuarios u LEFT JOIN admin_roles r ON r.id=u.rol_id WHERE u.usuario = ? LIMIT 1");
    $stmt->execute([$usuario]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$admin){ erp_registrar_acceso($pdo,null,$usuario,false,'Usuario no existe'); $error='Usuario o contraseña incorrectos.'; }
    elseif((int)$admin['activo'] !== 1){ erp_registrar_acceso($pdo,(int)$admin['id'],$usuario,false,'Usuario inactivo'); $error='Usuario inactivo. Contacta al administrador.'; }
    elseif(!empty($admin['bloqueado_hasta']) && strtotime($admin['bloqueado_hasta']) > time()){
        erp_registrar_acceso($pdo,(int)$admin['id'],$usuario,false,'Usuario bloqueado temporalmente');
        $error='Usuario bloqueado temporalmente. Intenta nuevamente más tarde.';
    }
    elseif(password_verify($password, $admin["password_hash"])) {
        $_SESSION["admin_id"] = (int)$admin["id"];
        $_SESSION["admin_nombre"] = $admin["nombre"];
        $_SESSION["admin_usuario"] = $admin["usuario"];
        $_SESSION["admin_rol"] = $admin["rol_nombre"] ?: ($admin["rol"] ?: 'Administrador');
        $_SESSION["admin_rol_id"] = (int)($admin["rol_id"] ?? 0);
        $_SESSION["admin_tienda_id"] = (int)($admin["tienda_id"] ?? 0);
        $_SESSION["admin_empresa_id"] = (int)($admin["empresa_id"] ?? 0);
        $pdo->prepare("UPDATE admin_usuarios SET ultimo_login=NOW(), ultimo_ip=?, intentos=0, bloqueado_hasta=NULL WHERE id=?")->execute([erp_client_ip(),$admin['id']]);
        erp_registrar_acceso($pdo,(int)$admin['id'],$usuario,true,'Login correcto');
        erp_auditoria($pdo,'accesos','login','Inicio de sesión correcto','admin_usuarios',$admin['id']);
        header("Location: dashboard.php"); exit;
    } else {
        $intentos=(int)($admin['intentos'] ?? 0)+1;
        $bloqueo = $intentos >= 5 ? date('Y-m-d H:i:s', time()+15*60) : null;
        $pdo->prepare("UPDATE admin_usuarios SET intentos=?, bloqueado_hasta=? WHERE id=?")->execute([$intentos,$bloqueo,$admin['id']]);
        erp_registrar_acceso($pdo,(int)$admin['id'],$usuario,false,'Contraseña incorrecta');
        $error = $bloqueo ? 'Demasiados intentos. Usuario bloqueado por 15 minutos.' : 'Usuario o contraseña incorrectos.';
    }
}

$brandNombre = trim(configValor($pdo, 'nombre_comercial', 'Mica Store')) ?: 'Mica Store';
$brandLogo = trim(configValor($pdo, 'logo', ''));
$brandLogoUrl = $brandLogo !== '' ? '/micastore/' . ltrim($brandLogo, '/') : '';
$brandInicial = mb_strtoupper(mb_substr($brandNombre, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Login Admin - <?= htmlspecialchars($brandNombre) ?></title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#07162f,#0057d9);display:flex;align-items:center;justify-content:center;color:#111827}.login-card{width:100%;max-width:430px;background:white;border-radius:24px;padding:34px;box-shadow:0 18px 45px rgba(0,0,0,.28)}.brand{text-align:center;margin-bottom:25px}.logo{width:70px;height:70px;border-radius:18px;margin:0 auto 14px;background:linear-gradient(135deg,#0057d9,#06b6d4);color:white;display:flex;align-items:center;justify-content:center;font-size:38px;font-weight:900;overflow:hidden}.logo img{width:100%;height:100%;object-fit:contain;background:white;padding:6px}h1{margin:0;font-size:28px}p{color:#6b7280}label{display:block;font-weight:bold;margin:14px 0 7px}input{width:100%;padding:14px;border:1px solid #d8dee9;border-radius:12px;font-size:16px}button{width:100%;margin-top:22px;padding:14px;border:0;border-radius:12px;background:#0057d9;color:white;font-size:16px;font-weight:bold;cursor:pointer}.error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:12px;border-radius:10px;margin-bottom:14px;font-weight:bold}.hint{margin-top:18px;background:#f8fafc;border:1px solid #e5e7eb;padding:12px;border-radius:12px;font-size:13px;color:#475569}.hint a{color:#0057d9;font-weight:bold;text-decoration:none}
</style></head><body><div class="login-card"><div class="brand"><div class="logo"><?php if($brandLogoUrl): ?><img src="<?= htmlspecialchars($brandLogoUrl) ?>" alt="<?= htmlspecialchars($brandNombre) ?>"><?php else: ?><?= htmlspecialchars($brandInicial) ?><?php endif; ?></div><h1><?= htmlspecialchars($brandNombre) ?> Admin</h1><p>Ingresa para administrar la tienda</p></div><?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?><form method="POST"><label>Usuario</label><input name="usuario" required autofocus><label>Contraseña</label><input type="password" name="password" required><button type="submit">Iniciar sesión</button></form><div class="hint">Usuario inicial: <strong>admin</strong><br>Contraseña inicial: <strong>Admin123*</strong><br><a href="recuperar_password.php">¿Olvidaste tu contraseña?</a></div></div></body></html>

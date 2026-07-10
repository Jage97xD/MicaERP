<?php
require_once "../config/db.php";
require_once "cliente_common.php";

$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM clientes_web WHERE correo=? AND activo=1 LIMIT 1");
    $stmt->execute([$correo]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if($cliente && password_verify($password, $cliente['password_hash'])){
        $_SESSION['cliente_web_id'] = (int)$cliente['id'];
        $_SESSION['cliente_web_nombre'] = $cliente['nombre'];
        $_SESSION['cliente_web_correo'] = $cliente['correo'];

        header("Location: mi_cuenta.php");
        exit;
    }else{
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login Cliente - Mica Store</title>
<link rel="stylesheet" href="cliente_style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <a class="brand" href="../tienda_visual_v3.php"><span>M</span> Mica Store</a>
        <h1>Iniciar sesión</h1>
        <p>Ingresa para revisar tus cotizaciones y favoritos.</p>

        <?php if($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

        <form method="POST">
            <label>Correo</label>
            <input type="email" name="correo" required>

            <label>Contraseña</label>
            <input type="password" name="password" required>

            <button>Ingresar</button>
        </form>

        <p class="center">¿No tienes cuenta? <a href="cliente_registro.php">Crear cuenta</a></p>
    </div>
</div>
</body>
</html>

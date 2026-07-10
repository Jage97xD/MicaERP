<?php
require_once "../config/db.php";
require_once "cliente_common.php";

$error = "";

try{
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS clientes_web (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(150) NOT NULL,
      documento VARCHAR(30) NULL,
      celular VARCHAR(30) NULL,
      correo VARCHAR(120) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      direccion VARCHAR(180) NULL,
      distrito VARCHAR(100) NULL,
      provincia VARCHAR(100) NULL,
      activo TINYINT DEFAULT 1,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
}catch(Exception $e){}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $celular = trim($_POST['celular'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $aceptaOfertas = isset($_POST['acepta_ofertas']) ? 1 : 0;
    $aceptaContacto = isset($_POST['acepta_contacto']) ? 1 : 0;

    if($nombre === '' || $correo === '' || $password === ''){
        $error = "Nombre, correo y contraseña son obligatorios.";
    }elseif(strlen($password) < 6){
        $error = "La contraseña debe tener mínimo 6 caracteres.";
    }else{
        try{
            $stmt = $pdo->prepare("SELECT id FROM clientes_web WHERE correo=? LIMIT 1");
            $stmt->execute([$correo]);

            if($stmt->fetchColumn()){
                $error = "Ese correo ya está registrado. Inicia sesión.";
            }else{
                $cols = [];
                try{ foreach($pdo->query("DESCRIBE clientes_web")->fetchAll(PDO::FETCH_ASSOC) as $r){ $cols[$r['Field']] = true; } }catch(Exception $e){}
                $datos = [
                    'nombre'=>$nombre,
                    'correo'=>$correo,
                    'password_hash'=>password_hash($password, PASSWORD_DEFAULT),
                    'celular'=>$celular,
                    'documento'=>$documento,
                    'acepta_ofertas'=>$aceptaOfertas,
                    'acepta_contacto'=>$aceptaContacto,
                    'fecha_consentimiento'=>($aceptaOfertas || $aceptaContacto) ? date('Y-m-d H:i:s') : null,
                    'activo'=>1
                ];
                $insert=[];
                foreach($datos as $k=>$v){ if(isset($cols[$k])) $insert[$k]=$v; }
                $campos=array_keys($insert);
                $sql="INSERT INTO clientes_web (".implode(',', $campos).") VALUES (".implode(',', array_fill(0,count($campos),'?')).")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($insert));

                $_SESSION['cliente_web_id'] = (int)$pdo->lastInsertId();
                $_SESSION['cliente_web_nombre'] = $nombre;
                $_SESSION['cliente_web_correo'] = $correo;

                header("Location: mi_cuenta.php");
                exit;
            }
        }catch(Exception $e){
            $error = "Error real: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro - Mica Store</title>
<link rel="stylesheet" href="cliente_style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <a class="brand" href="../tienda_visual_v3.php"><span>M</span> Mica Store</a>
        <h1>Crear cuenta</h1>
        <p>Regístrate para ver tus cotizaciones, favoritos e historial.</p>

        <?php if($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>

        <form method="POST">
            <label>Nombre completo</label>
            <input name="nombre" required>

            <label>Correo</label>
            <input type="email" name="correo" required>

            <label>Contraseña</label>
            <input type="password" name="password" required>

            <label>Celular</label>
            <input name="celular">

            <label>DNI/RUC</label>
            <input name="documento">

            <label style="display:flex;gap:10px;align-items:flex-start;font-weight:normal;line-height:1.4;margin-top:16px;">
                <input type="checkbox" name="acepta_ofertas" value="1" style="width:auto;margin-top:3px;">
                <span>Deseo recibir ofertas, promociones y novedades de Mica Store.</span>
            </label>

            <label style="display:flex;gap:10px;align-items:flex-start;font-weight:normal;line-height:1.4;margin-top:10px;">
                <input type="checkbox" name="acepta_contacto" value="1" style="width:auto;margin-top:3px;">
                <span>Acepto que Mica Store me contacte para ofrecerme productos o servicios relacionados.</span>
            </label>

            <button>Crear cuenta</button>
        </form>

        <p class="center">¿Ya tienes cuenta? <a href="cliente_login.php">Inicia sesión</a></p>
    </div>
</div>
</body>
</html>

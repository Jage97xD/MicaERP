<?php
require_once "../config/db.php";
require_once "cliente_common.php";
header("Content-Type: application/json; charset=utf-8");

$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if($correo === '' || $password === ''){
    echo json_encode(["ok"=>false,"mensaje"=>"Ingresa correo y contraseña."]);
    exit;
}

try{
    $stmt = $pdo->prepare("SELECT * FROM clientes_web WHERE correo=? AND activo=1 LIMIT 1");
    $stmt->execute([$correo]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$cliente || !password_verify($password, $cliente['password_hash'])){
        echo json_encode(["ok"=>false,"mensaje"=>"Correo o contraseña incorrectos."]);
        exit;
    }

    $_SESSION['cliente_web_id'] = (int)$cliente['id'];
    $_SESSION['cliente_web_nombre'] = $cliente['nombre'];
    $_SESSION['cliente_web_correo'] = $cliente['correo'];

    echo json_encode([
        "ok"=>true,
        "mensaje"=>"Sesión iniciada correctamente.",
        "cliente"=>[
            "id"=>(int)$cliente['id'],
            "nombre"=>$cliente['nombre'],
            "correo"=>$cliente['correo']
        ]
    ]);
}catch(Exception $e){
    echo json_encode(["ok"=>false,"mensaje"=>$e->getMessage()]);
}
?>
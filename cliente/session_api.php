<?php
require_once "../config/db.php";
require_once "cliente_common.php";

header("Content-Type: application/json; charset=utf-8");

if(clienteLogueado()){
    echo json_encode([
        "ok" => true,
        "logueado" => true,
        "cart_key" => "mica_cart_cliente_" . clienteId(),
        "cliente" => [
            "id" => clienteId(),
            "nombre" => clienteNombre(),
            "correo" => $_SESSION['cliente_web_correo'] ?? ''
        ]
    ], JSON_UNESCAPED_UNICODE);
}else{
    echo json_encode([
        "ok" => true,
        "logueado" => false,
        "cart_key" => "mica_cart_invitado"
    ], JSON_UNESCAPED_UNICODE);
}
?>
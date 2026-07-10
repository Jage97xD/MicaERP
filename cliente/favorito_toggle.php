<?php
require_once "../config/db.php";
require_once "cliente_common.php";
header("Content-Type: application/json; charset=utf-8");

if(!clienteLogueado()){
    echo json_encode(["ok"=>false, "login"=>false, "mensaje"=>"Debes iniciar sesión."]);
    exit;
}

$productoId = (int)($_POST['producto_id'] ?? $_GET['producto_id'] ?? 0);
$clienteId = clienteId();

if($productoId <= 0){
    echo json_encode(["ok"=>false, "mensaje"=>"Producto inválido."]);
    exit;
}

try{
    $stmt = $pdo->prepare("SELECT id FROM cliente_favoritos WHERE cliente_id=? AND producto_id=? LIMIT 1");
    $stmt->execute([$clienteId, $productoId]);
    $existe = $stmt->fetchColumn();

    if($existe){
        $stmt = $pdo->prepare("DELETE FROM cliente_favoritos WHERE cliente_id=? AND producto_id=?");
        $stmt->execute([$clienteId, $productoId]);
        echo json_encode(["ok"=>true, "favorito"=>false, "mensaje"=>"Producto quitado de favoritos."]);
    }else{
        $stmt = $pdo->prepare("INSERT INTO cliente_favoritos (cliente_id, producto_id) VALUES (?, ?)");
        $stmt->execute([$clienteId, $productoId]);
        echo json_encode(["ok"=>true, "favorito"=>true, "mensaje"=>"Producto agregado a favoritos."]);
    }
}catch(Exception $e){
    echo json_encode(["ok"=>false, "mensaje"=>$e->getMessage()]);
}

<?php
require_once "../config/db.php";
require_once "cliente_common.php";
header("Content-Type: application/json; charset=utf-8");

if(!clienteLogueado()){
    echo json_encode(["ok"=>true, "total"=>0]);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM cliente_favoritos WHERE cliente_id=?");
$stmt->execute([clienteId()]);
echo json_encode(["ok"=>true, "total"=>(int)$stmt->fetchColumn()]);

<?php
require_once "config/db.php";
header("Content-Type: application/json; charset=utf-8");

$ids = $_POST['ids'] ?? $_GET['ids'] ?? '';
if(is_array($ids)){
    $ids = implode(",", $ids);
}

$ids = array_filter(array_map('intval', explode(",", $ids)));

if(count($ids) === 0){
    echo json_encode(["ok"=>true, "productos"=>[]]);
    exit;
}

$placeholders = implode(",", array_fill(0, count($ids), "?"));

$stmt = $pdo->prepare("
SELECT
    p.id,
    p.nombre,
    p.precio,
    p.precio_oferta,
    p.stock,
    p.imagen_principal,
    c.nombre AS categoria,
    m.nombre AS marca
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN marcas m ON p.marca_id = m.id
WHERE p.activo = 1
AND p.id IN ($placeholders)
");
$stmt->execute($ids);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["ok"=>true, "productos"=>$productos], JSON_UNESCAPED_UNICODE);

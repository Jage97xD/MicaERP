<?php
require_once "../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$id = (int)($_GET["id"] ?? 0);

if($id <= 0){
    echo json_encode([
        "ok" => false,
        "mensaje" => "Categoría no válida."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$categoria){
    echo json_encode([
        "ok" => false,
        "mensaje" => "Categoría no encontrada."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$tipo = $categoria["tipo_categoria"] ?? "normal";

echo json_encode([
    "ok" => true,
    "categoria" => [
        "id" => (int)$categoria["id"],
        "nombre" => $categoria["nombre"] ?? "",
        "slug" => $categoria["slug"] ?? "",
        "tipo_categoria" => $tipo,
        "usa_marca" => (int)($categoria["usa_marca"] ?? 1),
        "usa_sku" => (int)($categoria["usa_sku"] ?? 1),
        "usa_codigo" => (int)($categoria["usa_codigo"] ?? 1),
        "usa_peso" => (int)($categoria["usa_peso"] ?? 0),
        "usa_vencimiento" => (int)($categoria["usa_vencimiento"] ?? 0),
        "usa_servicio" => $tipo === "servicio" ? 1 : 0,
        "usa_stock" => $tipo === "servicio" ? 0 : 1
    ]
], JSON_UNESCAPED_UNICODE);
exit;
?>
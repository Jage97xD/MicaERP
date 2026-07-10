<?php
require_once "../config/db.php";

$id = $_GET['id'] ?? 0;
$producto_id = $_GET['producto_id'] ?? 0;

if($id && $producto_id){
    $pdo->prepare("UPDATE imagenes_producto SET principal = 0 WHERE producto_id = ?")->execute([$producto_id]);
    $pdo->prepare("UPDATE imagenes_producto SET principal = 1 WHERE id = ? AND producto_id = ?")->execute([$id, $producto_id]);

    $stmt = $pdo->prepare("SELECT imagen FROM imagenes_producto WHERE id = ? AND producto_id = ?");
    $stmt->execute([$id, $producto_id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);

    if($img){
        $pdo->prepare("UPDATE productos SET imagen_principal = ? WHERE id = ?")->execute([$img['imagen'], $producto_id]);
    }
}

header("Location: producto_editar.php?id=" . $producto_id);
exit;
?>
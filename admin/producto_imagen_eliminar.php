<?php
require_once "../config/db.php";

$id = $_GET['id'] ?? 0;
$producto_id = $_GET['producto_id'] ?? 0;

if($id && $producto_id){
    $stmt = $pdo->prepare("SELECT imagen, principal FROM imagenes_producto WHERE id = ? AND producto_id = ?");
    $stmt->execute([$id, $producto_id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);

    if($img){
        $pdo->prepare("DELETE FROM imagenes_producto WHERE id = ? AND producto_id = ?")->execute([$id, $producto_id]);

        $ruta = "../" . $img['imagen'];
        if(file_exists($ruta)){
            @unlink($ruta);
        }

        if($img['principal']){
            $stmt = $pdo->prepare("SELECT * FROM imagenes_producto WHERE producto_id = ? ORDER BY orden ASC, id ASC LIMIT 1");
            $stmt->execute([$producto_id]);
            $nueva = $stmt->fetch(PDO::FETCH_ASSOC);

            if($nueva){
                $pdo->prepare("UPDATE imagenes_producto SET principal = 1 WHERE id = ?")->execute([$nueva['id']]);
                $pdo->prepare("UPDATE productos SET imagen_principal = ? WHERE id = ?")->execute([$nueva['imagen'], $producto_id]);
            }else{
                $pdo->prepare("UPDATE productos SET imagen_principal = '' WHERE id = ?")->execute([$producto_id]);
            }
        }
    }
}

header("Location: producto_editar.php?id=" . $producto_id);
exit;
?>
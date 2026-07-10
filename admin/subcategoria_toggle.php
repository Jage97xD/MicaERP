<?php
require_once "../config/db.php";

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT activo FROM subcategorias WHERE id = ?");
$stmt->execute([$id]);
$subcategoria = $stmt->fetch(PDO::FETCH_ASSOC);

if($subcategoria){
    $nuevo = $subcategoria['activo'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE subcategorias SET activo = ? WHERE id = ?");
    $stmt->execute([$nuevo, $id]);
}

header("Location: subcategorias.php");
exit;
?>
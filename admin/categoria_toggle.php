<?php
require_once "../config/db.php";

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT activo FROM categorias WHERE id = ?");
$stmt->execute([$id]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if($categoria){
    $nuevo = $categoria['activo'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE categorias SET activo = ? WHERE id = ?");
    $stmt->execute([$nuevo, $id]);
}

header("Location: categorias.php");
exit;
?>
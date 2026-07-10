<?php
require_once "../config/db.php";

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT activo FROM marcas WHERE id = ?");
$stmt->execute([$id]);
$marca = $stmt->fetch(PDO::FETCH_ASSOC);

if($marca){
    $nuevo = $marca['activo'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE marcas SET activo = ? WHERE id = ?");
    $stmt->execute([$nuevo, $id]);
}

header("Location: marcas.php");
exit;
?>
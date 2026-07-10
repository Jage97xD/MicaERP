<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../config/db.php";

$ids = $_GET['ids'] ?? '';
if ($ids === '') { echo json_encode([]); exit; }

$idsArray = array_filter(array_map('intval', explode(',', $ids)));
if (count($idsArray) === 0) { echo json_encode([]); exit; }

$placeholders = implode(',', array_fill(0, count($idsArray), '?'));

$sql = "SELECT p.id,p.nombre,p.precio,p.precio_oferta,p.stock,p.imagen_principal,
c.nombre AS categoria,s.nombre AS subcategoria,m.nombre AS marca
FROM productos p
LEFT JOIN categorias c ON p.categoria_id=c.id
LEFT JOIN subcategorias s ON p.subcategoria_id=s.id
LEFT JOIN marcas m ON p.marca_id=m.id
WHERE p.activo=1 AND p.id IN ($placeholders)";

$stmt = $pdo->prepare($sql);
$stmt->execute($idsArray);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
?>
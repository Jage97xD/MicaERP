<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../config/db.php";
$id=$_GET['id']??0;
$stmt=$pdo->prepare("SELECT p.*,c.nombre categoria,c.slug categoria_slug,s.nombre subcategoria,m.nombre marca FROM productos p LEFT JOIN categorias c ON p.categoria_id=c.id LEFT JOIN subcategorias s ON p.subcategoria_id=s.id LEFT JOIN marcas m ON p.marca_id=m.id WHERE p.id=? AND p.activo=1 LIMIT 1");
$stmt->execute([$id]); $p=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$p){ http_response_code(404); echo json_encode(['error'=>'Producto no encontrado']); exit; }
echo json_encode($p,JSON_UNESCAPED_UNICODE);
?>
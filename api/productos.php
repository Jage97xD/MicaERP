<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../config/db.php";
$categoria=$_GET['categoria']??''; $buscar=$_GET['buscar']??''; $limite=isset($_GET['limite'])?(int)$_GET['limite']:0;
$sql="SELECT p.id,p.nombre,p.slug,p.descripcion_corta,p.precio,p.precio_oferta,p.stock,p.imagen_principal,p.nuevo,p.oferta,c.nombre categoria,c.slug categoria_slug,s.nombre subcategoria,m.nombre marca
FROM productos p
LEFT JOIN categorias c ON p.categoria_id=c.id
LEFT JOIN subcategorias s ON p.subcategoria_id=s.id
LEFT JOIN marcas m ON p.marca_id=m.id
WHERE p.activo=1";
$params=[];
if($categoria!=''){ $sql.=" AND c.slug=:categoria"; $params[':categoria']=$categoria; }
if($buscar!=''){ $sql.=" AND (p.nombre LIKE :buscar OR p.descripcion_corta LIKE :buscar OR m.nombre LIKE :buscar)"; $params[':buscar']="%$buscar%"; }
$sql.=" ORDER BY p.id DESC";
if($limite>0){ $sql.=" LIMIT ".$limite; }
$stmt=$pdo->prepare($sql); $stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC),JSON_UNESCAPED_UNICODE);
?>
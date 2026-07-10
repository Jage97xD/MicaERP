<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../config/db.php";
$cats=$pdo->query("SELECT id,nombre,slug,color,icono FROM categorias WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
foreach($cats as &$c){
  $st=$pdo->prepare("SELECT id,nombre,slug FROM subcategorias WHERE categoria_id=? AND activo=1 ORDER BY nombre");
  $st->execute([$c['id']]); $c['subcategorias']=$st->fetchAll(PDO::FETCH_ASSOC);
}
echo json_encode($cats,JSON_UNESCAPED_UNICODE);
?>
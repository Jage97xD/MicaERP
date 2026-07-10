<?php
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
header("Location: producto_editar.php?id=".$id."#tab-descripcion");
exit;
?>
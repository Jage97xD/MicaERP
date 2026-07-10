<?php
require_once "cliente_common.php";
unset($_SESSION['cliente_web_id'], $_SESSION['cliente_web_nombre'], $_SESSION['cliente_web_correo']);
$volver = $_SERVER['HTTP_REFERER'] ?? '../tienda_visual_v3.php';
if(strpos($volver, 'cliente_logout.php') !== false){
    $volver = '../tienda_visual_v3.php';
}
header("Location: ".$volver);
exit;
?>
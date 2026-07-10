<?php
require_once "cliente_common.php";

header("Content-Type: application/json; charset=utf-8");

session_unset();
session_destroy();

echo json_encode([
    "ok" => true,
    "mensaje" => "Sesión cerrada.",
    "clear_cart" => true,
    "cart_key" => "mica_cart_invitado"
], JSON_UNESCAPED_UNICODE);

exit;
?>
<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

function clienteLogueado(){
    return isset($_SESSION['cliente_web_id']) && (int)$_SESSION['cliente_web_id'] > 0;
}

function clienteId(){
    return (int)($_SESSION['cliente_web_id'] ?? 0);
}

function clienteNombre(){
    return $_SESSION['cliente_web_nombre'] ?? '';
}

function requerirCliente(){
    if(!clienteLogueado()){
        header("Location: cliente_login.php");
        exit;
    }
}
?>
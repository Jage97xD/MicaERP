<?php
require_once "../config/db.php";
require_once "../config/erp_core.php";
if(session_status()===PHP_SESSION_NONE){ session_start(); }
erp_ensure_core($pdo);
erp_auditoria($pdo,'accesos','logout','Cierre de sesión');
session_destroy();
header("Location: login.php");
exit;
?>

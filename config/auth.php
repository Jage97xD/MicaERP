<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if(!isset($pdo)) { require_once __DIR__ . '/db.php'; }
require_once __DIR__ . '/erp_core.php';
erp_ensure_core($pdo);

function verificarLogin(){
    if (empty($_SESSION['admin_id'])) { header("Location: /micastore/admin/login.php"); exit; }
}
function adminNombre(){ return $_SESSION['admin_nombre'] ?? 'Admin'; }
function adminRol(){ return $_SESSION['admin_rol'] ?? 'Administrador'; }
function adminId(){ return (int)($_SESSION['admin_id'] ?? 0); }
function esAdministrador(){ return adminRol() === 'Administrador'; }
function requerirAdministrador(){ verificarLogin(); global $pdo; erp_requerir_permiso($pdo,'usuarios','ver'); }
function rolPuede($modulo, $accion='ver'){ global $pdo; return erp_tiene_permiso($pdo,$modulo,$accion); }
function requerirPermiso($modulo, $accion='ver'){ verificarLogin(); global $pdo; erp_requerir_permiso($pdo,$modulo,$accion); }
function categoriasPermitidas(){ global $pdo; return erp_categorias_permitidas($pdo); }
function alcanceResumen(){ global $pdo; return function_exists('erp_scope_resumen_comercial') ? erp_scope_resumen_comercial($pdo) : erp_scope_resumen($pdo); }
function tiendaAsignadaId(){ return (int)($_SESSION['admin_tienda_id'] ?? 0); }
function productoEnAlcance($productoId){ global $pdo; return erp_producto_en_scope($pdo,(int)$productoId); }
function cotizacionEnAlcance($cotizacionId){ global $pdo; return erp_cotizacion_en_scope($pdo,(int)$cotizacionId); }
?>

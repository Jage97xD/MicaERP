<?php
// MicaERP Marketplace - Contexto de Empresa (reutilizable)
// Se incluye al inicio de cada página pública para saber "en qué empresa estoy"
// según el parámetro ?empresa=slug, y mantenerlo disponible para header/footer/menu.
if(!isset($empresaSlug)){
    $empresaSlug = trim($_GET['empresa'] ?? '');
    $empresaActual = $empresaSlug !== '' ? erp_empresa_por_slug($pdo, $empresaSlug) : null;
    $empresaId = $empresaActual['id'] ?? 0;
    $GLOBALS['empresaSlugActual'] = $empresaActual ? $empresaSlug : null;
    $GLOBALS['empresaId'] = $empresaId;
}

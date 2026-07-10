<?php
require_once "config/db.php";

$hash = password_hash("admin123", PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    UPDATE admin_usuarios
    SET password_hash = ?, activo = 1
    WHERE usuario = 'admin'
");

$stmt->execute([$hash]);

echo "Admin reseteado. Usuario: admin | Clave: admin123";
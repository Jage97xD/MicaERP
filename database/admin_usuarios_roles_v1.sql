-- MicaStore - Usuarios y roles del panel admin
-- Ejecutar una vez en la base de datos mica_store.

CREATE TABLE IF NOT EXISTS admin_usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  usuario VARCHAR(80) NOT NULL UNIQUE,
  correo VARCHAR(150) NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol VARCHAR(50) DEFAULT 'Administrador',
  activo TINYINT DEFAULT 1,
  ultimo_login DATETIME NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE admin_usuarios ADD COLUMN IF NOT EXISTS correo VARCHAR(150) NULL AFTER usuario;
ALTER TABLE admin_usuarios ADD COLUMN IF NOT EXISTS ultimo_login DATETIME NULL AFTER activo;
ALTER TABLE admin_usuarios ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Usuario administrador inicial/reset de desarrollo.
-- Usuario: admin
-- Contraseña: Admin123*
INSERT INTO admin_usuarios (nombre, usuario, correo, password_hash, rol, activo)
VALUES ('Administrador', 'admin', 'admin@micastore.local', '$2y$12$I3IqSDkTuU0AF2Qqzp0CFuBvsReAe/lazJl9AVP4Xdi/NAR5tdbyO', 'Administrador', 1)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  correo = VALUES(correo),
  password_hash = VALUES(password_hash),
  rol = 'Administrador',
  activo = 1;

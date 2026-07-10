-- Mica Store Clientes Web Fix v2
-- Ejecutar dentro de la base mica_store.
-- Crea la tabla que falta para el login web de clientes.

CREATE TABLE IF NOT EXISTS clientes_web (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  documento VARCHAR(30) NULL,
  celular VARCHAR(30) NULL,
  correo VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  direccion VARCHAR(180) NULL,
  distrito VARCHAR(100) NULL,
  provincia VARCHAR(100) NULL,
  activo TINYINT DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cliente_favoritos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  producto_id INT NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cliente_producto (cliente_id, producto_id),
  INDEX(cliente_id),
  INDEX(producto_id)
);

SET @db = DATABASE();

SET @existe := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=@db
    AND TABLE_NAME='cotizaciones'
    AND COLUMN_NAME='cliente_web_id'
);

SET @sql := IF(
    @existe=0,
    'ALTER TABLE cotizaciones ADD COLUMN cliente_web_id INT NULL AFTER cliente_id',
    'SELECT "cliente_web_id ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

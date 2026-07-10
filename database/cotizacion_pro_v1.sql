-- Mica Store Cotización PRO v1
-- Ejecutar dentro de mica_store.
-- No elimina datos.

SET @db = DATABASE();

-- Asegurar tabla clientes
CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  documento VARCHAR(30) NOT NULL UNIQUE,
  celular VARCHAR(30),
  correo VARCHAR(120),
  direccion VARCHAR(180),
  distrito VARCHAR(100),
  provincia VARCHAR(100),
  activo TINYINT DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Asegurar tablas de cotización si no existen
CREATE TABLE IF NOT EXISTS cotizaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NULL,
  nombre_completo VARCHAR(150),
  documento VARCHAR(30),
  celular VARCHAR(30),
  correo VARCHAR(120),
  tipo_entrega VARCHAR(80),
  direccion VARCHAR(180),
  distrito VARCHAR(100),
  provincia VARCHAR(100),
  observaciones TEXT,
  total DECIMAL(10,2) DEFAULT 0,
  estado VARCHAR(40) DEFAULT 'Pendiente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cotizacion_detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cotizacion_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(cotizacion_id),
  INDEX(producto_id)
);

-- Agregar columnas a cotizaciones si faltan
SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='numero');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN numero VARCHAR(30) NULL AFTER id', 'SELECT "numero ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='total');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN total DECIMAL(10,2) DEFAULT 0', 'SELECT "total ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='estado');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN estado VARCHAR(40) DEFAULT "Pendiente"', 'SELECT "estado ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='observaciones');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN observaciones TEXT NULL', 'SELECT "observaciones ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='correo');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN correo VARCHAR(120) NULL', 'SELECT "correo ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='direccion');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN direccion VARCHAR(180) NULL', 'SELECT "direccion ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='distrito');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN distrito VARCHAR(100) NULL', 'SELECT "distrito ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='provincia');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN provincia VARCHAR(100) NULL', 'SELECT "provincia ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Agregar columnas a detalle si faltan
SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizacion_detalle' AND COLUMN_NAME='precio');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizacion_detalle ADD COLUMN precio DECIMAL(10,2) NOT NULL DEFAULT 0', 'SELECT "precio ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizacion_detalle' AND COLUMN_NAME='subtotal');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizacion_detalle ADD COLUMN subtotal DECIMAL(10,2) NOT NULL DEFAULT 0', 'SELECT "subtotal ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

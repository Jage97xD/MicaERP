-- Mica Store Nuevo Producto PRO v1
-- Ejecutar en mica_store. No elimina datos.

SET @db = DATABASE();

SET @existe := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'descripcion_larga'
);
SET @sql := IF(@existe = 0, 'ALTER TABLE productos ADD COLUMN descripcion_larga TEXT NULL AFTER descripcion_corta', 'SELECT "descripcion_larga ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'ficha_tecnica'
);
SET @sql := IF(@existe = 0, 'ALTER TABLE productos ADD COLUMN ficha_tecnica TEXT NULL AFTER descripcion_larga', 'SELECT "ficha_tecnica ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'garantia'
);
SET @sql := IF(@existe = 0, 'ALTER TABLE productos ADD COLUMN garantia VARCHAR(180) NULL AFTER ficha_tecnica', 'SELECT "garantia ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'pdf_ficha'
);
SET @sql := IF(@existe = 0, 'ALTER TABLE productos ADD COLUMN pdf_ficha VARCHAR(255) NULL AFTER garantia', 'SELECT "pdf_ficha ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS producto_especificaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  valor VARCHAR(255) NOT NULL,
  orden INT DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(producto_id)
);

CREATE TABLE IF NOT EXISTS producto_caracteristicas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  icono VARCHAR(20) DEFAULT '✔',
  texto VARCHAR(255) NOT NULL,
  orden INT DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(producto_id)
);

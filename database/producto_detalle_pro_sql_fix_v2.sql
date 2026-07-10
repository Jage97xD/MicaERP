-- Mica Store Producto Detalle PRO - SQL Fix v2
-- Compatible con MySQL que no acepta ADD COLUMN IF NOT EXISTS.
-- Ejecutar dentro de la base de datos mica_store.

SET @db = DATABASE();

SET @existe := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'productos'
    AND COLUMN_NAME = 'descripcion_larga'
);

SET @sql := IF(
    @existe = 0,
    'ALTER TABLE productos ADD COLUMN descripcion_larga TEXT NULL AFTER descripcion_corta',
    'SELECT "descripcion_larga ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


SET @existe := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'productos'
    AND COLUMN_NAME = 'ficha_tecnica'
);

SET @sql := IF(
    @existe = 0,
    'ALTER TABLE productos ADD COLUMN ficha_tecnica TEXT NULL AFTER descripcion_larga',
    'SELECT "ficha_tecnica ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


SET @existe := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'productos'
    AND COLUMN_NAME = 'garantia'
);

SET @sql := IF(
    @existe = 0,
    'ALTER TABLE productos ADD COLUMN garantia VARCHAR(180) NULL AFTER ficha_tecnica',
    'SELECT "garantia ya existe"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


CREATE TABLE IF NOT EXISTS producto_vistos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  session_id VARCHAR(120) NOT NULL,
  visto_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(producto_id),
  INDEX(session_id)
);

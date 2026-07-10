-- MicaStore - Pedidos / Tracking v1
-- Ejecutar una sola vez en la base mica_store.

SET @db := DATABASE();

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='pago_validado');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN pago_validado TINYINT DEFAULT 0 AFTER estado', 'SELECT "pago_validado ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='fecha_pago_validado');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN fecha_pago_validado DATETIME NULL AFTER pago_validado', 'SELECT "fecha_pago_validado ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='fecha_salida');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN fecha_salida DATETIME NULL AFTER fecha_pago_validado', 'SELECT "fecha_salida ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='fecha_entrega_estimada');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN fecha_entrega_estimada DATETIME NULL AFTER fecha_salida', 'SELECT "fecha_entrega_estimada ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='fecha_entregado');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN fecha_entregado DATETIME NULL AFTER fecha_entrega_estimada', 'SELECT "fecha_entregado ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='tracking_observacion');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN tracking_observacion TEXT NULL AFTER fecha_entregado', 'SELECT "tracking_observacion ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='tracking_actualizado_en');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN tracking_actualizado_en DATETIME NULL AFTER tracking_observacion', 'SELECT "tracking_actualizado_en ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @existe := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='cotizaciones' AND COLUMN_NAME='cliente_web_id');
SET @sql := IF(@existe=0, 'ALTER TABLE cotizaciones ADD COLUMN cliente_web_id INT NULL AFTER cliente_id', 'SELECT "cliente_web_id ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE cotizaciones SET estado='Pendiente de revisión' WHERE estado='Pendiente';
UPDATE cotizaciones SET estado='Pedido aceptado' WHERE estado IN ('Confirmada','Atendida','Atendido');
UPDATE cotizaciones SET estado='Cancelado' WHERE estado='Anulado';

CREATE TABLE IF NOT EXISTS header_menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(120) NOT NULL,
  icono VARCHAR(20) DEFAULT '',
  url VARCHAR(255) NOT NULL,
  tipo VARCHAR(40) DEFAULT 'link',
  visible TINYINT DEFAULT 1,
  visible_desktop TINYINT DEFAULT 1,
  visible_mobile TINYINT DEFAULT 1,
  orden INT DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO header_menu_items (titulo, icono, url, tipo, visible, visible_desktop, visible_mobile, orden) VALUES
('Inicio','🏠','tienda_visual_v3.php','link',1,1,1,1),
('Productos','🛍','tienda_visual_v3.php#productos','link',1,1,1,2),
('Categorías','📂','#categorias','categorias',1,1,1,3),
('Marcas','🏷','marcas.php','link',1,1,1,4),
('Ofertas','⭐','ofertas.php','link',1,1,1,5),
('Mis pedidos','📦','cliente/mis_pedidos.php','link',1,1,1,6),
('Contáctenos','📞','contacto.php','link',1,1,1,7)
ON DUPLICATE KEY UPDATE titulo=VALUES(titulo);

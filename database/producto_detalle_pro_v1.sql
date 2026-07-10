-- Mica Store Producto Detalle PRO v1
-- Ejecutar en mica_store. No elimina datos.

ALTER TABLE productos
ADD COLUMN IF NOT EXISTS descripcion_larga TEXT NULL AFTER descripcion_corta,
ADD COLUMN IF NOT EXISTS ficha_tecnica TEXT NULL AFTER descripcion_larga,
ADD COLUMN IF NOT EXISTS garantia VARCHAR(180) NULL AFTER ficha_tecnica;

CREATE TABLE IF NOT EXISTS producto_vistos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  session_id VARCHAR(120) NOT NULL,
  visto_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(producto_id),
  INDEX(session_id)
);

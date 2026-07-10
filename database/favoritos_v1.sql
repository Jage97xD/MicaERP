-- Mica Store Favoritos v1
-- Ejecutar dentro de mica_store.
-- No borra datos.

CREATE TABLE IF NOT EXISTS cliente_favoritos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  producto_id INT NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cliente_producto (cliente_id, producto_id),
  INDEX(cliente_id),
  INDEX(producto_id)
);

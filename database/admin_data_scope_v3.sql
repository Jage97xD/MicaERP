-- MicaStore ERP Core v3 - Alcance de datos por categoría
-- Este script es opcional si ya ejecutas /admin/migrar_erp_core.php,
-- porque el migrador crea esta tabla automáticamente.

CREATE TABLE IF NOT EXISTS admin_usuario_categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  categoria_id INT NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_usuario_categoria (usuario_id,categoria_id),
  INDEX(usuario_id),
  INDEX(categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

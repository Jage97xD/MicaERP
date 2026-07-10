CREATE TABLE IF NOT EXISTS inventario_movimientos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  tipo ENUM('Entrada','Salida','Ajuste') NOT NULL,
  cantidad INT NOT NULL,
  stock_anterior INT NOT NULL DEFAULT 0,
  stock_nuevo INT NOT NULL DEFAULT 0,
  motivo VARCHAR(180),
  referencia VARCHAR(100),
  usuario VARCHAR(100) DEFAULT 'Admin',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

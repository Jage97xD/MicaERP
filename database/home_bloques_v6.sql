CREATE TABLE IF NOT EXISTS home_bloques (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(180) NOT NULL,
  subtitulo VARCHAR(255),
  contenido TEXT,
  tipo ENUM('info','banner','html') DEFAULT 'info',
  orden INT DEFAULT 0,
  activo TINYINT DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO home_bloques (titulo, subtitulo, contenido, tipo, orden, activo) VALUES
('Novedades y ofertas','Recibe novedades y ofertas','Déjanos tu correo para recibir promociones, nuevos ingresos y campañas de Mica Store.','banner',10,1)
ON DUPLICATE KEY UPDATE titulo=titulo;

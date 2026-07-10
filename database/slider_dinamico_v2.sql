CREATE TABLE IF NOT EXISTS sliders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(180) NOT NULL,
  titulo_resaltado VARCHAR(120),
  subtitulo VARCHAR(255),
  texto_boton VARCHAR(80),
  url_boton VARCHAR(255),
  imagen VARCHAR(255),
  color_inicio VARCHAR(20) DEFAULT '#020817',
  color_fin VARCHAR(20) DEFAULT '#001b47',
  color_resaltado VARCHAR(20) DEFAULT '#37c5ff',
  orden INT DEFAULT 0,
  activo TINYINT DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

INSERT INTO sliders (titulo, titulo_resaltado, subtitulo, texto_boton, url_boton, color_inicio, color_fin, color_resaltado, orden, activo) VALUES
('TECNOLOGÍA QUE','CONECTA','Laptops, licencias, tóners, redes y servicios técnicos.','Ver tecnología','tienda_visual.php?categoria=tecnologia','#020817','#001b47','#37c5ff',1,1),
('HOGAR QUE','INSPIRA','Decoración, cocina, organización y productos para tu casa.','Ver hogar','tienda_visual.php?categoria=hogar','#052e16','#14532d','#86efac',2,1),
('FERRETERÍA PARA','CONSTRUIR','Herramientas, electricidad, gasfitería y construcción.','Ver ferretería','tienda_visual.php?categoria=ferreteria-construccion','#1c1917','#7c2d12','#fb923c',3,1),
('BELLEZA Y','CUIDADO','Skincare, maquillaje y productos de cuidado personal.','Ver belleza','tienda_visual.php?categoria=belleza-cuidado-personal','#500724','#831843','#f9a8d4',4,1);

-- Mica Store - Unificación v1
-- Ejecutar en mica_store. No elimina datos.

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

CREATE TABLE IF NOT EXISTS store_builder (
  id INT AUTO_INCREMENT PRIMARY KEY,
  componente VARCHAR(80) NOT NULL UNIQUE,
  visible TINYINT DEFAULT 1,
  texto VARCHAR(180),
  url VARCHAR(255),
  x INT DEFAULT 0,
  y INT DEFAULT 0,
  ancho INT DEFAULT 160,
  alto INT DEFAULT 50,
  color_fondo VARCHAR(20) DEFAULT '',
  color_texto VARCHAR(20) DEFAULT '',
  orden INT DEFAULT 0,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Componentes base del header
INSERT INTO store_builder (componente, visible, texto, url, x, y, ancho, alto, color_fondo, color_texto, orden) VALUES
('logo',1,'MICA STORE','tienda_visual.php',30,25,220,75,'','',1),
('buscador',1,'Buscar productos, marcas y más...','',285,40,560,55,'#ffffff','#111827',2),
('login',1,'Iniciar sesión','#',915,40,150,45,'#ffffff','#111827',3),
('cotizacion',1,'Cotización','cotizacion_mysql.php',1075,40,160,45,'#ffffff','#111827',4),
('whatsapp',1,'WhatsApp','https://wa.me/51920137707',1245,40,145,45,'#22c55e','#ffffff',5),
('menu',0,'Menú principal','',0,0,0,0,'#111827','#ffffff',99)
ON DUPLICATE KEY UPDATE componente = componente;

-- Sliders iniciales si la tabla está vacía
INSERT INTO sliders (titulo, titulo_resaltado, subtitulo, texto_boton, url_boton, color_inicio, color_fin, color_resaltado, orden, activo)
SELECT 'TECNOLOGÍA QUE','CONECTA','Laptops, licencias, tóners, redes y servicios técnicos.','Ver tecnología','tienda_visual.php?categoria=tecnologia','#020817','#001b47','#37c5ff',1,1
WHERE NOT EXISTS (SELECT 1 FROM sliders LIMIT 1);

INSERT INTO sliders (titulo, titulo_resaltado, subtitulo, texto_boton, url_boton, color_inicio, color_fin, color_resaltado, orden, activo)
SELECT 'HOGAR QUE','INSPIRA','Decoración, cocina, organización y productos para tu casa.','Ver hogar','tienda_visual.php?categoria=hogar','#052e16','#14532d','#86efac',2,1
WHERE (SELECT COUNT(*) FROM sliders) < 2;

INSERT INTO sliders (titulo, titulo_resaltado, subtitulo, texto_boton, url_boton, color_inicio, color_fin, color_resaltado, orden, activo)
SELECT 'FERRETERÍA PARA','CONSTRUIR','Herramientas, electricidad, gasfitería y construcción.','Ver ferretería','tienda_visual.php?categoria=ferreteria-construccion','#1c1917','#7c2d12','#fb923c',3,1
WHERE (SELECT COUNT(*) FROM sliders) < 3;

INSERT INTO sliders (titulo, titulo_resaltado, subtitulo, texto_boton, url_boton, color_inicio, color_fin, color_resaltado, orden, activo)
SELECT 'BELLEZA Y','CUIDADO','Skincare, maquillaje y productos de cuidado personal.','Ver belleza','tienda_visual.php?categoria=belleza-cuidado-personal','#500724','#831843','#f9a8d4',4,1
WHERE (SELECT COUNT(*) FROM sliders) < 4;

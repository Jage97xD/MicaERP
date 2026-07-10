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

INSERT INTO store_builder (componente, visible, texto, url, x, y, ancho, alto, color_fondo, color_texto, orden) VALUES
('logo',1,'Mica Store','tienda_visual.php',30,30,220,80,'#ffffff','#0057d9',1),
('buscador',1,'Buscar productos, marcas y más...','',310,42,560,55,'#ffffff','#111827',2),
('login',1,'Iniciar sesión','login.php',930,35,160,42,'#ffffff','#111827',3),
('cotizacion',1,'Cotización','cotizacion_mysql.php',1110,35,150,42,'#ffffff','#111827',4),
('menu',1,'Inicio | Catálogo | Tecnología | Ferretería | Hogar | Belleza','',260,125,760,52,'#111827','#ffffff',5),
('whatsapp',1,'WhatsApp','https://wa.me/51920137707',1040,125,140,52,'#22c55e','#ffffff',6)
ON DUPLICATE KEY UPDATE componente = componente;

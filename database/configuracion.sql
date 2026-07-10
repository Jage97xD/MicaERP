CREATE TABLE IF NOT EXISTS configuracion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(80) NOT NULL UNIQUE,
  valor TEXT,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO configuracion (clave, valor) VALUES
('nombre_comercial','Mica Store'),
('razon_social','Mica Store'),
('ruc',''),
('direccion','Mercado La Chacra - Lurigancho'),
('telefono',''),
('whatsapp','51920137707'),
('correo','ventas@micastore.pe'),
('facebook',''),
('instagram',''),
('tiktok',''),
('youtube',''),
('horario','Lunes a sábado de 9:00 am a 7:00 pm'),
('moneda','S/'),
('igv','18'),
('color_principal','#0057d9'),
('color_secundario','#06b6d4'),
('logo','')
ON DUPLICATE KEY UPDATE clave = clave;
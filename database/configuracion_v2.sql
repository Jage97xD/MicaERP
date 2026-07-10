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
('logo',''),
('favicon',''),
('apariencia_fondo_tipo','degradado'),
('apariencia_fondo_imagen',''),
('apariencia_fondo_size','cover'),
('apariencia_fondo_posicion','center'),
('apariencia_fondo_repetir','no-repeat'),
('apariencia_fondo_opacidad','85'),
('header_logo_posicion','izquierda'),
('header_orden','logo-buscador-acciones'),
('header_mostrar_topbar','1'),
('header_mostrar_redes','1'),
('header_mostrar_buscador','1'),
('header_mostrar_login','1'),
('header_mostrar_cotizacion','1')
ON DUPLICATE KEY UPDATE clave = clave;

CREATE TABLE IF NOT EXISTS configuracion_campos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  clave VARCHAR(120) NOT NULL UNIQUE,
  valor TEXT,
  ubicacion ENUM('header','footer','contacto','oculto') DEFAULT 'footer',
  activo TINYINT DEFAULT 1,
  orden INT DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

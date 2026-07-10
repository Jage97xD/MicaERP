-- MICA STORE v2 - Script maestro de integración
-- Ejecutar en la base de datos mica_store

CREATE TABLE IF NOT EXISTS admin_usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  usuario VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol VARCHAR(50) DEFAULT 'Administrador',
  activo TINYINT DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
('header_mostrar_cotizacion','1'),
('preview_logo_x','35'),
('preview_logo_y','35'),
('preview_nombre_x','160'),
('preview_nombre_y','45'),
('preview_info_x','35'),
('preview_info_y','170')
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
('logo',1,'Mica Store','',30,35,220,80,'','',1),
('buscador',1,'Buscar productos, marcas y más...','',330,45,520,55,'#ffffff','#111827',2),
('login',1,'Iniciar sesión','login.php',1020,35,150,40,'','',3),
('cotizacion',1,'Cotización','cotizacion_mysql.php',1020,82,150,40,'','',4),
('menu',1,'Inicio | Catálogo | Tecnología | Ferretería | Hogar | Belleza','',300,135,650,50,'#111827','#ffffff',5),
('whatsapp',1,'WhatsApp','',1180,35,120,40,'#22c55e','#ffffff',6)
ON DUPLICATE KEY UPDATE componente = componente;

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

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  documento VARCHAR(30) NOT NULL UNIQUE,
  celular VARCHAR(30),
  correo VARCHAR(120),
  direccion VARCHAR(180),
  distrito VARCHAR(100),
  provincia VARCHAR(100),
  activo TINYINT DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cotizaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NULL,
  nombre_cliente VARCHAR(150) NOT NULL,
  documento VARCHAR(30) NOT NULL,
  celular VARCHAR(30) NOT NULL,
  tipo_entrega VARCHAR(50),
  destino VARCHAR(150),
  subtotal DECIMAL(10,2) DEFAULT 0,
  envio DECIMAL(10,2) DEFAULT 0,
  total DECIMAL(10,2) DEFAULT 0,
  comentarios TEXT,
  estado VARCHAR(30) DEFAULT 'Pendiente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cotizacion_detalle (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cotizacion_id INT NOT NULL,
  producto_id INT NOT NULL,
  producto_nombre VARCHAR(180) NOT NULL,
  cantidad INT DEFAULT 1,
  precio DECIMAL(10,2) DEFAULT 0,
  subtotal DECIMAL(10,2) DEFAULT 0
);

-- Agrega columnas si faltan
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'imagenes_producto'
  AND COLUMN_NAME = 'principal'
);
SET @sql := IF(@col_exists = 0, 'ALTER TABLE imagenes_producto ADD COLUMN principal TINYINT DEFAULT 0 AFTER orden', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists2 := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'cotizaciones'
  AND COLUMN_NAME = 'cliente_id'
);
SET @sql2 := IF(@col_exists2 = 0, 'ALTER TABLE cotizaciones ADD COLUMN cliente_id INT NULL AFTER id', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

CREATE TABLE IF NOT EXISTS libro_reclamaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NULL,
  tipo VARCHAR(30) NOT NULL,
  nombre VARCHAR(160) NOT NULL,
  documento VARCHAR(30) NULL,
  correo VARCHAR(160) NULL,
  celular VARCHAR(40) NULL,
  direccion VARCHAR(220) NULL,
  producto_servicio VARCHAR(180) NULL,
  detalle TEXT NOT NULL,
  pedido TEXT NULL,
  estado VARCHAR(40) DEFAULT 'Nuevo',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO configuracion (clave, valor) VALUES
('mision','Brindar productos y servicios de calidad, con atención cercana y confiable.'),
('vision','Ser una tienda referente, integrando tecnología y mejora continua.'),
('valores','Honestidad, responsabilidad, respeto, innovación y compromiso con el cliente.'),
('publicidad_web_activa','1'),
('publicidad_web_texto','¿Te gustó esta página web y quieres crear la tuya? Comunícate con nosotros.'),
('publicidad_web_whatsapp','964546833'),
('publicidad_web_firma','Desarrollado con MicaStore ERP');

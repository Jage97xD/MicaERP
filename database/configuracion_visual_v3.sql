INSERT INTO configuracion (clave, valor) VALUES
('preview_logo_x','35'),
('preview_logo_y','35'),
('preview_nombre_x','160'),
('preview_nombre_y','45'),
('preview_info_x','35'),
('preview_info_y','170')
ON DUPLICATE KEY UPDATE clave = clave;

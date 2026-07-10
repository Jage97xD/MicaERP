INSERT INTO categorias (nombre, slug, color, icono) VALUES
('Tecnología','tecnologia','#0057d9','💻'),
('Ferretería y Construcción','ferreteria-construccion','#e67e00','🧰'),
('Hogar','hogar','#8b5e34','🏠'),
('Belleza y Cuidado Personal','belleza-cuidado-personal','#d63384','💄');

INSERT INTO subcategorias (categoria_id,nombre,slug) VALUES
(1,'Laptops','laptops'),(1,'Tóners','toners'),(1,'Licencias','licencias'),(1,'Servicios TI','servicios-ti'),
(2,'Herramientas','herramientas'),(2,'Electricidad','electricidad'),(2,'Gasfitería','gasfiteria'),(2,'Servicios de construcción','servicios-construccion'),
(3,'Decoración','decoracion'),(3,'Cocina','cocina'),(3,'Organización','organizacion'),
(4,'Skincare','skincare'),(4,'Maquillaje','maquillaje'),(4,'Labiales','labiales');

INSERT INTO marcas (nombre,slug) VALUES
('HP','hp'),('Lenovo','lenovo'),('Samsung','samsung'),('Brother','brother'),('Epson','epson'),('Bosch','bosch'),('Stanley','stanley'),('Mica Store','mica-store');
UPDATE store_builder SET x=30, y=22, ancho=220, alto=80, texto='MICA
STORE', color_fondo='', color_texto='#061a3d', orden=1 WHERE componente='logo';
UPDATE store_builder SET x=285, y=40, ancho=1180, alto=55, texto='Buscar productos, marcas y más...', color_fondo='#ffffff', color_texto='#111827', orden=2 WHERE componente='buscador';
UPDATE store_builder SET x=1490, y=35, ancho=120, alto=48, texto='Iniciar sesión', color_fondo='#ffffff', color_texto='#111827', orden=3 WHERE componente='login';
UPDATE store_builder SET x=1625, y=35, ancho=125, alto=48, texto='Cotización', color_fondo='#ffffff', color_texto='#111827', orden=4 WHERE componente='cotizacion';
UPDATE store_builder SET x=0, y=126, ancho=1000, alto=53, texto='Inicio | Catálogo | Tecnología | Ferretería | Hogar | Belleza', color_fondo='#111827', color_texto='#ffffff', orden=5 WHERE componente='menu';
UPDATE store_builder SET visible=0 WHERE componente='whatsapp';

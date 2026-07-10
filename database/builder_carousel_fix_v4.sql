UPDATE store_builder SET x=30, y=22, ancho=220, alto=80, texto='MICA
STORE', color_fondo='', color_texto='#061a3d', orden=1 WHERE componente='logo';

UPDATE store_builder SET x=220, y=40, ancho=605, alto=62, texto='Buscar productos, marcas y más...', color_fondo='#ffffff', color_texto='#111827', orden=2 WHERE componente='buscador';

UPDATE store_builder SET x=1210, y=35, ancho=160, alto=42, texto='Iniciar sesión', color_fondo='#ffffff', color_texto='#111827', orden=3 WHERE componente='login';

UPDATE store_builder SET x=1190, y=86, ancho=170, alto=42, texto='Cotización', color_fondo='#ffffff', color_texto='#111827', orden=4 WHERE componente='cotizacion';

UPDATE store_builder SET visible=1, texto='Menú principal', orden=5 WHERE componente='menu';

UPDATE store_builder SET visible=1, x=1370, y=24, ancho=140, alto=48, texto='WhatsApp', color_fondo='#22c55e', color_texto='#ffffff', orden=6 WHERE componente='whatsapp';

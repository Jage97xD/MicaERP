-- MicaStore Categorías Inteligentes v1
-- Ejecutar en phpMyAdmin sobre la base mica_store

ALTER TABLE categorias
ADD COLUMN tipo_categoria VARCHAR(30) DEFAULT 'normal',
ADD COLUMN usa_marca TINYINT DEFAULT 1,
ADD COLUMN usa_sku TINYINT DEFAULT 1,
ADD COLUMN usa_codigo TINYINT DEFAULT 1,
ADD COLUMN usa_peso TINYINT DEFAULT 0,
ADD COLUMN usa_vencimiento TINYINT DEFAULT 0;

-- Opcional: si ya tienes categorías creadas, puedes ajustar algunas así:
-- UPDATE categorias SET tipo_categoria='alimenticio', usa_marca=0, usa_sku=0, usa_codigo=0, usa_peso=1, usa_vencimiento=1 WHERE slug IN ('quesos','carnes','comida','jugos');
-- UPDATE categorias SET tipo_categoria='normal', usa_marca=1, usa_sku=1, usa_codigo=1 WHERE slug IN ('tecnologia','ferreteria','pintura');

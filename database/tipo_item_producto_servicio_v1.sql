-- MicaStore Tipo Item Producto/Servicio v1
-- Ejecutar en phpMyAdmin sobre la base mica_store

ALTER TABLE productos
ADD COLUMN tipo_item VARCHAR(30) DEFAULT 'producto';

-- Opcional si aún no existen:
ALTER TABLE productos
ADD COLUMN duracion_servicio VARCHAR(80) NULL;

ALTER TABLE productos
ADD COLUMN modalidad_servicio VARCHAR(80) NULL;

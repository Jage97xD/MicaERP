-- MicaStore Tipo Servicios v1
-- Ejecutar en phpMyAdmin sobre mica_store
-- Agrega columnas opcionales a productos para manejar servicios.

ALTER TABLE productos
ADD COLUMN IF NOT EXISTS duracion_servicio VARCHAR(80) NULL,
ADD COLUMN IF NOT EXISTS modalidad_servicio VARCHAR(80) NULL;

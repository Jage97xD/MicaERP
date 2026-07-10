-- MicaStore Producto PRO Paso Categoría v2
-- Ejecuta solo si alguna columna NO existe.
-- Si te aparece Duplicate column name, significa que ya existe y puedes ignorarlo.

ALTER TABLE productos
ADD COLUMN tipo_item VARCHAR(30) DEFAULT 'producto';

ALTER TABLE productos
ADD COLUMN duracion_servicio VARCHAR(80) NULL;

ALTER TABLE productos
ADD COLUMN modalidad_servicio VARCHAR(80) NULL;

ALTER TABLE productos
ADD COLUMN peso_unidad VARCHAR(80) NULL;

ALTER TABLE productos
ADD COLUMN fecha_vencimiento DATE NULL;

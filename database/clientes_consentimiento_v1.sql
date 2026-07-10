-- MicaStore - consentimiento comercial de clientes
-- Si alguna columna ya existe, omite esa línea.
ALTER TABLE clientes ADD COLUMN acepta_ofertas TINYINT(1) DEFAULT 0;
ALTER TABLE clientes ADD COLUMN acepta_contacto TINYINT(1) DEFAULT 0;
ALTER TABLE clientes ADD COLUMN fecha_consentimiento DATETIME NULL;
ALTER TABLE clientes_web ADD COLUMN acepta_ofertas TINYINT(1) DEFAULT 0;
ALTER TABLE clientes_web ADD COLUMN acepta_contacto TINYINT(1) DEFAULT 0;
ALTER TABLE clientes_web ADD COLUMN fecha_consentimiento DATETIME NULL;

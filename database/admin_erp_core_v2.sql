-- MicaStore ERP Core v2
-- Si tu MySQL/MariaDB no acepta algunos ALTER porque la columna ya existe, omite esa línea.
-- Recomendado: abrir /micastore/admin/migrar_erp_core.php en el navegador para una migración automática segura.

CREATE TABLE IF NOT EXISTS admin_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  descripcion VARCHAR(255) NULL,
  activo TINYINT(1) DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_permisos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  modulo VARCHAR(80) NOT NULL,
  accion VARCHAR(30) NOT NULL,
  descripcion VARCHAR(180) NULL,
  UNIQUE KEY uk_permiso (modulo,accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_rol_permisos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rol_id INT NOT NULL,
  permiso_id INT NOT NULL,
  permitido TINYINT(1) DEFAULT 1,
  UNIQUE KEY uk_rol_permiso (rol_id,permiso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_auditoria (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  usuario_nombre VARCHAR(150) NULL,
  modulo VARCHAR(80) NULL,
  accion VARCHAR(80) NULL,
  descripcion TEXT NULL,
  referencia_tabla VARCHAR(100) NULL,
  referencia_id VARCHAR(80) NULL,
  ip VARCHAR(60) NULL,
  user_agent VARCHAR(255) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_accesos (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  usuario VARCHAR(120) NULL,
  exito TINYINT(1) DEFAULT 0,
  mensaje VARCHAR(255) NULL,
  ip VARCHAR(60) NULL,
  user_agent VARCHAR(255) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO admin_roles (nombre,descripcion,activo) VALUES
('Administrador','Acceso completo al ERP.',1),
('Ventas','Clientes, cotizaciones y seguimiento comercial.',1),
('Almacen','Productos, stock e inventario.',1),
('Compras','Proveedores, compras e inventario.',1),
('Marketing','Contenido de tienda, carrusel, marcas y apariencia.',1),
('Atencion al cliente','Clientes, cotizaciones y pedidos.',1),
('Soporte','Soporte operativo.',1);

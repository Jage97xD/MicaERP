<?php
/**
 * MicaStore ERP Core
 * Usuarios, roles, permisos, auditoría, accesos y recuperación de contraseña.
 * Compatible con MySQL/MariaDB antiguos: no usa ADD COLUMN IF NOT EXISTS.
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function erp_table_exists(PDO $pdo, string $table): bool{
    try{ $st=$pdo->prepare("SHOW TABLES LIKE ?"); $st->execute([$table]); return (bool)$st->fetchColumn(); }catch(Exception $e){ return false; }
}
function erp_columns(PDO $pdo, string $table): array{
    $cols=[]; try{ foreach($pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC) as $r){ $cols[$r['Field']]=true; } }catch(Exception $e){} return $cols;
}
function erp_add_column(PDO $pdo, string $table, string $column, string $definition): void{
    $cols=erp_columns($pdo,$table); if(!isset($cols[$column])){ $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition"); }
}
function erp_client_ip(): string{
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
}

function erp_ensure_core(PDO $pdo): void{
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_usuarios (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(120) NOT NULL,
      usuario VARCHAR(80) NOT NULL UNIQUE,
      correo VARCHAR(150) NULL,
      password_hash VARCHAR(255) NOT NULL,
      rol VARCHAR(50) DEFAULT 'Administrador',
      rol_id INT NULL,
      activo TINYINT(1) DEFAULT 1,
      ultimo_login DATETIME NULL,
      ultimo_ip VARCHAR(60) NULL,
      intentos INT DEFAULT 0,
      bloqueado_hasta DATETIME NULL,
      token_recuperacion VARCHAR(120) NULL,
      token_expira DATETIME NULL,
      debe_cambiar_password TINYINT(1) DEFAULT 0,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    erp_add_column($pdo,'admin_usuarios','correo',"correo VARCHAR(150) NULL AFTER usuario");
    erp_add_column($pdo,'admin_usuarios','rol_id',"rol_id INT NULL AFTER rol");
    erp_add_column($pdo,'admin_usuarios','ultimo_login',"ultimo_login DATETIME NULL AFTER activo");
    erp_add_column($pdo,'admin_usuarios','ultimo_ip',"ultimo_ip VARCHAR(60) NULL AFTER ultimo_login");
    erp_add_column($pdo,'admin_usuarios','intentos',"intentos INT DEFAULT 0 AFTER ultimo_ip");
    erp_add_column($pdo,'admin_usuarios','bloqueado_hasta',"bloqueado_hasta DATETIME NULL AFTER intentos");
    erp_add_column($pdo,'admin_usuarios','token_recuperacion',"token_recuperacion VARCHAR(120) NULL AFTER bloqueado_hasta");
    erp_add_column($pdo,'admin_usuarios','token_expira',"token_expira DATETIME NULL AFTER token_recuperacion");
    erp_add_column($pdo,'admin_usuarios','debe_cambiar_password',"debe_cambiar_password TINYINT(1) DEFAULT 0 AFTER token_expira");
    erp_add_column($pdo,'admin_usuarios','tienda_id',"tienda_id INT NULL AFTER rol_id");
    if(erp_table_exists($pdo,'productos')){
        erp_add_column($pdo,'productos','tienda_id',"tienda_id INT NULL AFTER marca_id");
    }
    erp_add_column($pdo,'admin_usuarios','actualizado_en',"actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

    // Consentimiento comercial de clientes.
    if(erp_table_exists($pdo,'clientes')){
        erp_add_column($pdo,'clientes','acepta_ofertas',"acepta_ofertas TINYINT(1) DEFAULT 0");
        erp_add_column($pdo,'clientes','acepta_contacto',"acepta_contacto TINYINT(1) DEFAULT 0");
        erp_add_column($pdo,'clientes','fecha_consentimiento',"fecha_consentimiento DATETIME NULL");
    }
    if(erp_table_exists($pdo,'clientes_web')){
        erp_add_column($pdo,'clientes_web','acepta_ofertas',"acepta_ofertas TINYINT(1) DEFAULT 0");
        erp_add_column($pdo,'clientes_web','acepta_contacto',"acepta_contacto TINYINT(1) DEFAULT 0");
        erp_add_column($pdo,'clientes_web','fecha_consentimiento',"fecha_consentimiento DATETIME NULL");
    }


    // TopBar administrable de la tienda pública.
    $pdo->exec("CREATE TABLE IF NOT EXISTS topbar_items (
      id INT AUTO_INCREMENT PRIMARY KEY,
      grupo ENUM('izquierda','derecha') DEFAULT 'izquierda',
      icono VARCHAR(20) NULL,
      texto VARCHAR(180) NOT NULL,
      tipo_enlace ENUM('ninguno','url','maps','contacto','interno') DEFAULT 'ninguno',
      url VARCHAR(255) NULL,
      visible TINYINT(1) DEFAULT 1,
      nueva_pestana TINYINT(1) DEFAULT 0,
      orden INT DEFAULT 0,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try{
        $countTopbar = (int)$pdo->query("SELECT COUNT(*) FROM topbar_items")->fetchColumn();
        if($countTopbar === 0){
            $insTopbar = $pdo->prepare("INSERT INTO topbar_items (grupo,icono,texto,tipo_enlace,url,visible,nueva_pestana,orden) VALUES (?,?,?,?,?,?,?,?)");
            $insTopbar->execute(['izquierda','🚚','Envíos a todo el Perú','ninguno','',1,0,1]);
            $insTopbar->execute(['izquierda','🛡️','Garantía en productos','ninguno','',1,0,2]);
            $insTopbar->execute(['izquierda','📍','Mercado La Chacra - Lurigancho','maps','',1,1,3]);
            $insTopbar->execute(['derecha','','Facebook','url','',1,1,10]);
            $insTopbar->execute(['derecha','','Instagram','url','',1,1,11]);
            $insTopbar->execute(['derecha','','TikTok','url','',1,1,12]);
        }
    }catch(Exception $e){}

    $pdo->exec("CREATE TABLE IF NOT EXISTS libro_reclamaciones (
      id INT AUTO_INCREMENT PRIMARY KEY,
      codigo VARCHAR(30) NULL,
      tipo VARCHAR(30) NOT NULL,
      nombre VARCHAR(160) NOT NULL,
      documento VARCHAR(30) NULL,
      correo VARCHAR(160) NULL,
      celular VARCHAR(40) NULL,
      direccion VARCHAR(220) NULL,
      producto_servicio VARCHAR(180) NULL,
      detalle TEXT NOT NULL,
      pedido TEXT NULL,
      estado VARCHAR(40) DEFAULT 'Nuevo',
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try{
      $cfgIns = $pdo->prepare("INSERT IGNORE INTO configuracion (clave, valor) VALUES (?, ?)");
      $cfgIns->execute(['mision','Brindar productos y servicios de calidad, con atención cercana y confiable.']);
      $cfgIns->execute(['vision','Ser una tienda referente, integrando tecnología y mejora continua.']);
      $cfgIns->execute(['valores','Honestidad, responsabilidad, respeto, innovación y compromiso con el cliente.']);
      $cfgIns->execute(['publicidad_web_activa','1']);
      $cfgIns->execute(['publicidad_web_texto','¿Te gustó esta página web y quieres crear la tuya? Comunícate con nosotros.']);
      $cfgIns->execute(['publicidad_web_whatsapp','964546833']);
      $cfgIns->execute(['publicidad_web_firma','Desarrollado con MicaStore ERP']);
    }catch(Exception $e){}



    // CMS / Contenido del sitio y módulo Trabaja con nosotros.
    $pdo->exec("CREATE TABLE IF NOT EXISTS rrhh_puestos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      titulo VARCHAR(180) NOT NULL,
      area VARCHAR(120) NULL,
      modalidad VARCHAR(80) NULL,
      ubicacion VARCHAR(160) NULL,
      vacantes INT DEFAULT 1,
      salario VARCHAR(120) NULL,
      descripcion TEXT NULL,
      requisitos TEXT NULL,
      beneficios TEXT NULL,
      fecha_limite DATE NULL,
      estado VARCHAR(30) DEFAULT 'Activo',
      orden INT DEFAULT 0,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS rrhh_postulantes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      puesto_id INT NOT NULL,
      nombre VARCHAR(160) NOT NULL,
      correo VARCHAR(160) NULL,
      celular VARCHAR(40) NULL,
      documento VARCHAR(40) NULL,
      experiencia TEXT NULL,
      mensaje TEXT NULL,
      cv_archivo VARCHAR(255) NULL,
      estado VARCHAR(40) DEFAULT 'Nuevo',
      nota_interna TEXT NULL,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX(puesto_id), INDEX(estado), INDEX(creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try{
      $cfgCms = $pdo->prepare("INSERT IGNORE INTO configuracion (clave, valor) VALUES (?, ?)");
      $cfgCms->execute(['footer_descripcion','Catálogo online de productos y servicios. Atención por WhatsApp, cotizaciones y seguimiento de pedidos.']);
      $cfgCms->execute(['newsletter_activo','1']);
      $cfgCms->execute(['newsletter_titulo','Novedades y ofertas']);
      $cfgCms->execute(['newsletter_texto','Déjanos tu correo para recibir promociones, nuevos ingresos y campañas.']);
      $cfgCms->execute(['home_contacto_titulo','Estamos para atenderte']);
      $cfgCms->execute(['home_contacto_texto','Cotiza por WhatsApp, revisa nuestra ubicación o escríbenos para recibir atención personalizada.']);
      $cfgCms->execute(['publicidad_web_titulo','¿Necesitas una tienda como esta?']);
      $cfgCms->execute(['publicidad_web_firma','MicaStore ERP']);
      $cfgCms->execute(['publicidad_web_texto','Creamos tiendas, catálogos y sistemas para negocios. Comunícate para una demostración.']);
      $cfgCms->execute(['publicidad_web_activa','1']);
      $cfgCms->execute(['trabaja_activo','1']);
      $cfgCms->execute(['trabaja_titulo','Trabaja con nosotros']);
      $cfgCms->execute(['trabaja_texto','Publicamos oportunidades laborales para personas con actitud, responsabilidad y ganas de crecer.']);
    }catch(Exception $e){}

    try{
      // Menú público administrable y enlace a Trabaja con nosotros.
      $pdo->exec("CREATE TABLE IF NOT EXISTS header_menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(120) NOT NULL,
        icono VARCHAR(20) DEFAULT '',
        url VARCHAR(255) NOT NULL,
        tipo VARCHAR(40) DEFAULT 'link',
        visible TINYINT DEFAULT 1,
        visible_desktop TINYINT DEFAULT 1,
        visible_mobile TINYINT DEFAULT 1,
        orden INT DEFAULT 0,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      $stMenu = $pdo->prepare("SELECT COUNT(*) FROM header_menu_items WHERE url='trabaja_con_nosotros.php'");
      $stMenu->execute();
      if((int)$stMenu->fetchColumn() === 0){
        $pdo->prepare("INSERT INTO header_menu_items (titulo,icono,url,tipo,visible,visible_desktop,visible_mobile,orden) VALUES ('Trabaja con nosotros','💼','trabaja_con_nosotros.php','link',1,1,1,8)")->execute();
      }
    }catch(Exception $e){}


    // MicaERP Marketplace Core v2.0 - Empresas (capa multiempresa, por encima de Tiendas).
    // Una Empresa agrupa varias Tiendas. Permite vender el sistema a distintos clientes
    // (multi-tenant) sin tocar la lógica de Tiendas/Vendedores que ya existe.
    $pdo->exec("CREATE TABLE IF NOT EXISTS marketplace_empresas (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(160) NOT NULL,
      slug VARCHAR(170) NOT NULL UNIQUE,
      ruc VARCHAR(20) NULL,
      responsable VARCHAR(160) NULL,
      whatsapp VARCHAR(40) NULL,
      correo VARCHAR(160) NULL,
      direccion VARCHAR(220) NULL,
      logo VARCHAR(255) NULL,
      color_principal VARCHAR(20) NULL,
      color_secundario VARCHAR(20) NULL,
      plan VARCHAR(40) DEFAULT 'Estandar',
      activo TINYINT(1) DEFAULT 1,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX(activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Marketplace local: tiendas/vendedores dentro de categorías.
    $pdo->exec("CREATE TABLE IF NOT EXISTS marketplace_tiendas (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(160) NOT NULL,
      slug VARCHAR(170) NOT NULL UNIQUE,
      categoria_id INT NULL,
      responsable VARCHAR(160) NULL,
      whatsapp VARCHAR(40) NULL,
      telefono VARCHAR(40) NULL,
      correo VARCHAR(160) NULL,
      direccion VARCHAR(220) NULL,
      logo VARCHAR(255) NULL,
      descripcion TEXT NULL,
      activo TINYINT(1) DEFAULT 1,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX(categoria_id), INDEX(activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    erp_add_column($pdo,'marketplace_tiendas','empresa_id',"empresa_id INT NULL AFTER id");
    erp_add_column($pdo,'admin_usuarios','empresa_id',"empresa_id INT NULL AFTER tienda_id");

    // Empresa matriz por defecto, para no romper las tiendas ya creadas.
    try{
        $tieneEmpresa = (int)$pdo->query("SELECT COUNT(*) FROM marketplace_empresas")->fetchColumn();
        if($tieneEmpresa === 0){
            $pdo->prepare("INSERT INTO marketplace_empresas (nombre,slug,activo) VALUES (?,?,1)")->execute(['Mi Empresa','mi-empresa']);
        }
        $empresaDefaultId = (int)$pdo->query("SELECT id FROM marketplace_empresas ORDER BY id ASC LIMIT 1")->fetchColumn();
        if($empresaDefaultId > 0){
            $pdo->exec("UPDATE marketplace_tiendas SET empresa_id={$empresaDefaultId} WHERE empresa_id IS NULL");
        }
    }catch(Exception $e){}

    // Sitios públicos independientes por Empresa: configuracion y store_builder
    // dejan de ser "una sola web global" y pasan a tener una fila base
    // (empresa_id=0, la plantilla) + overrides propios por cada empresa.
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
      id INT AUTO_INCREMENT PRIMARY KEY,
      clave VARCHAR(80) NOT NULL,
      valor TEXT,
      actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS store_builder (
      id INT AUTO_INCREMENT PRIMARY KEY,
      componente VARCHAR(80) NOT NULL,
      visible TINYINT DEFAULT 1,
      texto VARCHAR(180), url VARCHAR(255),
      x INT DEFAULT 0, y INT DEFAULT 0, ancho INT DEFAULT 160, alto INT DEFAULT 50,
      color_fondo VARCHAR(20) DEFAULT '', color_texto VARCHAR(20) DEFAULT '',
      orden INT DEFAULT 0,
      actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    erp_add_column($pdo,'configuracion','empresa_id',"empresa_id INT NOT NULL DEFAULT 0 AFTER id");
    erp_add_column($pdo,'store_builder','empresa_id',"empresa_id INT NOT NULL DEFAULT 0 AFTER id");
    try{
        $idxCfg = $pdo->query("SHOW INDEX FROM configuracion WHERE Key_name='uk_config_empresa_clave'")->fetchAll();
        if(!$idxCfg){
            try{ $pdo->exec("ALTER TABLE configuracion DROP INDEX clave"); }catch(Exception $e){}
            $pdo->exec("ALTER TABLE configuracion ADD UNIQUE KEY uk_config_empresa_clave (empresa_id,clave)");
        }
    }catch(Exception $e){}
    try{
        $idxSb = $pdo->query("SHOW INDEX FROM store_builder WHERE Key_name='uk_builder_empresa_componente'")->fetchAll();
        if(!$idxSb){
            try{ $pdo->exec("ALTER TABLE store_builder DROP INDEX componente"); }catch(Exception $e){}
            $pdo->exec("ALTER TABLE store_builder ADD UNIQUE KEY uk_builder_empresa_componente (empresa_id,componente)");
        }
    }catch(Exception $e){}

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_roles (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nombre VARCHAR(80) NOT NULL UNIQUE,
      descripcion VARCHAR(255) NULL,
      activo TINYINT(1) DEFAULT 1,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_permisos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      modulo VARCHAR(80) NOT NULL,
      accion VARCHAR(30) NOT NULL,
      descripcion VARCHAR(180) NULL,
      UNIQUE KEY uk_permiso (modulo,accion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_rol_permisos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      rol_id INT NOT NULL,
      permiso_id INT NOT NULL,
      permitido TINYINT(1) DEFAULT 1,
      UNIQUE KEY uk_rol_permiso (rol_id,permiso_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");


    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_usuario_categorias (
      id INT AUTO_INCREMENT PRIMARY KEY,
      usuario_id INT NOT NULL,
      categoria_id INT NOT NULL,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uk_usuario_categoria (usuario_id,categoria_id),
      INDEX(usuario_id), INDEX(categoria_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_auditoria (
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
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX(usuario_id), INDEX(modulo), INDEX(creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_accesos (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      usuario_id INT NULL,
      usuario VARCHAR(120) NULL,
      exito TINYINT(1) DEFAULT 0,
      mensaje VARCHAR(255) NULL,
      ip VARCHAR(60) NULL,
      user_agent VARCHAR(255) NULL,
      creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX(usuario_id), INDEX(usuario), INDEX(creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $roles=[
      ['Administrador','Acceso completo al ERP.'],['Ventas','Clientes, cotizaciones y seguimiento comercial.'],
      ['Almacen','Productos, stock e inventario.'],['Compras','Proveedores, compras e inventario.'],
      ['Marketing','Contenido de tienda, carrusel, marcas y apariencia.'],['Atencion al cliente','Clientes, cotizaciones y pedidos.'],
      ['Soporte','Soporte operativo.'],
      ['Vendedor','Vendedor de una tienda específica del marketplace.'],
      ['Supervisor','Supervisa varias tiendas dentro de una misma empresa del marketplace.']
    ];
    $ins=$pdo->prepare("INSERT IGNORE INTO admin_roles (nombre,descripcion,activo) VALUES (?,?,1)");
    foreach($roles as $r){ $ins->execute($r); }

    $modulos=[
      'dashboard'=>'Dashboard','empresas'=>'Empresas','productos'=>'Productos','inventario'=>'Inventario','categorias'=>'Categorías','subcategorias'=>'Subcategorías',
      'marcas'=>'Marcas','tiendas'=>'Tiendas/Vendedores','clientes'=>'Clientes','cotizaciones'=>'Cotizaciones/Pedidos','sliders'=>'Carrusel','configuracion'=>'Configuración',
      'builder'=>'Constructor visual','topbar'=>'TopBar','bloques'=>'Bloques de inicio','campos'=>'Campos personalizados','usuarios'=>'Usuarios','roles'=>'Roles y permisos',
      'auditoria'=>'Auditoría','accesos'=>'Historial de accesos','reportes'=>'Reportes','reclamaciones'=>'Libro de reclamaciones','contenido'=>'Contenido del sitio','rrhh'=>'Trabaja con nosotros'
    ];
    $acciones=['ver'=>'Ver','crear'=>'Crear','editar'=>'Editar','eliminar'=>'Eliminar','exportar'=>'Exportar'];
    $insP=$pdo->prepare("INSERT IGNORE INTO admin_permisos (modulo,accion,descripcion) VALUES (?,?,?)");
    foreach($modulos as $m=>$nombre){ foreach($acciones as $a=>$nom){ $insP->execute([$m,$a,"$nom $nombre"]); } }

    $rolesDb=[]; foreach($pdo->query("SELECT id,nombre FROM admin_roles")->fetchAll(PDO::FETCH_ASSOC) as $r){ $rolesDb[$r['nombre']]=$r['id']; }
    $perms=$pdo->query("SELECT id,modulo,accion FROM admin_permisos")->fetchAll(PDO::FETCH_ASSOC);
    $grant=$pdo->prepare("INSERT IGNORE INTO admin_rol_permisos (rol_id,permiso_id,permitido) VALUES (?,?,1)");
    $allow=[
      'Administrador'=>['*'],
      'Ventas'=>['dashboard','productos','clientes','cotizaciones'],
      'Vendedor'=>['dashboard','productos','clientes','cotizaciones'],
      'Supervisor'=>['dashboard','productos','clientes','cotizaciones','tiendas','reportes'],
      'Almacen'=>['dashboard','productos','inventario'],
      'Compras'=>['dashboard','productos','inventario','reportes'],
      'Marketing'=>['dashboard','productos','categorias','subcategorias','marcas','tiendas','sliders','builder','topbar','bloques','campos','contenido','rrhh'],
      'Atencion al cliente'=>['dashboard','clientes','cotizaciones','reclamaciones','rrhh'],
      'Soporte'=>['dashboard','clientes','cotizaciones','productos','inventario','reclamaciones']
    ];
    foreach($allow as $rol=>$mods){ if(empty($rolesDb[$rol])) continue; foreach($perms as $p){ if(in_array('*',$mods,true) || in_array($p['modulo'],$mods,true)){ $grant->execute([$rolesDb[$rol],$p['id']]); } } }

    // Enlazar usuarios existentes con su rol por nombre.
    try{
      $pdo->exec("UPDATE admin_usuarios u INNER JOIN admin_roles r ON r.nombre=u.rol SET u.rol_id=r.id WHERE u.rol_id IS NULL");
    }catch(Exception $e){}
}

function erp_registrar_acceso(PDO $pdo, ?int $uid, string $usuario, bool $exito, string $mensaje): void{
    try{ $st=$pdo->prepare("INSERT INTO admin_accesos (usuario_id,usuario,exito,mensaje,ip,user_agent) VALUES (?,?,?,?,?,?)"); $st->execute([$uid,$usuario,$exito?1:0,$mensaje,erp_client_ip(),substr($_SERVER['HTTP_USER_AGENT']??'',0,250)]); }catch(Exception $e){}
}
function erp_auditoria(PDO $pdo, string $modulo, string $accion, string $descripcion='', string $tabla='', $refId=null): void{
    try{
        $st=$pdo->prepare("INSERT INTO admin_auditoria (usuario_id,usuario_nombre,modulo,accion,descripcion,referencia_tabla,referencia_id,ip,user_agent) VALUES (?,?,?,?,?,?,?,?,?)");
        $st->execute([$_SESSION['admin_id']??null,$_SESSION['admin_nombre']??'', $modulo,$accion,$descripcion,$tabla,(string)$refId,erp_client_ip(),substr($_SERVER['HTTP_USER_AGENT']??'',0,250)]);
    }catch(Exception $e){}
}
function erp_tiene_permiso(PDO $pdo, string $modulo, string $accion='ver'): bool{
    if(empty($_SESSION['admin_id'])) return false;
    if(($_SESSION['admin_rol'] ?? '') === 'Administrador') return true;
    $rolId=(int)($_SESSION['admin_rol_id'] ?? 0);
    if($rolId<=0) return false;
    try{
      $st=$pdo->prepare("SELECT rp.permitido FROM admin_rol_permisos rp INNER JOIN admin_permisos p ON p.id=rp.permiso_id WHERE rp.rol_id=? AND p.modulo=? AND p.accion=? LIMIT 1");
      $st->execute([$rolId,$modulo,$accion]); return (bool)$st->fetchColumn();
    }catch(Exception $e){ return false; }
}
function erp_requerir_permiso(PDO $pdo, string $modulo, string $accion='ver'): void{
    if(!erp_tiene_permiso($pdo,$modulo,$accion)){ http_response_code(403); die('Acceso restringido. No tienes permiso para esta opción.'); }
}


/**
 * DATA SCOPE / ALCANCE DE DATOS
 * - Administrador ve todo.
 * - Usuario no administrador solo ve categorías asignadas.
 * - Si no tiene categorías asignadas, no ve datos comerciales por seguridad.
 */
function erp_es_admin_global(): bool{
    return ($_SESSION['admin_rol'] ?? '') === 'Administrador';
}
function erp_categorias_permitidas(PDO $pdo, ?int $usuarioId=null): ?array{
    $usuarioId = $usuarioId ?: (int)($_SESSION['admin_id'] ?? 0);
    if($usuarioId <= 0) return [];
    if(erp_es_admin_global()) return null; // null = sin filtro, acceso total
    try{
        $st=$pdo->prepare("SELECT categoria_id FROM admin_usuario_categorias WHERE usuario_id=? ORDER BY categoria_id ASC");
        $st->execute([$usuarioId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }catch(Exception $e){ return []; }
}
function erp_categorias_permitidas_nombres(PDO $pdo, int $usuarioId): array{
    try{
        $st=$pdo->prepare("SELECT c.nombre FROM admin_usuario_categorias uc INNER JOIN categorias c ON c.id=uc.categoria_id WHERE uc.usuario_id=? ORDER BY c.nombre ASC");
        $st->execute([$usuarioId]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }catch(Exception $e){ return []; }
}
function erp_scope_sql_producto(PDO $pdo, string $campo='p.categoria_id'): string{
    $ids = erp_categorias_permitidas($pdo);
    if($ids === null) return ''; // admin global
    if(count($ids) === 0) return ' AND 1=0 ';
    return ' AND '.$campo.' IN ('.implode(',', array_map('intval',$ids)).') ';
}
function erp_scope_sql_cotizacion(PDO $pdo, string $cotAlias='co'): string{
    $ids = erp_categorias_permitidas($pdo);
    if($ids === null) return '';
    if(count($ids) === 0) return ' AND 1=0 ';
    $lista = implode(',', array_map('intval',$ids));
    return " AND EXISTS (SELECT 1 FROM cotizacion_detalle cd_scope INNER JOIN productos p_scope ON p_scope.id=cd_scope.producto_id WHERE cd_scope.cotizacion_id={$cotAlias}.id AND p_scope.categoria_id IN ($lista)) ";
}
function erp_producto_en_scope(PDO $pdo, int $productoId): bool{
    if(erp_es_admin_global()) return true;
    $ids = erp_categorias_permitidas($pdo);
    if(!$ids) return false;
    $st=$pdo->prepare('SELECT categoria_id FROM productos WHERE id=? LIMIT 1');
    $st->execute([$productoId]);
    return in_array((int)$st->fetchColumn(), $ids, true);
}
function erp_cotizacion_en_scope(PDO $pdo, int $cotizacionId): bool{
    if(erp_es_admin_global()) return true;
    $ids = erp_categorias_permitidas($pdo);
    if(!$ids) return false;
    $lista = implode(',', array_map('intval',$ids));
    $st=$pdo->prepare("SELECT COUNT(*) FROM cotizacion_detalle cd INNER JOIN productos p ON p.id=cd.producto_id WHERE cd.cotizacion_id=? AND p.categoria_id IN ($lista)");
    $st->execute([$cotizacionId]);
    return (int)$st->fetchColumn() > 0;
}
function erp_scope_resumen(PDO $pdo): string{
    $ids = erp_categorias_permitidas($pdo);
    if($ids === null) return 'Acceso a todas las categorías';
    if(!$ids) return 'Sin categorías asignadas';
    try{
        $lista = implode(',', array_map('intval',$ids));
        $n=$pdo->query("SELECT GROUP_CONCAT(nombre ORDER BY nombre SEPARATOR ', ') FROM categorias WHERE id IN ($lista)")->fetchColumn();
        return $n ?: 'Sin categorías asignadas';
    }catch(Exception $e){ return 'Categorías restringidas'; }
}


function erp_tienda_id_actual(): int{
    return (int)($_SESSION['admin_tienda_id'] ?? 0);
}
function erp_empresa_id_actual(): int{
    return (int)($_SESSION['admin_empresa_id'] ?? 0);
}
function erp_es_vendedor_tienda(): bool{
    return ($_SESSION['admin_rol'] ?? '') === 'Vendedor' && erp_tienda_id_actual() > 0;
}
function erp_es_supervisor_empresa(): bool{
    return ($_SESSION['admin_rol'] ?? '') === 'Supervisor' && erp_empresa_id_actual() > 0;
}
// Tiendas visibles para el usuario actual dentro de su empresa (Supervisor).
function erp_tiendas_de_mi_empresa(PDO $pdo): array{
    if(!erp_es_supervisor_empresa()) return [];
    $st=$pdo->prepare("SELECT id FROM marketplace_tiendas WHERE empresa_id=?");
    $st->execute([erp_empresa_id_actual()]);
    return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}
function erp_scope_sql_tienda_producto(PDO $pdo, string $productoAlias='p'): string{
    if(erp_es_admin_global()) return '';
    if(erp_es_vendedor_tienda()) return ' AND '.$productoAlias.'.tienda_id='.(int)erp_tienda_id_actual().' ';
    return erp_scope_sql_producto($pdo, $productoAlias.'.categoria_id');
}
function erp_scope_sql_tienda_cotizacion(PDO $pdo, string $cotAlias='co'): string{
    if(erp_es_admin_global()) return '';
    if(erp_es_vendedor_tienda()){
        $tid=(int)erp_tienda_id_actual();
        return " AND EXISTS (SELECT 1 FROM cotizacion_detalle cd_tienda INNER JOIN productos p_tienda ON p_tienda.id=cd_tienda.producto_id WHERE cd_tienda.cotizacion_id={$cotAlias}.id AND p_tienda.tienda_id={$tid}) ";
    }
    return erp_scope_sql_cotizacion($pdo, $cotAlias);
}
function erp_scope_resumen_comercial(PDO $pdo): string{
    if(erp_es_vendedor_tienda()){
        try{
            $st=$pdo->prepare('SELECT nombre FROM marketplace_tiendas WHERE id=? LIMIT 1');
            $st->execute([erp_tienda_id_actual()]);
            $n=$st->fetchColumn();
            return $n ? ('Tienda: '.$n) : 'Tienda asignada';
        }catch(Exception $e){ return 'Tienda asignada'; }
    }
    return erp_scope_resumen($pdo);
}

// ==================== MicaERP Marketplace v2.0 - Contexto de Empresa ====================
// Detecta qué empresa está viendo el visitante (por slug de carpeta: /la-chacra/)
// y arma su configuración: plantilla base (empresa_id=0) + overrides propios.

function erp_empresa_por_slug(PDO $pdo, string $slug): ?array{
    if($slug==='') return null;
    $st=$pdo->prepare("SELECT * FROM marketplace_empresas WHERE slug=? AND activo=1 LIMIT 1");
    $st->execute([$slug]);
    $r=$st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function erp_config_empresa(PDO $pdo, int $empresaId=0): array{
    $cfg=[];
    foreach($pdo->query("SELECT clave,valor FROM configuracion WHERE empresa_id=0") as $r){ $cfg[$r['clave']]=$r['valor']; }
    if($empresaId>0){
        $st=$pdo->prepare("SELECT clave,valor FROM configuracion WHERE empresa_id=?");
        $st->execute([$empresaId]);
        foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){ if($r['valor']!=='' && $r['valor']!==null) $cfg[$r['clave']]=$r['valor']; }
    }
    return $cfg;
}

// Guarda una clave de configuración para una empresa concreta (o la plantilla si empresaId=0).
function erp_set_config_empresa(PDO $pdo, int $empresaId, string $clave, string $valor): void{
    $st=$pdo->prepare("SELECT id FROM configuracion WHERE empresa_id=? AND clave=? LIMIT 1");
    $st->execute([$empresaId,$clave]);
    if($id=$st->fetchColumn()){
        $pdo->prepare("UPDATE configuracion SET valor=? WHERE id=?")->execute([$valor,$id]);
    }else{
        $pdo->prepare("INSERT INTO configuracion (empresa_id,clave,valor) VALUES (?,?,?)")->execute([$empresaId,$clave,$valor]);
    }
}

// Al crear una empresa nueva, le clonamos la plantilla base para que arranque
// con un sitio funcional y editable de inmediato (no en blanco).
function erp_clonar_sitio_para_empresa(PDO $pdo, int $empresaId): void{
    if($empresaId<=0) return;
    $st=$pdo->prepare("SELECT COUNT(*) FROM configuracion WHERE empresa_id=?"); $st->execute([$empresaId]);
    if((int)$st->fetchColumn() > 0) return; // ya tiene su propia configuración, no pisar nada
    foreach($pdo->query("SELECT clave,valor FROM configuracion WHERE empresa_id=0") as $r){
        $pdo->prepare("INSERT IGNORE INTO configuracion (empresa_id,clave,valor) VALUES (?,?,?)")->execute([$empresaId,$r['clave'],$r['valor']]);
    }
    $st2=$pdo->prepare("SELECT COUNT(*) FROM store_builder WHERE empresa_id=?"); $st2->execute([$empresaId]);
    if((int)$st2->fetchColumn() == 0){
        foreach($pdo->query("SELECT componente,visible,texto,url,x,y,ancho,alto,color_fondo,color_texto,orden FROM store_builder WHERE empresa_id=0") as $r){
            $pdo->prepare("INSERT IGNORE INTO store_builder (empresa_id,componente,visible,texto,url,x,y,ancho,alto,color_fondo,color_texto,orden) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$empresaId,$r['componente'],$r['visible'],$r['texto'],$r['url'],$r['x'],$r['y'],$r['ancho'],$r['alto'],$r['color_fondo'],$r['color_texto'],$r['orden']]);
        }
    }
}

// Construye un enlace interno que conserva el contexto de empresa (?empresa=slug)
// sin importar si el .htaccess/mod_rewrite está activo en el servidor — por eso
// usamos el parámetro de consulta como forma confiable de "quedarse" en la empresa.
function erp_url_empresa(?string $slugEmpresa, string $ruta=''): string{
    if(!$slugEmpresa) return $ruta;
    $hash = '';
    if(($pos = strpos($ruta, '#')) !== false){
        $hash = substr($ruta, $pos);
        $ruta = substr($ruta, 0, $pos);
    }
    $sep = (strpos($ruta, '?') !== false) ? '&' : '?';
    return $ruta . $sep . 'empresa=' . urlencode($slugEmpresa) . $hash;
}

?>

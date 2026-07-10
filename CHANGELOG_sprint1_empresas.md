# Sprint 1 — Marketplace Core: capa Empresa (multiempresa)

## Resumen
Se agregó la capa "Empresa" por encima de "Tiendas" (que ya existía y funcionaba,
pero no estaba conectada al menú ni tenía dueño multiempresa). Con esto queda
armada la jerarquía real:

    Empresa → Tiendas → Vendedores → Productos

## Auditoría previa (por qué no se reconstruyó nada desde cero)
Antes de tocar código se verificó qué ya existía en el proyecto:
- `marketplace_tiendas`, `admin/tiendas.php`, `admin/tienda_form.php`: YA EXISTÍAN
  y funcionan (CRUD completo, logo, WhatsApp propio, categoría).
- Rol "Vendedor" + `admin_usuarios.tienda_id` + scoping (`erp_es_vendedor_tienda()`):
  YA EXISTÍA en `config/erp_core.php`.
- Roles/permisos granulares (`admin_roles`, `admin_permisos`, `admin_rol_permisos`),
  auditoría y bloqueo por intentos: YA EXISTÍAN.
- Bug encontrado: el módulo "Tiendas" no tenía enlace en el menú lateral del
  admin (`admin/layout.php`) — se corrigió.

## Archivos nuevos
- `admin/empresas.php` — listado de empresas (buscador, tiendas/usuarios por empresa).
- `admin/empresa_form.php` — alta/edición de empresa (nombre, RUC, logo, colores, plan).

## Archivos modificados
- `config/erp_core.php`
  - Tabla `marketplace_empresas` (idempotente, vía `erp_ensure_core()`).
  - Columna `marketplace_tiendas.empresa_id` y `admin_usuarios.empresa_id`.
  - Empresa matriz por defecto ("Mi Empresa") asignada automáticamente a las
    tiendas existentes, para no romper datos actuales.
  - Nuevo rol **Supervisor** (ve todas las tiendas de su empresa).
  - Nuevo módulo de permisos `empresas`.
  - Helpers: `erp_empresa_id_actual()`, `erp_es_supervisor_empresa()`,
    `erp_tiendas_de_mi_empresa()`.
- `admin/login.php` — guarda `empresa_id` en sesión al iniciar sesión.
- `admin/tiendas.php` — muestra y filtra por empresa (`?empresa_id=`).
- `admin/tienda_form.php` — selector de empresa al crear/editar tienda.
- `admin/usuario_form.php` — selector de empresa (solo visible para rol Supervisor),
  guardado condicional igual que ya se hacía con `tienda_id` para Vendedor.
- `admin/layout.php` — se agregó "🏢 Empresas" y se corrigió el enlace faltante
  de "🏬 Tiendas / Vendedores" en el menú lateral.

## Cómo aplicar los cambios en tu entorno
1. Reemplaza los archivos en tu proyecto Laragon por los de este ZIP (o usa git,
   ver más abajo).
2. Abre en el navegador: `http://localhost/micastore/admin/migrar_erp_core.php`
   Esto ejecuta `erp_ensure_core()` y crea/actualiza las tablas de forma segura
   (no borra nada existente). Verás el mensaje de instalación correcta.
3. **Importante**: borra `admin/migrar_erp_core.php` después de correrlo, como
   ya indica el propio archivo (crea/resetea el usuario admin).

## Checklist de pruebas
- [ ] Entrar a `/admin/empresas.php` → debe aparecer "Mi Empresa" ya creada.
- [ ] Crear una empresa nueva (ej. "CC Mercado La Chacra") con logo y colores.
- [ ] Entrar a `/admin/tiendas.php` → las tiendas existentes deben mostrar
      "Mi Empresa" en la columna Empresa (no vacío).
- [ ] Editar una tienda y cambiarla a la empresa nueva.
- [ ] Ir a `/admin/tiendas.php?empresa_id=X` y confirmar que filtra correctamente.
- [ ] Crear un usuario con rol **Supervisor** y asignarle una empresa → el campo
      "Empresa asignada" debe aparecer/ocultarse según el rol seleccionado.
- [ ] Confirmar que un usuario con rol **Vendedor** sigue funcionando igual que
      antes (no se rompió el scoping existente).
- [ ] Revisar `/admin/auditoria.php` → deben aparecer los registros de
      creación/edición de empresas y tiendas.

## Pendiente para el siguiente sprint (según roadmap del usuario)
- Conectar `pedidos_tracking` a `tienda_id`/vendedor.
- Dashboard por rol (Administrador / Supervisor / Vendedor).
- Mini-página pública individual por tienda (hoy solo existe el listado
  `tiendas.php`, falta el detalle tipo `tienda.php?slug=jaimito-tech`).

## Mensaje de commit sugerido
```
feat(marketplace): agregar capa Empresa (multiempresa) sobre Tiendas

- Nueva tabla marketplace_empresas + empresa_id en tiendas y usuarios
- Nuevo rol Supervisor con scope a nivel empresa
- CRUD admin/empresas.php y admin/empresa_form.php
- Conectar tiendas.php y tienda_form.php a empresa
- Fix: enlace faltante de Tiendas en el menú del admin
- Migración automática e idempotente vía erp_ensure_core()
```

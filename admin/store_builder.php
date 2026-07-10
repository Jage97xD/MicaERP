<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

function tienda_url($path='tienda_visual_v3.php'){
    return "/micastore/" . ltrim($path, '/');
}

function asegurarStoreBuilder(PDO $pdo){
    $pdo->exec("CREATE TABLE IF NOT EXISTS store_builder (
        id INT AUTO_INCREMENT PRIMARY KEY,
        componente VARCHAR(80) NOT NULL UNIQUE,
        visible TINYINT(1) DEFAULT 1,
        texto VARCHAR(180) NULL,
        url VARCHAR(255) NULL,
        x INT DEFAULT 0,
        y INT DEFAULT 0,
        ancho INT DEFAULT 160,
        alto INT DEFAULT 50,
        color_fondo VARCHAR(30) NULL,
        color_texto VARCHAR(30) NULL,
        orden INT DEFAULT 0,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $defaults = [
        ['logo',1,'Mica Store','tienda_visual_v3.php',0,32,220,80,'#ffffff','#111827',1],
        ['buscador',1,'Buscar productos, marcas y más...','tienda_visual_v3.php',0,40,605,62,'#ffffff','#111827',2],
        ['login',1,'Iniciar sesión','cliente/cliente_login.php',0,35,160,48,'#ffffff','#111827',3],
        ['cotizacion',1,'Cotización','cotizacion_mysql.php',0,35,170,48,'#ffffff','#111827',4],
        ['whatsapp',1,'WhatsApp','https://wa.me/51920137707',0,35,140,48,'#22c55e','#ffffff',5]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO store_builder (componente,visible,texto,url,x,y,ancho,alto,color_fondo,color_texto,orden) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    foreach($defaults as $d){ $stmt->execute($d); }
}

function guardarConfig(PDO $pdo, $clave, $valor){
    try{
        $stmt = $pdo->prepare("INSERT INTO configuracion (clave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
        $stmt->execute([$clave,$valor]);
    }catch(Exception $e){}
}

function leerConfig(PDO $pdo){
    $config = [];
    try{
        $stmt = $pdo->query("SELECT clave, valor FROM configuracion");
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){ $config[$r['clave']] = $r['valor']; }
    }catch(Exception $e){}
    return $config;
}

asegurarStoreBuilder($pdo);
$mensaje = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    try{
        if(($_POST['accion'] ?? '') === 'reset_header'){
            $pdo->exec("UPDATE store_builder SET visible=1 WHERE componente IN ('logo','buscador','login','cotizacion','whatsapp')");
            $defaults = [
                'logo'=>['Mica Store','tienda_visual_v3.php','#ffffff','#111827',1],
                'buscador'=>['Buscar productos, marcas y más...','tienda_visual_v3.php','#ffffff','#111827',2],
                'login'=>['Iniciar sesión','cliente/cliente_login.php','#ffffff','#111827',3],
                'cotizacion'=>['Cotización','cotizacion_mysql.php','#ffffff','#111827',4],
                'whatsapp'=>['WhatsApp','https://wa.me/51920137707','#22c55e','#ffffff',5]
            ];
            $stmt = $pdo->prepare("UPDATE store_builder SET texto=?, url=?, color_fondo=?, color_texto=?, orden=? WHERE componente=?");
            foreach($defaults as $comp=>$d){ $stmt->execute([$d[0],$d[1],$d[2],$d[3],$d[4],$comp]); }
            $mensaje = 'Diseño del header restaurado al estándar recomendado.';
        }else{
            foreach($_POST['componentes'] ?? [] as $id => $data){
                $visible = isset($data['visible']) ? 1 : 0;
                $texto = trim($data['texto'] ?? '');
                $url = trim($data['url'] ?? '');
                $colorFondo = trim($data['color_fondo'] ?? '');
                $colorTexto = trim($data['color_texto'] ?? '');
                $orden = (int)($data['orden'] ?? 0);

                $stmt = $pdo->prepare("UPDATE store_builder SET visible=?, texto=?, url=?, color_fondo=?, color_texto=?, orden=? WHERE id=?");
                $stmt->execute([$visible,$texto,$url,$colorFondo,$colorTexto,$orden,(int)$id]);
            }

            guardarConfig($pdo, 'header_estilo', $_POST['header_estilo'] ?? 'normal');
            guardarConfig($pdo, 'header_menu_sticky', isset($_POST['header_menu_sticky']) ? '1' : '0');
            guardarConfig($pdo, 'header_mostrar_topbar', isset($_POST['header_mostrar_topbar']) ? '1' : '0');
            guardarConfig($pdo, 'home_mostrar_contacto', isset($_POST['home_mostrar_contacto']) ? '1' : '0');

            if(function_exists('erp_auditoria')){
                erp_auditoria($pdo, 'builder', 'editar', 'Actualizó el panel de diseño de tienda', 'store_builder', 'header');
            }

            $mensaje = 'Diseño de tienda guardado correctamente.';
        }
    }catch(Exception $e){
        $error = $e->getMessage();
    }
}

$componentes = $pdo->query("SELECT * FROM store_builder WHERE componente <> 'menu' ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$config = leerConfig($pdo);

$componentLabels = [
    'logo'=>['titulo'=>'Logo','icono'=>'🔷','ayuda'=>'Marca principal que lleva al inicio.'],
    'buscador'=>['titulo'=>'Buscador','icono'=>'🔎','ayuda'=>'Búsqueda global de productos.'],
    'login'=>['titulo'=>'Login / Cuenta','icono'=>'👤','ayuda'=>'Acceso del cliente a su cuenta.'],
    'cotizacion'=>['titulo'=>'Cotización','icono'=>'🛒','ayuda'=>'Carrito/cotización del cliente.'],
    'whatsapp'=>['titulo'=>'WhatsApp','icono'=>'💬','ayuda'=>'Atención rápida por WhatsApp.']
];

admin_header("Diseño de tienda", "builder");
?>

<style>
.notice-ok{background:#dcfce7;color:#166534;border:1px solid #86efac;padding:13px;border-radius:12px;margin-bottom:16px;font-weight:900}.notice-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:13px;border-radius:12px;margin-bottom:16px;font-weight:900}.design-grid{display:grid;grid-template-columns:1.5fr .9fr;gap:22px}.design-card{background:white;border:1px solid #e5e7eb;border-radius:20px;padding:22px;box-shadow:0 8px 24px rgba(15,23,42,.08);margin-bottom:20px}.design-head{display:flex;justify-content:space-between;gap:15px;align-items:flex-start;margin-bottom:16px}.muted{color:#64748b;font-size:14px}.design-options{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}.option-box{border:1px solid #e5e7eb;background:#f8fafc;border-radius:16px;padding:16px}.option-box label{font-weight:900;display:block;margin-bottom:8px}.option-box input,.option-box select{width:100%;padding:12px;border:1px solid #d8dee9;border-radius:12px}.component-row{display:grid;grid-template-columns:46px 1fr 110px;gap:14px;align-items:center;border:1px solid #e5e7eb;border-radius:16px;padding:15px;margin-bottom:12px;background:#fff}.component-ico{width:46px;height:46px;border-radius:14px;background:#eff6ff;color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-size:22px}.component-row h4{margin:0 0 4px}.component-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px}.component-edit{grid-column:1/-1;display:grid;grid-template-columns:1fr 1.2fr 110px 110px 80px;gap:10px;background:#f8fafc;border-radius:14px;padding:12px}.component-edit input{width:100%;padding:11px;border:1px solid #d8dee9;border-radius:10px}.switch{display:flex;align-items:center;gap:8px;font-weight:900}.switch input{width:18px;height:18px}.preview-header{border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;background:#fff}.preview-topbar{background:#07162f;color:white;font-weight:900;font-size:12px;display:flex;justify-content:space-between;padding:8px 18px}.preview-main{display:flex;align-items:center;gap:16px;padding:18px;flex-wrap:wrap}.preview-logo{display:flex;align-items:center;gap:10px;font-weight:900;font-size:20px}.preview-logo span{width:42px;height:42px;border-radius:12px;background:#008ee6;color:#fff;display:flex;align-items:center;justify-content:center}.preview-search{flex:1;min-width:240px;border:1px solid #d8dee9;border-radius:12px;height:46px;display:flex;align-items:center;justify-content:space-between;padding-left:16px;color:#64748b;overflow:hidden}.preview-search button{height:100%;width:58px;border:0;background:#0057d9;color:white}.preview-pill{background:white;border:1px solid #e5e7eb;border-radius:12px;height:44px;padding:0 14px;display:flex;align-items:center;font-weight:900;box-shadow:0 8px 22px rgba(15,23,42,.08)}.preview-wa{background:#22c55e;color:white}.quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.quick-link{display:block;border:1px solid #e5e7eb;background:#f8fafc;border-radius:14px;padding:14px;font-weight:900;color:#0f172a;text-decoration:none}.quick-link span{display:block;color:#64748b;font-weight:700;font-size:13px;margin-top:5px}.module-map{display:grid;gap:10px}.module-map div{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:12px}.danger-zone{border-color:#fed7aa;background:#fff7ed}.btn-line{display:flex;gap:10px;flex-wrap:wrap}.btn.outline{background:white;color:#111827;border:1px solid #d8dee9}.small-note{font-size:12px;color:#64748b;margin-top:6px}@media(max-width:1100px){.design-grid{grid-template-columns:1fr}.component-edit{grid-template-columns:1fr}.quick-grid,.design-options{grid-template-columns:1fr}}.component-edit input[type="color"]{width:52px;height:44px;padding:2px;border:2px solid #e5e7eb;border-radius:10px;cursor:pointer;flex:0 0 auto}
</style>

<?php if($mensaje): ?><div class="notice-ok"><?= h($mensaje) ?></div><?php endif; ?>
<?php if($error): ?><div class="notice-error"><?= h($error) ?></div><?php endif; ?>

<div class="design-grid">
    <div>
        <form method="POST">
            <div class="design-card">
                <div class="design-head">
                    <div>
                        <h3>Header de tienda</h3>
                        <p class="muted">Este panel reemplaza al antiguo editor por coordenadas X/Y. Ahora el header usa una estructura flexible y responsive para evitar deformaciones.</p>
                    </div>
                    <button class="btn green" type="submit">Guardar diseño</button>
                </div>

                <div class="preview-header">
                    <?php if(($config['header_mostrar_topbar'] ?? '1') === '1'): ?>
                        <div class="preview-topbar"><span>🚚 Envíos · 🛡 Garantía · 📍 Ubicación</span><span>Facebook · Instagram · TikTok</span></div>
                    <?php endif; ?>
                    <div class="preview-main">
                        <?php foreach($componentes as $c): ?>
                            <?php if(empty($c['visible'])) continue; ?>
                            <?php $comp = $c['componente']; $txt = $c['texto'] ?: ucfirst($comp); ?>
                            <?php if($comp === 'logo'): ?>
                                <div class="preview-logo"><span>M</span><strong><?= h($txt) ?></strong></div>
                            <?php elseif($comp === 'buscador'): ?>
                                <div class="preview-search"><span><?= h($txt) ?></span><button>🔎</button></div>
                            <?php elseif($comp === 'whatsapp'): ?>
                                <div class="preview-pill preview-wa">💬 <?= h($txt) ?></div>
                            <?php elseif($comp === 'cotizacion'): ?>
                                <div class="preview-pill">🛒 <?= h($txt) ?> · 0</div>
                            <?php else: ?>
                                <div class="preview-pill">👤 <?= h($txt) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <p class="small-note">La vista previa es referencial. Para ver la tienda real usa el botón “Abrir tienda real”.</p>
            </div>

            <div class="design-card">
                <div class="design-head">
                    <div>
                        <h3>Componentes visibles</h3>
                        <p class="muted">Activa, desactiva y edita los textos del header. Para enlaces complejos usa módulos especializados como TopBar o Menú público.</p>
                    </div>
                </div>

                <?php foreach($componentes as $c): ?>
                    <?php $meta = $componentLabels[$c['componente']] ?? ['titulo'=>ucfirst($c['componente']),'icono'=>'⚙️','ayuda'=>'Componente del header.']; ?>
                    <div class="component-row">
                        <div class="component-ico"><?= h($meta['icono']) ?></div>
                        <div>
                            <h4><?= h($meta['titulo']) ?></h4>
                            <p class="muted"><?= h($meta['ayuda']) ?></p>
                        </div>
                        <div class="component-actions">
                            <label class="switch"><input type="checkbox" name="componentes[<?= (int)$c['id'] ?>][visible]" <?= !empty($c['visible'])?'checked':'' ?>> Visible</label>
                        </div>
                        <div class="component-edit">
                            <input name="componentes[<?= (int)$c['id'] ?>][texto]" value="<?= h($c['texto']) ?>" placeholder="Texto">
                            <input name="componentes[<?= (int)$c['id'] ?>][url]" value="<?= h($c['url']) ?>" placeholder="URL o enlace">
                            <input type="color" name="componentes[<?= (int)$c['id'] ?>][color_fondo]" value="<?= h($c['color_fondo'] ?: '#ffffff') ?>" title="Color fondo">
                            <input type="color" name="componentes[<?= (int)$c['id'] ?>][color_texto]" value="<?= h($c['color_texto'] ?: '#111827') ?>" title="Color texto">
                            <input name="componentes[<?= (int)$c['id'] ?>][orden]" value="<?= (int)$c['orden'] ?>" title="Orden">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="design-card">
                <div class="design-head"><div><h3>Opciones globales</h3><p class="muted">Ajustes simples para mantener el diseño estable en escritorio y móvil.</p></div></div>
                <div class="design-options">
                    <div class="option-box">
                        <label>Estilo del header</label>
                        <select name="header_estilo">
                            <option value="compacto" <?= ($config['header_estilo'] ?? 'normal')==='compacto'?'selected':'' ?>>Compacto</option>
                            <option value="normal" <?= ($config['header_estilo'] ?? 'normal')==='normal'?'selected':'' ?>>Normal</option>
                            <option value="amplio" <?= ($config['header_estilo'] ?? 'normal')==='amplio'?'selected':'' ?>>Amplio</option>
                        </select>
                    </div>
                    <div class="option-box">
                        <label>Visibilidad</label>
                        <label class="switch"><input type="checkbox" name="header_mostrar_topbar" <?= ($config['header_mostrar_topbar'] ?? '1')==='1'?'checked':'' ?>> Mostrar TopBar</label>
                        <label class="switch"><input type="checkbox" name="header_menu_sticky" <?= ($config['header_menu_sticky'] ?? '1')==='1'?'checked':'' ?>> Menú fijo al hacer scroll</label>
                        <label class="switch"><input type="checkbox" name="home_mostrar_contacto" <?= ($config['home_mostrar_contacto'] ?? '1')==='1'?'checked':'' ?>> Mostrar contacto en inicio</label>
                    </div>
                </div>
            </div>
        </form>

        <div class="design-card danger-zone">
            <div class="design-head">
                <div>
                    <h3>Mantenimiento del diseño</h3>
                    <p class="muted">Usa esta opción si el header se deforma por configuraciones anteriores.</p>
                </div>
            </div>
            <form method="POST" onsubmit="return confirm('¿Restaurar el diseño recomendado del header?');">
                <input type="hidden" name="accion" value="reset_header">
                <button class="btn outline" type="submit">Restaurar header recomendado</button>
            </form>
        </div>
    </div>

    <aside>
        <div class="design-card">
            <h3>Accesos rápidos</h3>
            <p class="muted">El diseño se administra por módulos. Esto evita romper el responsive.</p>
            <div class="quick-grid">
                <a class="quick-link" href="topbar.php">📌 TopBar<span>Textos superiores, redes y ubicación con Google Maps.</span></a>
                <a class="quick-link" href="header_menu.php">🧭 Menú público<span>Inicio, productos, categorías, marcas y pedidos.</span></a>
                <a class="quick-link" href="configuracion.php">⚙️ Configuración<span>Logo, colores, WhatsApp, correo y dirección.</span></a>
                <a class="quick-link" href="home_bloques.php">🧩 Bloques inicio<span>Banners, textos y secciones comerciales.</span></a>
                <a class="quick-link" href="sliders.php">🖼️ Carrusel<span>Slides, botones y enlaces automáticos.</span></a>
                <a class="quick-link" href="../tienda_visual_v3.php" target="_blank">🌐 Abrir tienda real<span>Abre la tienda en otra pestaña.</span></a>
            </div>
        </div>

        <div class="design-card">
            <h3>Qué pasó con el editor X/Y</h3>
            <div class="module-map">
                <div><strong>Antes:</strong><br><span class="muted">Mover cajas manualmente con X/Y. Esto podía deformar el header en 100%, 50% o móvil.</span></div>
                <div><strong>Ahora:</strong><br><span class="muted">Estructura flexible, componentes activables y módulos especializados.</span></div>
                <div><strong>Resultado:</strong><br><span class="muted">Menos errores visuales, más fácil de administrar y preparado para crecer.</span></div>
            </div>
        </div>

        <div class="design-card">
            <h3>Recomendación ERP</h3>
            <p class="muted">Mantén este módulo para decisiones generales de diseño. Para contenido específico usa los módulos dedicados: TopBar, Menú público, Carrusel, Bloques y Configuración.</p>
        </div>
    </aside>
</div>

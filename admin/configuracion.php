<?php
require_once "../config/db.php";
require_once "layout.php";
requerirPermiso('configuracion','ver');

// Qué empresa está editando el admin. 0 = plantilla base (comportamiento clásico,
// como antes de tener multiempresa). Se recuerda en sesión para no perderla entre guardados.
if(isset($_GET['empresa_id'])){ $_SESSION['config_empresa_id'] = (int)$_GET['empresa_id']; }
$empresaEditando = (int)($_SESSION['config_empresa_id'] ?? 0);
$empresasDisponibles = $pdo->query("SELECT id,nombre FROM marketplace_empresas WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

function setConfig($pdo, $clave, $valor, $empresaId=0){
    erp_set_config_empresa($pdo, $empresaId, $clave, $valor);
}

$mensaje = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $campos = [
        'nombre_comercial','razon_social','ruc','direccion','telefono','whatsapp','correo',
        'facebook','instagram','tiktok','youtube','maps_url','horario','moneda','igv',
        'mision','vision','valores','publicidad_web_texto','publicidad_web_whatsapp','publicidad_web_firma',
        'color_principal','color_secundario',
        'apariencia_fondo_tipo','apariencia_fondo_size','apariencia_fondo_posicion',
        'apariencia_fondo_repetir','apariencia_fondo_opacidad',
        'header_logo_posicion','header_orden',
        'preview_logo_x','preview_logo_y','preview_nombre_x','preview_nombre_y','preview_info_x','preview_info_y'
    ];

    foreach($campos as $campo){
        setConfig($pdo, $campo, $_POST[$campo] ?? '', $empresaEditando);
    }

    $checks = ['header_mostrar_topbar','header_mostrar_redes','header_mostrar_buscador','header_mostrar_login','header_mostrar_cotizacion','publicidad_web_activa'];
    foreach($checks as $check){
        setConfig($pdo, $check, isset($_POST[$check]) ? '1' : '0', $empresaEditando);
    }

    if(!empty($_POST['logo_base64']) && strpos($_POST['logo_base64'], 'data:image/png;base64,') === 0){
        $datosLogo = base64_decode(substr($_POST['logo_base64'], strlen('data:image/png;base64,')));
        if($datosLogo !== false && strlen($datosLogo) > 100){
            $dirLogo = '../uploads/logo';
            if(!is_dir($dirLogo)) mkdir($dirLogo, 0777, true);
            $ruta = 'uploads/logo/logo_' . time() . '.png';
            file_put_contents('../' . $ruta, $datosLogo);
            setConfig($pdo, 'logo', $ruta, $empresaEditando);
        }
    } elseif(!empty($_FILES['logo']['name'])){
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg','jpeg','png','webp','svg'];
        if(in_array($ext, $permitidos)){
            $ruta = 'uploads/logo/logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], '../' . $ruta);
            setConfig($pdo, 'logo', $ruta, $empresaEditando);
        }
    }

    if(!empty($_FILES['fondo_imagen']['name'])){
        $ext = strtolower(pathinfo($_FILES['fondo_imagen']['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg','jpeg','png','webp'];
        if(in_array($ext, $permitidos)){
            $ruta = 'uploads/config/fondo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['fondo_imagen']['tmp_name'], '../' . $ruta);
            setConfig($pdo, 'apariencia_fondo_imagen', $ruta, $empresaEditando);
        }
    }

    $mensaje = "Configuración guardada correctamente.";
}

$config = erp_config_empresa($pdo, $empresaEditando);

admin_header("Configuración general", "configuracion");
?>

<style>
.tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.tab-btn{border:0;background:#eef2ff;padding:12px 16px;border-radius:12px;font-weight:bold;cursor:pointer}
.tab-btn.active{background:#2563eb;color:white}
.tab-panel{display:none}
.tab-panel.active{display:block}
.config-grid{display:grid;grid-template-columns:1.2fr .9fr;gap:22px}
.preview{border-radius:20px;color:white;height:420px;box-shadow:0 12px 30px rgba(15,23,42,.18);position:relative;overflow:hidden;background:linear-gradient(135deg,<?= htmlspecialchars($config['color_principal'] ?? '#0057d9') ?>,<?= htmlspecialchars($config['color_secundario'] ?? '#06b6d4') ?>)}
.preview::before{content:"";position:absolute;inset:0;background:rgba(0,0,0,.10);z-index:0}
.draggable{position:absolute;z-index:2;cursor:move;user-select:none}
.preview-logo{width:90px;height:90px;border-radius:20px;background:white;color:#0057d9;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:42px;overflow:hidden}
.preview-logo img{width:100%;height:100%;object-fit:contain;padding:6px;box-sizing:border-box}
.preview-name{font-size:30px;font-weight:900;line-height:1.05;max-width:260px}
.preview-info{font-size:18px;font-weight:bold;max-width:360px}
.notice-ok{background:#dcfce7;color:#166534;border:1px solid #86efac;padding:12px;border-radius:12px;margin-bottom:15px;font-weight:bold}
.color-line{display:grid;grid-template-columns:90px 1fr;gap:10px}
.color-line input[type="color"]{height:48px;width:90px;padding:4px}
.help{font-size:13px;color:#6b7280;margin-top:6px}
.hidden-pos{display:none}
.public-preview{border-radius:22px;padding:28px;background:linear-gradient(135deg,#f8fafc,#eef6ff);border:1px solid #e5e7eb;box-shadow:0 12px 30px rgba(15,23,42,.08)}
.public-logo{width:82px;height:82px;border-radius:20px;background:#0057d9;color:white;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:38px;margin-bottom:18px;overflow:hidden}
.public-logo img{width:100%;height:100%;object-fit:contain;padding:4px;box-sizing:border-box}
.public-preview h2{font-size:30px;margin:0 0 14px;color:#07162f}
.public-info{background:white;border:1px solid #e5e7eb;border-radius:16px;padding:16px;margin:16px 0;color:#334155;font-weight:700}
.public-info p{margin:9px 0}
.public-actions{display:flex;gap:10px;flex-wrap:wrap}
@media(max-width:900px){.config-grid{grid-template-columns:1fr}}
.logo-editor-drop{display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:18px}
.logo-editor-frame{width:140px;height:140px;border-radius:18px;background:repeating-conic-gradient(#f1f5f9 0% 25%,#ffffff 0% 50%) 50%/16px 16px;border:2px dashed #94a3b8;overflow:hidden;position:relative;cursor:grab;flex:0 0 auto;user-select:none}
.logo-editor-frame:active{cursor:grabbing}
.logo-editor-frame img{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%) scale(1);max-width:none;pointer-events:none}
#logoFramePlaceholder{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px;text-align:center}
.logo-editor-controls{flex:1 1 260px;min-width:220px}
.logo-editor-zoom{display:flex;align-items:center;gap:8px;margin-top:12px}
.logo-editor-zoom input[type=range]{flex:1}
.empresa-switch{background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:14px 16px;margin-bottom:18px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.empresa-switch label{font-weight:800;color:#1e3a8a}
.empresa-switch select{padding:9px 12px;border-radius:10px;border:1px solid #93c5fd;font-weight:700}
.empresa-switch .help{margin:0;width:100%}
</style>
<div class="config-grid">
    <div class="panel">
        <div class="panel-header"><h3>Configuración de la tienda</h3></div>

        <div class="empresa-switch">
            <label>Editando el sitio de:</label>
            <select onchange="window.location.href='configuracion.php?empresa_id='+this.value">
                <option value="0" <?= $empresaEditando===0?'selected':'' ?>>🏠 Plantilla base (sitio global / clásico)</option>
                <?php foreach($empresasDisponibles as $e): ?>
                    <option value="<?= (int)$e['id'] ?>" <?= $empresaEditando===(int)$e['id']?'selected':'' ?>>🏢 <?= htmlspecialchars($e['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if($empresaEditando>0): $empActual = array_filter($empresasDisponibles, fn($e)=>(int)$e['id']===$empresaEditando); $empActual = reset($empActual); ?>
                <span class="help">Los cambios aquí solo afectan el sitio público de <b><?= htmlspecialchars($empActual['nombre'] ?? '') ?></b> (no a otras empresas). Si un campo no lo tocas, hereda el de la plantilla base.</span>
            <?php else: ?>
                <span class="help">Estás editando la plantilla base: la usan de fondo todas las empresas que aún no hayan cambiado ese campo por su cuenta.</span>
            <?php endif; ?>
        </div>

        <?php if($mensaje): ?><div class="notice-ok"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" type="button" onclick="showTab('empresa',this)">🏢 Empresa</button>
            <button class="tab-btn" type="button" onclick="showTab('apariencia',this)">🎨 Apariencia</button>
            <button class="tab-btn" type="button" onclick="showTab('header',this)">🧭 Header</button>
            <button class="tab-btn" type="button" onclick="showTab('redes',this)">🌐 Redes</button>
            <button class="tab-btn" type="button" onclick="showTab('institucional',this)">🏛 Institucional</button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="formConfig">
            <input type="hidden" name="preview_logo_x" id="preview_logo_x" value="<?= htmlspecialchars($config['preview_logo_x'] ?? '35') ?>">
            <input type="hidden" name="preview_logo_y" id="preview_logo_y" value="<?= htmlspecialchars($config['preview_logo_y'] ?? '35') ?>">
            <input type="hidden" name="preview_nombre_x" id="preview_nombre_x" value="<?= htmlspecialchars($config['preview_nombre_x'] ?? '160') ?>">
            <input type="hidden" name="preview_nombre_y" id="preview_nombre_y" value="<?= htmlspecialchars($config['preview_nombre_y'] ?? '45') ?>">
            <input type="hidden" name="preview_info_x" id="preview_info_x" value="<?= htmlspecialchars($config['preview_info_x'] ?? '35') ?>">
            <input type="hidden" name="preview_info_y" id="preview_info_y" value="<?= htmlspecialchars($config['preview_info_y'] ?? '170') ?>">

            <div id="tab-empresa" class="tab-panel active">
                <div class="form-grid">
                    <div class="form-group"><label>Nombre comercial</label><input name="nombre_comercial" id="nombre_comercial" value="<?= htmlspecialchars($config['nombre_comercial'] ?? '') ?>"></div>
                    <div class="form-group"><label>Razón social</label><input name="razon_social" value="<?= htmlspecialchars($config['razon_social'] ?? '') ?>"></div>
                    <div class="form-group"><label>RUC</label><input name="ruc" value="<?= htmlspecialchars($config['ruc'] ?? '') ?>"></div>
                    <div class="form-group"><label>Correo</label><input name="correo" value="<?= htmlspecialchars($config['correo'] ?? '') ?>"></div>
                    <div class="form-group"><label>Teléfono</label><input name="telefono" value="<?= htmlspecialchars($config['telefono'] ?? '') ?>"></div>
                    <div class="form-group"><label>WhatsApp</label><input name="whatsapp" id="whatsapp" value="<?= htmlspecialchars($config['whatsapp'] ?? '') ?>"></div>
                    <div class="form-group full"><label>Dirección</label><input name="direccion" id="direccion" value="<?= htmlspecialchars($config['direccion'] ?? '') ?>"></div>
                    <div class="form-group full"><label>Enlace de Google Maps</label><input name="maps_url" id="maps_url" value="<?= htmlspecialchars($config['maps_url'] ?? '') ?>" placeholder="https://maps.app.goo.gl/..."><p class="help">Este enlace alimentará el botón “Cómo llegar” de la página pública de contacto.</p></div>
                    <div class="form-group full"><label>Horario</label><input name="horario" id="horario" value="<?= htmlspecialchars($config['horario'] ?? '') ?>"></div>
                    <div class="form-group"><label>Moneda</label><input name="moneda" value="<?= htmlspecialchars($config['moneda'] ?? 'S/') ?>"></div>
                    <div class="form-group"><label>IGV (%)</label><input type="number" step="0.01" name="igv" value="<?= htmlspecialchars($config['igv'] ?? '18') ?>"></div>
                    <div class="form-group full">
                        <label>Logo</label>
                        <input type="hidden" name="logo_base64" id="logo_base64" value="">
                        <div class="logo-editor">
                            <div class="logo-editor-drop" id="logoDropZone">
                                <div class="logo-editor-frame" id="logoFrame">
                                    <?php if(!empty($config['logo'])): ?>
                                        <img id="logoCropImg" src="../<?= htmlspecialchars($config['logo']) ?>" draggable="false">
                                    <?php else: ?>
                                        <img id="logoCropImg" src="" draggable="false" style="display:none">
                                        <span id="logoFramePlaceholder">Sin logo</span>
                                    <?php endif; ?>
                                </div>
                                <div class="logo-editor-controls">
                                    <label class="btn gray" style="cursor:pointer;display:inline-block">📁 Elegir imagen<input type="file" id="logoFileInput" accept="image/png,image/jpeg,image/webp" style="display:none"></label>
                                    <div class="logo-editor-zoom"><span>🔍−</span><input type="range" id="logoZoom" min="100" max="300" value="100" step="1"><span>🔍+</span></div>
                                    <p class="help">Arrastra la imagen dentro del cuadro para moverla. Usa el control de zoom para acercar. Así se verá tu logo antes de guardar (el cuadro es exactamente el tamaño real del header).</p>
                                </div>
                            </div>
                        </div>
                        <?php if(!empty($config['logo'])): ?><p class="help">Logo actual guardado: <?= htmlspecialchars($config['logo']) ?></p><?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="tab-apariencia" class="tab-panel">
                <div class="form-grid">
                    <div class="form-group"><label>Tipo de fondo</label><select name="apariencia_fondo_tipo" id="fondo_tipo"><option value="degradado" <?= ($config['apariencia_fondo_tipo']??'degradado')==='degradado'?'selected':'' ?>>Degradado</option><option value="imagen" <?= ($config['apariencia_fondo_tipo']??'')==='imagen'?'selected':'' ?>>Imagen</option></select></div>
                    <div class="form-group"><label>Opacidad imagen (%)</label><input type="number" min="0" max="100" name="apariencia_fondo_opacidad" id="fondo_opacidad" value="<?= htmlspecialchars($config['apariencia_fondo_opacidad'] ?? '85') ?>"></div>
                    <div class="form-group"><label>Color principal</label><div class="color-line"><input type="color" name="color_principal" id="color_principal" value="<?= htmlspecialchars($config['color_principal'] ?? '#0057d9') ?>"><input id="color_principal_text" readonly value="<?= htmlspecialchars($config['color_principal'] ?? '#0057d9') ?>"></div></div>
                    <div class="form-group"><label>Color secundario</label><div class="color-line"><input type="color" name="color_secundario" id="color_secundario" value="<?= htmlspecialchars($config['color_secundario'] ?? '#06b6d4') ?>"><input id="color_secundario_text" readonly value="<?= htmlspecialchars($config['color_secundario'] ?? '#06b6d4') ?>"></div></div>
                    <div class="form-group full"><label>Imagen de fondo</label><input type="file" name="fondo_imagen" accept="image/*"><?php if(!empty($config['apariencia_fondo_imagen'])): ?><p class="help">Fondo actual: <?= htmlspecialchars($config['apariencia_fondo_imagen']) ?></p><?php endif; ?></div>
                    <div class="form-group"><label>Tamaño fondo</label><select name="apariencia_fondo_size" id="fondo_size"><option value="cover" <?= ($config['apariencia_fondo_size']??'cover')==='cover'?'selected':'' ?>>Cubrir</option><option value="contain" <?= ($config['apariencia_fondo_size']??'')==='contain'?'selected':'' ?>>Contener</option><option value="auto" <?= ($config['apariencia_fondo_size']??'')==='auto'?'selected':'' ?>>Auto</option></select></div>
                    <div class="form-group"><label>Posición fondo</label><select name="apariencia_fondo_posicion" id="fondo_posicion"><option value="center" <?= ($config['apariencia_fondo_posicion']??'center')==='center'?'selected':'' ?>>Centro</option><option value="left" <?= ($config['apariencia_fondo_posicion']??'')==='left'?'selected':'' ?>>Izquierda</option><option value="right" <?= ($config['apariencia_fondo_posicion']??'')==='right'?'selected':'' ?>>Derecha</option><option value="top" <?= ($config['apariencia_fondo_posicion']??'')==='top'?'selected':'' ?>>Arriba</option><option value="bottom" <?= ($config['apariencia_fondo_posicion']??'')==='bottom'?'selected':'' ?>>Abajo</option></select></div>
                    <div class="form-group"><label>Repetir fondo</label><select name="apariencia_fondo_repetir" id="fondo_repetir"><option value="no-repeat" <?= ($config['apariencia_fondo_repetir']??'no-repeat')==='no-repeat'?'selected':'' ?>>No repetir</option><option value="repeat" <?= ($config['apariencia_fondo_repetir']??'')==='repeat'?'selected':'' ?>>Repetir</option></select></div>
                </div>
            </div>

            <div id="tab-header" class="tab-panel">
                <p class="help"><strong>Nuevo:</strong> en la vista previa puedes arrastrar el logo, el nombre y la información. Luego guarda la configuración.</p>
                <div class="form-grid">
                    <div class="form-group"><label>Orden del header real</label><select name="header_orden"><option value="logo-buscador-acciones" <?= ($config['header_orden']??'logo-buscador-acciones')==='logo-buscador-acciones'?'selected':'' ?>>Logo | Buscador | Acciones</option><option value="buscador-logo-acciones" <?= ($config['header_orden']??'')==='buscador-logo-acciones'?'selected':'' ?>>Buscador | Logo | Acciones</option><option value="acciones-buscador-logo" <?= ($config['header_orden']??'')==='acciones-buscador-logo'?'selected':'' ?>>Acciones | Buscador | Logo</option></select></div>
                    <div class="form-group"><label>Posición predefinida</label><select name="header_logo_posicion"><option value="izquierda" <?= ($config['header_logo_posicion']??'izquierda')==='izquierda'?'selected':'' ?>>Izquierda</option><option value="centro" <?= ($config['header_logo_posicion']??'')==='centro'?'selected':'' ?>>Centro</option><option value="derecha" <?= ($config['header_logo_posicion']??'')==='derecha'?'selected':'' ?>>Derecha</option></select></div>
                    <div class="form-group full"><label><input type="checkbox" name="header_mostrar_topbar" <?= ($config['header_mostrar_topbar']??'1')==='1'?'checked':'' ?>> Mostrar barra superior</label></div>
                    <div class="form-group full"><label><input type="checkbox" name="header_mostrar_redes" <?= ($config['header_mostrar_redes']??'1')==='1'?'checked':'' ?>> Mostrar redes sociales</label></div>
                    <div class="form-group full"><label><input type="checkbox" name="header_mostrar_buscador" <?= ($config['header_mostrar_buscador']??'1')==='1'?'checked':'' ?>> Mostrar buscador</label></div>
                    <div class="form-group full"><label><input type="checkbox" name="header_mostrar_login" <?= ($config['header_mostrar_login']??'1')==='1'?'checked':'' ?>> Mostrar login</label></div>
                    <div class="form-group full"><label><input type="checkbox" name="header_mostrar_cotizacion" <?= ($config['header_mostrar_cotizacion']??'1')==='1'?'checked':'' ?>> Mostrar cotización</label></div>
                </div>
            </div>

            <div id="tab-institucional" class="tab-panel">
                <div class="form-grid">
                    <div class="form-group full"><label>Misión</label><textarea name="mision" rows="4"><?= htmlspecialchars($config['mision'] ?? 'Brindar productos y servicios de calidad, con atención cercana y confiable.') ?></textarea></div>
                    <div class="form-group full"><label>Visión</label><textarea name="vision" rows="4"><?= htmlspecialchars($config['vision'] ?? 'Ser una tienda referente, integrando tecnología y mejora continua.') ?></textarea></div>
                    <div class="form-group full"><label>Valores</label><textarea name="valores" rows="4"><?= htmlspecialchars($config['valores'] ?? 'Honestidad, responsabilidad, respeto, innovación y compromiso con el cliente.') ?></textarea></div>
                    <div class="form-group full"><label><input type="checkbox" name="publicidad_web_activa" <?= ($config['publicidad_web_activa']??'1')==='1'?'checked':'' ?>> Mostrar publicidad de desarrollo web en el footer</label></div>
                    <div class="form-group full"><label>Texto publicitario</label><input name="publicidad_web_texto" value="<?= htmlspecialchars($config['publicidad_web_texto'] ?? '¿Te gustó esta página web y quieres crear la tuya? Comunícate con nosotros.') ?>"></div>
                    <div class="form-group"><label>WhatsApp publicidad</label><input name="publicidad_web_whatsapp" value="<?= htmlspecialchars($config['publicidad_web_whatsapp'] ?? '964546833') ?>"></div>
                    <div class="form-group"><label>Firma publicidad</label><input name="publicidad_web_firma" value="<?= htmlspecialchars($config['publicidad_web_firma'] ?? 'Desarrollado con MicaStore ERP') ?>"></div>
                </div>
            </div>

            <div id="tab-redes" class="tab-panel">
                <div class="form-grid">
                    <div class="form-group"><label>Facebook</label><input name="facebook" value="<?= htmlspecialchars($config['facebook'] ?? '') ?>"></div>
                    <div class="form-group"><label>Instagram</label><input name="instagram" value="<?= htmlspecialchars($config['instagram'] ?? '') ?>"></div>
                    <div class="form-group"><label>TikTok</label><input name="tiktok" value="<?= htmlspecialchars($config['tiktok'] ?? '') ?>"></div>
                    <div class="form-group"><label>YouTube</label><input name="youtube" value="<?= htmlspecialchars($config['youtube'] ?? '') ?>"></div>
                </div>
            </div>

            <br><button class="btn green" type="submit">Guardar configuración</button>
        </form>
    </div>

    <div class="panel">
        <h3>Vista previa real</h3>
        <p class="help">Esta información alimenta la página pública de contacto.</p><br>

        <div class="public-preview">
            <div class="public-logo" id="publicLogoBox">
                <?php if(!empty($config['logo'])): ?>
                    <img id="publicLogoImg" src="../<?= htmlspecialchars($config['logo']) ?>">
                <?php else: ?>
                    <span id="publicLogoLetter">M</span><img id="publicLogoImg" src="" style="display:none">
                <?php endif; ?>
            </div>

            <h2 id="previewNombre"><?= htmlspecialchars($config['nombre_comercial'] ?? 'Mica Store') ?></h2>

            <div class="public-info">
                <p>📍 <span id="previewDireccion"><?= htmlspecialchars($config['direccion'] ?? '') ?></span></p>
                <p>🕒 <span id="previewHorario"><?= htmlspecialchars($config['horario'] ?? '') ?></span></p>
                <p>💬 WhatsApp: <span id="previewWhatsapp"><?= htmlspecialchars($config['whatsapp'] ?? '') ?></span></p>
                <p>✉️ <?= htmlspecialchars($config['correo'] ?? '') ?></p>
            </div>

            <div class="public-actions">
                <a class="btn green" id="previewWaBtn" target="_blank" href="#">WhatsApp</a>
                <a class="btn" id="previewMapBtn" target="_blank" href="#">Cómo llegar</a>
                <a class="btn gray" target="_blank" href="../contacto.php">Abrir página de contacto</a>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(id, btn){
    document.querySelectorAll(".tab-panel").forEach(x=>x.classList.remove("active"));
    document.querySelectorAll(".tab-btn").forEach(x=>x.classList.remove("active"));
    document.getElementById("tab-"+id).classList.add("active");
    btn.classList.add("active");
}

const principal=document.getElementById("color_principal"),secundario=document.getElementById("color_secundario"),preview=document.getElementById("preview");

function actualizarPreview(){
    document.getElementById("color_principal_text").value=principal.value;
    document.getElementById("color_secundario_text").value=secundario.value;
    document.getElementById("previewNombre").textContent=document.getElementById("nombre_comercial").value||"Mica Store";
    document.getElementById("previewDireccion").textContent=document.getElementById("direccion").value||"";
    document.getElementById("previewHorario").textContent=document.getElementById("horario").value||"";
    document.getElementById("previewWhatsapp").textContent=document.getElementById("whatsapp").value||"";
    const wa=(document.getElementById("whatsapp").value||"").replace(/\D/g,"");
    const maps=document.getElementById("maps_url") ? document.getElementById("maps_url").value : "";
    const waBtn=document.getElementById("previewWaBtn");
    const mapBtn=document.getElementById("previewMapBtn");
    if(waBtn) waBtn.href=wa ? "https://wa.me/51"+wa : "#";
    if(mapBtn){
        mapBtn.href=maps || "#";
        mapBtn.style.display=maps ? "inline-flex" : "none";
    }

    const tipo=document.getElementById("fondo_tipo").value;
    if(preview){
        if(tipo==="degradado"){
            preview.style.background=`linear-gradient(135deg, ${principal.value}, ${secundario.value})`;
            preview.style.backgroundImage="";
        }else{
            preview.style.backgroundColor=principal.value;
            preview.style.backgroundImage="url('../<?= htmlspecialchars($config['apariencia_fondo_imagen'] ?? '') ?>')";
            preview.style.backgroundSize=document.getElementById("fondo_size").value;
            preview.style.backgroundPosition=document.getElementById("fondo_posicion").value;
            preview.style.backgroundRepeat=document.getElementById("fondo_repetir").value;
        }
    }
}

function posicionarInicial(){
    document.querySelectorAll(".draggable").forEach(el=>{
        const xField=document.getElementById(el.dataset.xfield);
        const yField=document.getElementById(el.dataset.yfield);
        if(!xField || !yField) return;
        const x=xField.value || 20;
        const y=yField.value || 20;
        el.style.left=x+"px";
        el.style.top=y+"px";
    });
}

let activo=null, offsetX=0, offsetY=0;
document.querySelectorAll(".draggable").forEach(el=>{
    el.addEventListener("mousedown", e=>{
        activo=el;
        const rect=el.getBoundingClientRect();
        offsetX=e.clientX-rect.left;
        offsetY=e.clientY-rect.top;
    });
});

document.addEventListener("mousemove", e=>{
    if(!activo) return;
    if(!preview) return;
    const p=preview.getBoundingClientRect();
    let x=e.clientX-p.left-offsetX;
    let y=e.clientY-p.top-offsetY;
    x=Math.max(0,Math.min(x,preview.clientWidth-activo.offsetWidth));
    y=Math.max(0,Math.min(y,preview.clientHeight-activo.offsetHeight));
    activo.style.left=x+"px";
    activo.style.top=y+"px";
    document.getElementById(activo.dataset.xfield).value=Math.round(x);
    document.getElementById(activo.dataset.yfield).value=Math.round(y);
});
document.addEventListener("mouseup",()=>activo=null);

document.querySelectorAll("input,select").forEach(el=>el.addEventListener("input",actualizarPreview));
document.querySelectorAll("input,select").forEach(el=>el.addEventListener("change",actualizarPreview));
actualizarPreview();
posicionarInicial();

/* ---- Editor de logo: elegir imagen, arrastrar dentro del cuadro, hacer zoom ---- */
(function(){
    const frame = document.getElementById("logoFrame");
    const img = document.getElementById("logoCropImg");
    const placeholder = document.getElementById("logoFramePlaceholder");
    const fileInput = document.getElementById("logoFileInput");
    const zoom = document.getElementById("logoZoom");
    const base64Input = document.getElementById("logo_base64");
    const FRAME_SIZE = 140;
    const OUTPUT_SIZE = 400;

    let estado = { srcImg:null, natW:0, natH:0, scale:1, baseScale:1, x:0, y:0, arrastrando:false, ix:0, iy:0 };

    function aplicarTransform(){
        img.style.transform = "translate(-50%,-50%) translate("+estado.x+"px,"+estado.y+"px) scale("+estado.scale+")";
    }

    function cargarImagen(src){
        const im = new Image();
        im.onload = function(){
            estado.srcImg = im;
            estado.natW = im.naturalWidth;
            estado.natH = im.naturalHeight;
            estado.baseScale = FRAME_SIZE / Math.min(im.naturalWidth, im.naturalHeight);
            estado.scale = estado.baseScale;
            estado.x = 0; estado.y = 0;
            zoom.value = 100;
            img.src = src;
            img.style.width = im.naturalWidth+"px";
            img.style.height = im.naturalHeight+"px";
            img.style.display = "block";
            if(placeholder) placeholder.style.display = "none";
            aplicarTransform();
            generarRecorte();
        };
        im.src = src;
    }

    fileInput.addEventListener("change", function(e){
        const f = e.target.files[0];
        if(!f) return;
        const reader = new FileReader();
        reader.onload = ev => cargarImagen(ev.target.result);
        reader.readAsDataURL(f);
    });

    // Arrastrar el logo dentro del cuadro (mouse y touch)
    function iniciarArrastre(clientX, clientY){
        if(!estado.srcImg) return;
        estado.arrastrando = true;
        estado.ix = clientX - estado.x;
        estado.iy = clientY - estado.y;
    }
    function moverArrastre(clientX, clientY){
        if(!estado.arrastrando) return;
        estado.x = clientX - estado.ix;
        estado.y = clientY - estado.iy;
        aplicarTransform();
    }
    function soltarArrastre(){
        if(estado.arrastrando){ estado.arrastrando=false; generarRecorte(); }
    }
    frame.addEventListener("mousedown", e=>{ iniciarArrastre(e.clientX,e.clientY); e.preventDefault(); });
    document.addEventListener("mousemove", e=>moverArrastre(e.clientX,e.clientY));
    document.addEventListener("mouseup", soltarArrastre);
    frame.addEventListener("touchstart", e=>{ const t=e.touches[0]; iniciarArrastre(t.clientX,t.clientY); }, {passive:true});
    frame.addEventListener("touchmove", e=>{ const t=e.touches[0]; moverArrastre(t.clientX,t.clientY); }, {passive:true});
    frame.addEventListener("touchend", soltarArrastre);

    // Zoom con el slider (100% a 300%)
    zoom.addEventListener("input", function(){
        if(!estado.srcImg) return;
        estado.scale = estado.baseScale * (zoom.value/100);
        aplicarTransform();
    });
    zoom.addEventListener("change", generarRecorte);

    // Genera el PNG final recortado (lo que realmente se va a guardar) y actualiza
    // las vistas previas para que el usuario vea exactamente cómo quedará.
    function generarRecorte(){
        if(!estado.srcImg) return;
        const canvas = document.createElement("canvas");
        canvas.width = OUTPUT_SIZE; canvas.height = OUTPUT_SIZE;
        const ctx = canvas.getContext("2d");
        const factor = OUTPUT_SIZE / FRAME_SIZE;
        const drawW = estado.natW * estado.scale * factor;
        const drawH = estado.natH * estado.scale * factor;
        const cx = OUTPUT_SIZE/2 + estado.x*factor;
        const cy = OUTPUT_SIZE/2 + estado.y*factor;
        ctx.drawImage(estado.srcImg, cx-drawW/2, cy-drawH/2, drawW, drawH);
        const dataUrl = canvas.toDataURL("image/png");
        base64Input.value = dataUrl;
        const publicImg = document.getElementById("publicLogoImg");
        const publicLetter = document.getElementById("publicLogoLetter");
        if(publicImg){ publicImg.src = dataUrl; publicImg.style.display="block"; if(publicLetter) publicLetter.style.display="none"; }
    }

    const srcExistente = img.getAttribute("src");
    if(srcExistente && srcExistente.trim() !== ""){ cargarImagen(srcExistente); }
})();
</script>

<?php admin_footer(); ?>
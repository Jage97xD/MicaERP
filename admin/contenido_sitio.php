<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
function setConfigCms($pdo, $clave, $valor){
    $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
    $stmt->execute([$clave, $valor]);
}

$mensaje = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $campos = [
        'footer_descripcion','newsletter_titulo','newsletter_texto',
        'home_contacto_titulo','home_contacto_texto',
        'publicidad_web_titulo','publicidad_web_firma','publicidad_web_texto','publicidad_web_whatsapp',
        'trabaja_titulo','trabaja_texto',
        'mision','vision','valores','terminos_texto','privacidad_texto'
    ];
    foreach($campos as $c){ setConfigCms($pdo, $c, trim($_POST[$c] ?? '')); }
    foreach(['newsletter_activo','publicidad_web_activa','trabaja_activo'] as $c){ setConfigCms($pdo, $c, isset($_POST[$c]) ? '1' : '0'); }
    erp_auditoria($pdo,'contenido','editar','Actualizó contenido editable del sitio');
    $mensaje = 'Contenido guardado correctamente.';
}

$config = configTodos($pdo);
admin_header("Contenido del sitio", "contenido");
?>
<style>
.cms-layout{display:grid;grid-template-columns:1.25fr .75fr;gap:22px}.tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}.tab-btn{border:0;background:#eef2ff;padding:12px 16px;border-radius:12px;font-weight:900;cursor:pointer}.tab-btn.active{background:#2563eb;color:#fff}.tab-panel{display:none}.tab-panel.active{display:block}.notice-ok{background:#dcfce7;color:#166534;border:1px solid #86efac;border-radius:12px;padding:13px;margin-bottom:16px;font-weight:900}.preview-card{background:linear-gradient(120deg,#07162f,#0057d9);color:white;border-radius:22px;padding:24px;margin-bottom:16px}.preview-card.light{background:white;color:#07162f;border:1px solid #e5e7eb}.preview-card p{color:inherit;opacity:.88;line-height:1.55}.help{color:#64748b;font-size:13px;margin-top:6px}.check-line{display:flex;gap:8px;align-items:center;font-weight:900;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:13px}@media(max-width:1000px){.cms-layout{grid-template-columns:1fr}}
</style>
<div class="cms-layout">
    <div class="panel">
        <div class="panel-header"><div><h3>CMS de la tienda</h3><p class="help">Edita textos comerciales e institucionales sin tocar código.</p></div></div>
        <?php if($mensaje): ?><div class="notice-ok"><?= h($mensaje) ?></div><?php endif; ?>
        <div class="tabs">
            <button class="tab-btn active" type="button" onclick="showCmsTab('home',this)">🏠 Home</button>
            <button class="tab-btn" type="button" onclick="showCmsTab('footer',this)">🧩 Footer</button>
            <button class="tab-btn" type="button" onclick="showCmsTab('publicidad',this)">📣 Publicidad</button>
            <button class="tab-btn" type="button" onclick="showCmsTab('institucional',this)">🏢 Institucional</button>
            <button class="tab-btn" type="button" onclick="showCmsTab('rrhh',this)">💼 Trabaja</button>
        </div>
        <form method="POST">
            <div id="tab-home" class="tab-panel active">
                <div class="form-grid">
                    <div class="form-group full"><label class="check-line"><input type="checkbox" name="newsletter_activo" <?= ($config['newsletter_activo']??'1')==='1'?'checked':'' ?>> Mostrar bloque de novedades/ofertas</label></div>
                    <div class="form-group"><label>Título newsletter</label><input name="newsletter_titulo" value="<?= h($config['newsletter_titulo'] ?? 'Novedades y ofertas') ?>"></div>
                    <div class="form-group"><label>Texto newsletter</label><input name="newsletter_texto" value="<?= h($config['newsletter_texto'] ?? '') ?>"></div>
                    <div class="form-group"><label>Título contacto home</label><input name="home_contacto_titulo" value="<?= h($config['home_contacto_titulo'] ?? 'Estamos para atenderte') ?>"></div>
                    <div class="form-group"><label>Texto contacto home</label><input name="home_contacto_texto" value="<?= h($config['home_contacto_texto'] ?? '') ?>"></div>
                </div>
            </div>
            <div id="tab-footer" class="tab-panel">
                <div class="form-grid">
                    <div class="form-group full"><label>Descripción del footer</label><textarea name="footer_descripcion" rows="5"><?= h($config['footer_descripcion'] ?? '') ?></textarea></div>
                </div>
            </div>
            <div id="tab-publicidad" class="tab-panel">
                <div class="form-grid">
                    <div class="form-group full"><label class="check-line"><input type="checkbox" name="publicidad_web_activa" <?= ($config['publicidad_web_activa']??'1')==='1'?'checked':'' ?>> Mostrar bloque discreto de publicidad</label></div>
                    <div class="form-group"><label>Título</label><input name="publicidad_web_titulo" value="<?= h($config['publicidad_web_titulo'] ?? '¿Necesitas una tienda como esta?') ?>"></div>
                    <div class="form-group"><label>Firma / marca</label><input name="publicidad_web_firma" value="<?= h($config['publicidad_web_firma'] ?? 'MicaStore ERP') ?>"></div>
                    <div class="form-group full"><label>Texto</label><textarea name="publicidad_web_texto" rows="4"><?= h($config['publicidad_web_texto'] ?? '') ?></textarea></div>
                    <div class="form-group"><label>WhatsApp comercial</label><input name="publicidad_web_whatsapp" value="<?= h($config['publicidad_web_whatsapp'] ?? '964546833') ?>"></div>
                </div>
            </div>
            <div id="tab-institucional" class="tab-panel">
                <div class="form-grid">
                    <div class="form-group full"><label>Misión</label><textarea name="mision" rows="4"><?= h($config['mision'] ?? '') ?></textarea></div>
                    <div class="form-group full"><label>Visión</label><textarea name="vision" rows="4"><?= h($config['vision'] ?? '') ?></textarea></div>
                    <div class="form-group full"><label>Valores</label><textarea name="valores" rows="4"><?= h($config['valores'] ?? '') ?></textarea></div>
                    <div class="form-group full"><label>Términos y condiciones</label><textarea name="terminos_texto" rows="6"><?= h($config['terminos_texto'] ?? '') ?></textarea></div>
                    <div class="form-group full"><label>Política de privacidad</label><textarea name="privacidad_texto" rows="6"><?= h($config['privacidad_texto'] ?? '') ?></textarea></div>
                </div>
            </div>
            <div id="tab-rrhh" class="tab-panel">
                <div class="form-grid">
                    <div class="form-group full"><label class="check-line"><input type="checkbox" name="trabaja_activo" <?= ($config['trabaja_activo']??'1')==='1'?'checked':'' ?>> Mostrar página Trabaja con nosotros</label></div>
                    <div class="form-group"><label>Título de la página</label><input name="trabaja_titulo" value="<?= h($config['trabaja_titulo'] ?? 'Trabaja con nosotros') ?>"></div>
                    <div class="form-group"><label>Texto introductorio</label><input name="trabaja_texto" value="<?= h($config['trabaja_texto'] ?? '') ?>"></div>
                </div>
            </div>
            <br><button class="btn green" type="submit">Guardar contenido</button>
        </form>
    </div>
    <div class="panel">
        <div class="panel-header"><h3>Vista previa rápida</h3></div>
        <div class="preview-card"><h3><?= h($config['home_contacto_titulo'] ?? 'Estamos para atenderte') ?></h3><p><?= h($config['home_contacto_texto'] ?? '') ?></p></div>
        <div class="preview-card light"><h3><?= h($config['publicidad_web_titulo'] ?? '¿Necesitas una tienda como esta?') ?></h3><p><?= h($config['publicidad_web_texto'] ?? '') ?></p></div>
        <a class="btn" target="_blank" href="../tienda_visual_v3.php">Ver home</a>
        <a class="btn gray" target="_blank" href="../trabaja_con_nosotros.php">Ver trabaja con nosotros</a>
    </div>
</div>
<script>
function showCmsTab(name,btn){document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));document.getElementById('tab-'+name).classList.add('active');btn.classList.add('active');}
</script>
<?php admin_footer(); ?>

<?php
require_once __DIR__ . '/../../config/erp_core.php';
$empresaSlugActual = $GLOBALS['empresaSlugActual'] ?? null;
if(!function_exists('micaFooterConfig')){
    function micaFooterConfig($pdo){
        $empresaId = $GLOBALS['empresaId'] ?? 0;
        $config = erp_config_empresa($pdo, $empresaId);
        $whatsapp = preg_replace('/\D+/', '', $config['whatsapp'] ?? '');
        $pubWa = preg_replace('/\D+/', '', $config['publicidad_web_whatsapp'] ?? ($config['telefono'] ?? '964546833'));
        return [
            'nombre' => $config['nombre_comercial'] ?? 'Mica Store',
            'direccion' => $config['direccion'] ?? '',
            'whatsapp' => $whatsapp,
            'correo' => $config['correo'] ?? '',
            'horario' => $config['horario'] ?? '',
            'facebook' => $config['facebook'] ?? '',
            'instagram' => $config['instagram'] ?? '',
            'tiktok' => $config['tiktok'] ?? '',
            'publicidad_activa' => ($config['publicidad_web_activa'] ?? '1') === '1',
            'publicidad_titulo' => $config['publicidad_web_titulo'] ?? '¿Necesitas una tienda como esta?',
            'publicidad_texto' => $config['publicidad_web_texto'] ?? 'Creamos tiendas, catálogos y sistemas para negocios. Comunícate para una demostración.',
            'publicidad_whatsapp' => $pubWa,
            'publicidad_firma' => $config['publicidad_web_firma'] ?? 'MicaStore ERP',
            'footer_descripcion' => $config['footer_descripcion'] ?? 'Catálogo online de productos y servicios. Atención por WhatsApp, cotizaciones y seguimiento de pedidos.',
        ];
    }
}
$footerConfig = micaFooterConfig($pdo);
$footerCampos = function_exists('micaCamposPersonalizados') ? micaCamposPersonalizados($pdo, 'footer') : [];
?>

<?php if($footerConfig['publicidad_activa']): ?>
<section class="v3-web-promo">
    <div>
        <strong>💻 <?= h($footerConfig['publicidad_titulo']) ?></strong>
        <p><?= h($footerConfig['publicidad_texto']) ?> <span class="v3-web-promo-by">· <?= h($footerConfig['publicidad_firma']) ?></span></p>
    </div>
    <?php if(!empty($footerConfig['publicidad_whatsapp'])): ?>
        <a target="_blank" href="https://wa.me/51<?= h($footerConfig['publicidad_whatsapp']) ?>">Solicitar información</a>
    <?php endif; ?>
</section>
<?php endif; ?>

<footer class="v3-footer">
    <div class="v3-footer-grid">
        <div>
            <h3><?= h($footerConfig['nombre']) ?></h3>
            <p><?= nl2br(h($footerConfig['footer_descripcion'])) ?></p>
            <?php if(!empty($footerConfig['direccion'])): ?><p>📍 <?= h($footerConfig['direccion']) ?></p><?php endif; ?>
        </div>

        <div>
            <h3>Categorías</h3>
            <?php foreach(($categorias ?? []) as $cat): ?>
                <a href="<?= h(erp_url_empresa($empresaSlugActual, 'tienda_visual_v3.php?categoria='.$cat['slug'])) ?>"><?= h($cat['nombre']) ?></a>
            <?php endforeach; ?>
        </div>

        <div>
            <h3>Atención</h3>
            <?php if(!empty($footerConfig['whatsapp'])): ?><p>WhatsApp: <?= h($footerConfig['whatsapp']) ?></p><?php endif; ?>
            <?php if(!empty($footerConfig['correo'])): ?><p>Correo: <?= h($footerConfig['correo']) ?></p><?php endif; ?>
            <?php if(!empty($footerConfig['horario'])): ?><p>Horario: <?= h($footerConfig['horario']) ?></p><?php endif; ?>
            <a href="<?= h(erp_url_empresa($empresaSlugActual, 'contacto.php')) ?>">Contáctenos</a>
            <a href="<?= h(erp_url_empresa($empresaSlugActual, 'cliente/mis_pedidos.php')) ?>">Mis pedidos</a>
            <a href="<?= h(erp_url_empresa($empresaSlugActual, 'libro_reclamaciones.php')) ?>">Libro de reclamaciones</a>
            <a href="<?= h(erp_url_empresa($empresaSlugActual, 'trabaja_con_nosotros.php')) ?>">Trabaja con nosotros</a>
        </div>

        <div>
            <h3>Institucional</h3>
            <a href="<?= h(erp_url_empresa($empresaSlugActual, 'nosotros.php')) ?>">Misión, visión y valores</a>
            <a href="<?= h(erp_url_empresa($empresaSlugActual, 'terminos.php')) ?>">Términos y condiciones</a>
            <a href="<?= h(erp_url_empresa($empresaSlugActual, 'privacidad.php')) ?>">Política de privacidad</a>
            <a href="<?= h(erp_url_empresa($empresaSlugActual, 'trabaja_con_nosotros.php')) ?>">Trabaja con nosotros</a>
            <?php foreach($footerCampos as $campo): ?>
                <p><strong><?= h($campo['nombre']) ?>:</strong> <?= nl2br(h($campo['valor'])) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
</footer>

<?php
require_once __DIR__ . '/../../config/erp_core.php';
if(!function_exists('micaContactoConfig')){
    function micaContactoConfig($pdo){
        $empresaId = $GLOBALS['empresaId'] ?? 0;
        $config = erp_config_empresa($pdo, $empresaId);

        $nombre = $config['nombre_comercial'] ?? 'Mica Store';
        $whatsappRaw = $config['whatsapp'] ?? '';
        $whatsapp = preg_replace('/\D+/', '', $whatsappRaw);
        $telefono = $config['telefono'] ?? '';
        $correo = $config['correo'] ?? '';
        $direccion = $config['direccion'] ?? '';
        $horario = $config['horario'] ?? '';
        $maps = $config['maps_url'] ?? '';
        $facebook = $config['facebook'] ?? '';
        $instagram = $config['instagram'] ?? '';
        $tiktok = $config['tiktok'] ?? '';

        $waNumero = $whatsapp;
        if($waNumero && strlen($waNumero) === 9){
            $waNumero = '51' . $waNumero;
        }

        return [
            'nombre' => $nombre,
            'whatsapp' => $whatsapp,
            'wa_url' => $waNumero ? 'https://wa.me/'.$waNumero.'?text='.urlencode('Hola, deseo información de '.$nombre) : '#',
            'telefono' => $telefono,
            'correo' => $correo,
            'direccion' => $direccion,
            'horario' => $horario,
            'maps' => $maps,
            'facebook' => $facebook,
            'instagram' => $instagram,
            'tiktok' => $tiktok,
        ];
    }
}

if(!function_exists('contactoWidgetV3')){
    function contactoWidgetV3($pdo, $modo='home'){
        $c = micaContactoConfig($pdo);
        ob_start();
        if($modo === 'full'):
?>
<section class="v3-contact-hero">
    <div class="v3-contact-hero-text">
        <span class="v3-contact-kicker">Centro de atención</span>
        <h1>Contáctanos</h1>
        <p>Estamos listos para ayudarte con cotizaciones, pedidos, disponibilidad, entregas y soporte.</p>
        <div class="v3-contact-actions">
            <a class="v3-contact-btn green" target="_blank" href="<?= h($c['wa_url']) ?>">💬 Escribir por WhatsApp</a>
            <?php if(!empty($c['maps'])): ?>
                <a class="v3-contact-btn blue" target="_blank" href="<?= h($c['maps']) ?>">📍 Cómo llegar</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="v3-contact-card premium">
        <h2><?= h($c['nombre']) ?></h2>
        <div class="v3-contact-row"><strong>📍 Dirección</strong><span><?= h($c['direccion'] ?: 'Dirección pendiente') ?></span></div>
        <div class="v3-contact-row"><strong>🕒 Horario</strong><span><?= h($c['horario'] ?: 'Horario pendiente') ?></span></div>
        <div class="v3-contact-row"><strong>💬 WhatsApp</strong><span><?= h($c['whatsapp'] ?: 'Pendiente') ?></span></div>
        <?php if(!empty($c['telefono'])): ?><div class="v3-contact-row"><strong>☎️ Teléfono</strong><span><?= h($c['telefono']) ?></span></div><?php endif; ?>
        <div class="v3-contact-row"><strong>✉️ Correo</strong><span><?= h($c['correo'] ?: 'Pendiente') ?></span></div>
    </div>
</section>
<?php
        else:
?>
<section class="v3-home-contact" id="contacto">
    <div class="v3-home-contact-text">
        <span class="v3-contact-kicker">¿Necesitas ayuda?</span>
        <h2>Estamos para atenderte</h2>
        <p>Cotiza por WhatsApp, revisa nuestra ubicación o escríbenos para recibir atención personalizada.</p>
    </div>
    <div class="v3-home-contact-grid">
        <div class="v3-home-contact-item"><strong>💬 WhatsApp</strong><span><?= h($c['whatsapp'] ?: 'Pendiente') ?></span></div>
        <div class="v3-home-contact-item"><strong>📍 Dirección</strong><span><?= h($c['direccion'] ?: 'Dirección pendiente') ?></span></div>
        <div class="v3-home-contact-item"><strong>✉️ Correo</strong><span><?= h($c['correo'] ?: 'Pendiente') ?></span></div>
        <div class="v3-home-contact-item"><strong>🕒 Horario</strong><span><?= h($c['horario'] ?: 'Horario pendiente') ?></span></div>
    </div>
    <div class="v3-home-contact-actions">
        <a class="v3-contact-btn green" target="_blank" href="<?= h($c['wa_url']) ?>">💬 WhatsApp</a>
        <a class="v3-contact-btn dark" href="contacto.php">Ver contacto completo</a>
    </div>
</section>
<?php
        endif;
        return ob_get_clean();
    }
}
?>

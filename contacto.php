<?php
require_once "config/db.php";
require_once "config/erp_core.php";
require_once "includes/v3/common.php";
require_once "includes/v3/contacto_widget.php";
require_once "includes/v3/empresa_context.php";

$buscar = $_GET['buscar'] ?? '';
$categoria = '';
$mensajeOk = '';
$mensajeError = '';

try{
    $categorias = $pdo->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){
    $categorias = [];
}

$contacto = micaContactoConfig($pdo);
$contactoCampos = function_exists('micaCamposPersonalizados') ? micaCamposPersonalizados($pdo, 'contacto') : [];

try{
    $pdo->exec("CREATE TABLE IF NOT EXISTS contacto_mensajes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(160) NOT NULL,
        correo VARCHAR(160) NULL,
        celular VARCHAR(40) NULL,
        asunto VARCHAR(180) NULL,
        mensaje TEXT NOT NULL,
        acepta_contacto TINYINT DEFAULT 0,
        estado VARCHAR(40) DEFAULT 'Nuevo',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}catch(Exception $e){}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $celular = trim($_POST['celular'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    $acepta = isset($_POST['acepta_contacto']) ? 1 : 0;

    if($nombre === '' || $mensaje === ''){
        $mensajeError = 'Ingresa tu nombre y mensaje para poder atenderte.';
    }else{
        try{
            $stmt = $pdo->prepare("INSERT INTO contacto_mensajes (nombre, correo, celular, asunto, mensaje, acepta_contacto) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$nombre,$correo,$celular,$asunto,$mensaje,$acepta]);
            $mensajeOk = 'Tu mensaje fue registrado correctamente. Te contactaremos pronto.';
        }catch(Exception $e){
            $mensajeError = 'No se pudo registrar el mensaje. También puedes escribirnos por WhatsApp.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Contacto - <?= h($contacto['nombre']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="includes/v3/store_v3.css">
<link rel="stylesheet" href="includes/v3/login_modal.css">
<link rel="stylesheet" href="includes/v3/header_cliente.css">
</head>
<body>

<?php require "includes/v3/topbar.php"; ?>
<?php require "includes/v3/header.php"; ?>
<?php require "includes/v3/menu.php"; ?>

<main class="v3-contact-page">
    <?= contactoWidgetV3($pdo, 'full') ?>

    <section class="v3-contact-content">
        <div class="v3-contact-form-card">
            <div class="v3-section-head compact">
                <h2>Escríbenos</h2>
                <span>Atención comercial</span>
            </div>

            <?php if($mensajeOk): ?>
                <div class="v3-contact-alert ok"><?= h($mensajeOk) ?></div>
            <?php endif; ?>
            <?php if($mensajeError): ?>
                <div class="v3-contact-alert error"><?= h($mensajeError) ?></div>
            <?php endif; ?>

            <form method="POST" class="v3-contact-form">
                <div>
                    <label>Nombre completo *</label>
                    <input name="nombre" required placeholder="Ejemplo: Pilar Bonilla">
                </div>
                <div>
                    <label>Correo</label>
                    <input type="email" name="correo" placeholder="correo@ejemplo.com">
                </div>
                <div>
                    <label>Celular</label>
                    <input name="celular" placeholder="999999999">
                </div>
                <div>
                    <label>Asunto</label>
                    <input name="asunto" placeholder="Cotización, pedido, soporte...">
                </div>
                <div class="full">
                    <label>Mensaje *</label>
                    <textarea name="mensaje" required placeholder="Cuéntanos qué necesitas"></textarea>
                </div>
                <label class="v3-check full">
                    <input type="checkbox" name="acepta_contacto" checked>
                    Acepto que Mica Store me contacte para responder mi consulta y ofrecerme productos relacionados.
                </label>
                <button type="submit">Enviar mensaje</button>
            </form>
        </div>

        <div class="v3-contact-side">
            <div class="v3-contact-mini-card">
                <h3>Atención rápida</h3>
                <p>Para atención inmediata, usa WhatsApp.</p>
                <a class="v3-contact-btn green" target="_blank" href="<?= h($contacto['wa_url']) ?>">💬 Abrir WhatsApp</a>
            </div>

            <div class="v3-contact-mini-card">
                <h3>Ubicación</h3>
                <?php if(!empty($contacto['maps'])): ?>
                    <p>Abre la ubicación exacta en Google Maps.</p>
                    <a class="v3-contact-btn blue" target="_blank" href="<?= h($contacto['maps']) ?>">📍 Ver mapa</a>
                <?php else: ?>
                    <p>El enlace de Google Maps todavía no fue configurado.</p>
                <?php endif; ?>
            </div>

            <?php if(!empty($contactoCampos)): ?>
            <div class="v3-contact-mini-card v3-contact-custom">
                <h3>Información adicional</h3>
                <?php foreach($contactoCampos as $campo): ?>
                    <p><strong><?= h($campo['nombre']) ?>:</strong><br><?= nl2br(h($campo['valor'])) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="v3-contact-mini-card">
                <h3>Preguntas frecuentes</h3>
                <p><strong>¿Puedo cotizar varios productos?</strong><br>Sí, agrega productos a tu cotización y envíala.</p>
                <p><strong>¿Hacen envíos?</strong><br>Sí, coordinamos según dirección y disponibilidad.</p>
                <p><strong>¿Cómo veo mi pedido?</strong><br>Ingresa a Mi cuenta / Mis pedidos.</p>
            </div>
        </div>
    </section>
</main>

<?php require "includes/v3/footer.php"; ?>
<?php require "includes/v3/login_modal.php"; ?>

<a class="v3-float-chat" target="_blank" href="<?= h($contacto['wa_url']) ?>">💬</a>

<script>
window.MICA_CATEGORIA_ACTUAL = "";
</script>
<script src="includes/v3/store_v3.js"></script>
<script src="includes/v3/login_modal.js"></script>
<script src="includes/v3/header_cliente.js"></script>
</body>
</html>

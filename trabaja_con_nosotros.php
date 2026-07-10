<?php
require_once "config/db.php";
require_once "config/erp_core.php";
require_once "includes/v3/common.php";
require_once "includes/v3/empresa_context.php";
$buscar = $_GET['buscar'] ?? '';
$categoria = '';
$config = micaConfigTodos($pdo);
if(($config['trabaja_activo'] ?? '1') !== '1'){ http_response_code(404); die('Página no disponible.'); }
$categorias = $pdo->query("SELECT * FROM categorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$puestos = $pdo->query("SELECT * FROM rrhh_puestos WHERE estado='Activo' AND (fecha_limite IS NULL OR fecha_limite >= CURDATE()) ORDER BY orden ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= h($config['trabaja_titulo'] ?? 'Trabaja con nosotros') ?> - <?= h($config['nombre_comercial'] ?? 'Mica Store') ?></title><link rel="stylesheet" href="includes/v3/store_v3.css"><link rel="stylesheet" href="includes/v3/login_modal.css"><link rel="stylesheet" href="includes/v3/header_cliente.css"></head><body>
<?php require "includes/v3/topbar.php"; require "includes/v3/header.php"; require "includes/v3/menu.php"; ?>
<section class="v3-page-hero"><div class="v3-page-hero-inner"><h1>💼 <?= h($config['trabaja_titulo'] ?? 'Trabaja con nosotros') ?></h1><p><?= h($config['trabaja_texto'] ?? 'Publicamos oportunidades laborales para personas con actitud, responsabilidad y ganas de crecer.') ?></p></div></section>
<main class="v3-page-wrap">
    <section class="v3-jobs-grid">
        <?php foreach($puestos as $p): ?>
            <article class="v3-job-card">
                <div><span class="v3-job-area"><?= h($p['area'] ?: 'General') ?></span><h2><?= h($p['titulo']) ?></h2><p><?= h(mb_substr(strip_tags($p['descripcion'] ?? ''),0,160)) ?><?= strlen($p['descripcion'] ?? '')>160?'...':'' ?></p></div>
                <div class="v3-job-meta"><span>📍 <?= h($p['ubicacion'] ?: 'Por definir') ?></span><span>🧭 <?= h($p['modalidad'] ?: 'Presencial') ?></span><span>👥 <?= (int)$p['vacantes'] ?> vacante(s)</span><?php if($p['fecha_limite']): ?><span>📅 Hasta <?= h($p['fecha_limite']) ?></span><?php endif; ?></div>
                <a class="v3-job-btn" href="trabajo_detalle.php?id=<?= (int)$p['id'] ?>">Ver y postular</a>
            </article>
        <?php endforeach; ?>
        <?php if(!$puestos): ?><div class="v3-legal-card"><h2>No hay puestos activos por ahora</h2><p>Vuelve pronto o escríbenos para dejarnos tus datos.</p><a class="v3-job-btn" href="contacto.php">Contactar</a></div><?php endif; ?>
    </section>
</main>
<?php require "includes/v3/footer.php"; require "includes/v3/login_modal.php"; ?><script src="includes/v3/login_modal.js"></script><script src="includes/v3/header_cliente.js"></script></body></html>

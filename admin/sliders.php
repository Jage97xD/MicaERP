<?php
require_once "../config/db.php";
require_once "layout.php";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

if(isset($_GET["delete"])){
    $id = (int)$_GET["delete"];
    if($id > 0){
        $stmt = $pdo->prepare("DELETE FROM sliders WHERE id=?");
        $stmt->execute([$id]);
    }
    header("Location: sliders.php");
    exit;
}

$sliders = [];
try{ $sliders = $pdo->query("SELECT * FROM sliders ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){}

admin_header("Carrusel", "sliders");
?>

<style>
.slider-card{display:grid;grid-template-columns:130px 1fr 180px;gap:18px;align-items:center;background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:16px;margin-bottom:14px;box-shadow:0 8px 20px rgba(15,23,42,.06)}.slider-img{height:85px;border-radius:14px;background:#020817;overflow:hidden;display:flex;align-items:center;justify-content:center;color:white;font-weight:900}.slider-img img{width:100%;height:100%;object-fit:cover}.slider-info h3{margin:0 0 6px}.slider-info p{margin:0;color:#64748b}.slider-url{margin-top:8px;font-size:13px;color:#475569;background:#f8fafc;border-radius:10px;padding:8px;word-break:break-all}.slider-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}.status{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:900}.status.on{background:#dcfce7;color:#166534}.status.off{background:#fee2e2;color:#991b1b}@media(max-width:800px){.slider-card{grid-template-columns:1fr}.slider-actions{justify-content:flex-start}}
</style>

<div class="panel">
    <div class="panel-header">
        <div>
            <h3>Constructor de carrusel</h3>
            <p style="margin:4px 0 0;color:#64748b;">Crea banners sin escribir rutas técnicas. Elige categoría, producto o link externo.</p>
        </div>
        <a class="btn green" href="slider_form.php">+ Nuevo carrusel</a>
    </div>

    <?php if(isset($_GET["ok"])): ?>
        <div style="background:#dcfce7;color:#166534;padding:14px;border-radius:12px;margin-bottom:18px;font-weight:bold;">Carrusel guardado correctamente.</div>
    <?php endif; ?>

    <?php if(count($sliders) === 0): ?>
        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:24px;text-align:center;">No hay carruseles creados todavía.</div>
    <?php endif; ?>

    <?php foreach($sliders as $s): ?>
        <div class="slider-card">
            <div class="slider-img">
                <?php if(!empty($s["imagen"])): ?><img src="../<?= h($s["imagen"]) ?>"><?php else: ?><?= h(substr($s["titulo"] ?? "M", 0, 1)) ?><?php endif; ?>
            </div>
            <div class="slider-info">
                <h3><?= h($s["titulo"] ?? "") ?> <span style="color:<?= h($s["color_resaltado"] ?? "#0057d9") ?>"><?= h($s["titulo_resaltado"] ?? "") ?></span></h3>
                <p><?= h($s["subtitulo"] ?? "") ?></p>
                <div style="margin-top:8px;"><span class="status <?= !empty($s["activo"]) ? "on" : "off" ?>"><?= !empty($s["activo"]) ? "Activo" : "Inactivo" ?></span><span style="margin-left:10px;color:#64748b;font-size:13px;">Orden: <?= (int)($s["orden"] ?? 0) ?></span></div>
                <div class="slider-url">Botón: <?= h($s["texto_boton"] ?? "") ?> → <?= h($s["url_boton"] ?? "") ?></div>
            </div>
            <div class="slider-actions">
                <a class="btn blue" href="slider_form.php?id=<?= (int)$s["id"] ?>">Editar</a>
                <a class="btn gray" target="_blank" href="../<?= h($s["url_boton"] ?: "tienda_visual_v3.php") ?>">Probar</a>
                <a class="btn red" href="sliders.php?delete=<?= (int)$s["id"] ?>" onclick="return confirm('¿Eliminar este carrusel?')">Eliminar</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php admin_footer(); ?>

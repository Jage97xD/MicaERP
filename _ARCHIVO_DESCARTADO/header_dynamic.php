<?php
require_once __DIR__ . "/../config/configuracion.php";
$builder = builderComponentes($pdo);
?>
<div class="dynamic-header" style="position:relative;height:220px;background:#fff;">
<?php foreach($builder as $b): if(!$b['visible']) continue; ?>
    <?php $tag = $b['url'] ? 'a' : 'div'; ?>
    <<?= $tag ?>
        <?php if($b['url']): ?>href="<?= htmlspecialchars($b['url']) ?>"<?php endif; ?>
        style="
            position:absolute;
            left:<?= (int)$b['x'] ?>px;
            top:<?= (int)$b['y'] ?>px;
            width:<?= (int)$b['ancho'] ?>px;
            height:<?= (int)$b['alto'] ?>px;
            background:<?= htmlspecialchars($b['color_fondo'] ?: 'transparent') ?>;
            color:<?= htmlspecialchars($b['color_texto'] ?: '#111827') ?>;
            display:flex;
            align-items:center;
            justify-content:center;
            text-decoration:none;
            font-weight:bold;
            border-radius:10px;
        ">
        <?= htmlspecialchars($b['texto']) ?>
    </<?= $tag ?>>
<?php endforeach; ?>
</div>

<?php if(count($bloques) > 0): ?>
<section class="blocks">
    <?php foreach($bloques as $b): ?>
        <?php if($b["tipo"] === "html"): ?>
            <?= $b["contenido"] ?>
        <?php elseif($b["tipo"] === "banner"): ?>
            <div class="block-banner">
                <h2><?= q($b["titulo"]) ?></h2>
                <p><?= q($b["contenido"]) ?></p>
            </div>
        <?php else: ?>
            <div class="block-info">
                <h3><?= q($b["titulo"]) ?></h3>
                <p><?= q($b["contenido"]) ?></p>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</section>
<?php endif; ?>

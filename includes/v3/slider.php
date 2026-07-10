<section class="v3-hero">
    <?php foreach($sliders as $i => $s): ?>
        <?php $slideSlug = slugDesdeUrl($s['url_boton'] ?? ''); ?>

        <article
            class="v3-slide <?= $i === 0 ? 'active' : '' ?>"
            data-slug="<?= h($slideSlug) ?>"
            style="
                background:
                linear-gradient(120deg, <?= h($s['color_inicio']) ?>, <?= h($s['color_fin']) ?>)
                <?= !empty($s['imagen']) ? ', url('.h($s['imagen']).')' : '' ?>;
            "
        >
            <div class="v3-hero-content">
                <h1>
                    <?= h($s['titulo']) ?><br>
                    <span style="color:<?= h($s['color_resaltado']) ?>"><?= h($s['titulo_resaltado']) ?></span>
                </h1>
                <p><?= h($s['subtitulo']) ?></p>
                <a href="<?= h($s['url_boton'] ?: '#') ?>"><?= h($s['texto_boton'] ?: 'Ver más') ?></a>
            </div>
        </article>
    <?php endforeach; ?>

    <button class="v3-arrow left" type="button" onclick="moverSlide(-1, true)">‹</button>
    <button class="v3-arrow right" type="button" onclick="moverSlide(1, true)">›</button>

    <div class="v3-dots">
        <?php foreach($sliders as $i => $s): ?>
            <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" onclick="mostrarSlide(<?= $i ?>, true)"></button>
        <?php endforeach; ?>
    </div>
</section>

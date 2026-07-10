<section class="hero">
    <?php foreach($sliders as $i => $s): ?>
        <article
            class="slide <?= $i === 0 ? 'active' : '' ?>"
            style="background:
                linear-gradient(120deg, <?= q($s['color_inicio']) ?>, <?= q($s['color_fin']) ?>)
                <?= !empty($s['imagen']) ? ', url(../'.q($s['imagen']).')' : '' ?>;"
        >
            <div class="hero-content">
                <h1>
                    <?= q($s["titulo"]) ?><br>
                    <span style="color:<?= q($s["color_resaltado"]) ?>"><?= q($s["titulo_resaltado"]) ?></span>
                </h1>
                <p><?= q($s["subtitulo"]) ?></p>
                <a href="<?= q($s["url_boton"] ?: '#') ?>"><?= q($s["texto_boton"] ?: 'Ver más') ?></a>
            </div>
        </article>
    <?php endforeach; ?>

    <button class="hero-arrow left" onclick="moverSlide(-1)">‹</button>
    <button class="hero-arrow right" onclick="moverSlide(1)">›</button>

    <div class="hero-dots">
        <?php foreach($sliders as $i => $s): ?>
            <button class="<?= $i === 0 ? 'active' : '' ?>" onclick="mostrarSlide(<?= $i ?>)"></button>
        <?php endforeach; ?>
    </div>
</section>

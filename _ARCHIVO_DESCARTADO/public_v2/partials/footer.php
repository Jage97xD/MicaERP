<footer class="footer-public">
    <div>
        <h3>Mica Store</h3>
        <p>Catálogo online de productos y servicios. Atención por WhatsApp y cotizaciones.</p>
    </div>

    <div>
        <h3>Categorías</h3>
        <?php foreach($categorias as $cat): ?>
            <a href="index.php?categoria=<?= q($cat["slug"]) ?>"><?= q($cat["nombre"]) ?></a>
        <?php endforeach; ?>
    </div>

    <div>
        <h3>Contacto</h3>
        <p>WhatsApp: +51 920 137 707</p>
    </div>
</footer>

<nav class="menu-main">
    <a href="index.php" class="<?= empty($categoria) ? 'active' : '' ?>">Inicio</a>
    <a href="#productos">Catálogo</a>

    <?php foreach($categorias as $cat): ?>
        <a href="index.php?categoria=<?= q($cat["slug"]) ?>" class="<?= $categoria === $cat["slug"] ? 'active' : '' ?>">
            <?= q($cat["nombre"]) ?>
        </a>
    <?php endforeach; ?>
</nav>

<header class="header-main">
    <a class="brand-public" href="index.php">
        <span>M</span>
        <strong>MICA<br>STORE</strong>
    </a>

    <form class="search-main" method="GET" action="index.php">
        <input name="buscar" value="<?= q($buscar) ?>" placeholder="Buscar productos, marcas y más...">
        <button>🔍</button>
    </form>

    <div class="header-actions">
        <a href="#">Iniciar sesión</a>
        <a href="../cotizacion_mysql.php">🛒 Cotización <strong id="contadorCarrito">0</strong></a>
        <a class="wa" href="https://wa.me/51920137707" target="_blank">💬 WhatsApp</a>
    </div>
</header>

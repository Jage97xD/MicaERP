<!-- Modal Login Cliente -->
<div class="mica-login-overlay" id="micaLoginOverlay" aria-hidden="true">
    <div class="mica-login-modal">
        <button class="mica-login-close" type="button" onclick="cerrarLoginModal()">×</button>

        <div class="mica-login-brand">
            <span>M</span>
            <strong>Mica Store</strong>
        </div>

        <h2>Inicia sesión para cotizar</h2>
        <p>Usa tu cuenta para revisar tus cotizaciones, favoritos e historial.</p>

        <div id="micaLoginMsg"></div>

        <form id="micaLoginForm">
            <label>Correo electrónico</label>
            <input type="email" name="correo" placeholder="Ingresa tu correo electrónico" required>

            <label>Contraseña</label>
            <div class="mica-password-box">
                <input type="password" name="password" id="micaLoginPassword" placeholder="Ingresa tu contraseña" required>
                <button type="button" onclick="toggleMicaPassword()">👁</button>
            </div>

            <button class="mica-login-submit" type="submit">Ingresar</button>
        </form>

        <div class="mica-login-footer">
            ¿Aún no tienes cuenta?
            <a href="cliente/cliente_registro.php">Regístrate</a>
        </div>
    </div>
</div>
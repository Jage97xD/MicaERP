/* Header cliente PRO */
function micaPrimeraPalabra(nombre){
    nombre = (nombre || "").trim();
    if(!nombre) return "Cliente";
    return nombre.split(/\s+/)[0];
}

function micaIniciales(nombre){
    nombre = (nombre || "C").trim();
    let partes = nombre.split(/\s+/);
    let ini = (partes[0]?.[0] || "C");
    if(partes.length > 1) ini += partes[1][0] || "";
    return ini.toUpperCase();
}

function micaCrearHeaderCliente(cliente){
    const nombreCorto = micaPrimeraPalabra(cliente.nombre);
    const iniciales = micaIniciales(cliente.nombre);

    return `
        <div class="mica-user-menu" id="micaUserMenu">
            <span class="mica-user-icon">🏠</span>

<div class="mica-user-text">
    <small>Hola,</small>
    <strong>${nombreCorto}</strong>
</div>

<span class="mica-user-arrow">▼</span>

            <div class="mica-user-dropdown" id="micaUserDropdown">
                <div class="mica-user-small">Conectado como<br><strong>${cliente.correo || cliente.nombre}</strong></div>
                <div class="mica-user-divider"></div>
                <a href="cliente/mi_cuenta.php">👤 Mi cuenta</a>
                <a href="cliente/mi_cuenta.php#cotizaciones">📄 Mis cotizaciones</a>
                <a href="cliente/favoritos.php">❤️ Favoritos</a>
                <div class="mica-user-divider"></div>
                <a class="logout" href="cliente/cliente_logout.php">🚪 Cerrar sesión</a>
            </div>
        </div>
    `;
}

function micaReemplazarLoginPorMenu(cliente){
    const links = document.querySelectorAll('a[href*="cliente_login.php"], a[href*="login.php"]');

    links.forEach(a => {
        const wrapper = document.createElement("div");
        wrapper.innerHTML = micaCrearHeaderCliente(cliente);
        const menu = wrapper.firstElementChild;

        // Mantener el menú del cliente dentro del flujo flexible del header.
        // No copiamos left/top del constructor porque en el header público esos valores se anulan
        // para evitar desbordes y superposiciones con el buscador.
        a.replaceWith(menu);
    });
}

document.addEventListener("click", function(e){
    const menu = e.target.closest("#micaUserMenu");
    const dropdown = document.getElementById("micaUserDropdown");

    if(menu){
        e.stopPropagation();
        if(dropdown) dropdown.classList.toggle("active");
        return;
    }

    if(dropdown) dropdown.classList.remove("active");
});

function micaLoginActualizarHeader(nombre){
    fetch("cliente/session_api.php", {cache:"no-store"})
        .then(r => r.json())
        .then(data => {
            if(data.ok && data.logueado && data.cliente){
                micaReemplazarLoginPorMenu(data.cliente);
            }else{
                micaReemplazarLoginPorMenu({nombre:nombre, correo:""});
            }
        })
        .catch(() => micaReemplazarLoginPorMenu({nombre:nombre, correo:""}));
}

document.addEventListener("DOMContentLoaded", function(){
    fetch("cliente/session_api.php", {cache:"no-store"})
        .then(r => r.json())
        .then(data => {
            if(data.ok && data.logueado && data.cliente){
                micaReemplazarLoginPorMenu(data.cliente);
            }
        })
        .catch(()=>{});
});

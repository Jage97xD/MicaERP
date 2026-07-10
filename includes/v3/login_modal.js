let micaLoginDestino = null;

function micaCartClearAll(){
    try{
        Object.keys(localStorage).forEach(function(key){
            if(key.startsWith("mica_cart_")){
                localStorage.removeItem(key);
            }
        });
    }catch(e){}

    const contador = document.getElementById("contadorCarrito");
    if(contador){
        contador.textContent = "0";
    }
}

function abrirLoginModal(destino = null){
    micaLoginDestino = destino;
    const overlay = document.getElementById("micaLoginOverlay");
    const msg = document.getElementById("micaLoginMsg");

    if(msg) msg.innerHTML = "";

    if(overlay){
        overlay.classList.add("active");
        overlay.setAttribute("aria-hidden", "false");
    }
}

function cerrarLoginModal(){
    const overlay = document.getElementById("micaLoginOverlay");

    if(overlay){
        overlay.classList.remove("active");
        overlay.setAttribute("aria-hidden", "true");
    }
}

function toggleMicaPassword(){
    const input = document.getElementById("micaLoginPassword");
    if(!input) return;
    input.type = input.type === "password" ? "text" : "password";
}

function micaLoginActualizarHeader(nombre){
    if(typeof micaReemplazarLoginPorMenu === "function"){
        fetch("cliente/session_api.php", {cache:"no-store"})
            .then(r => r.json())
            .then(data => {
                if(data.ok && data.logueado && data.cliente){
                    micaReemplazarLoginPorMenu(data.cliente);
                }
            })
            .catch(()=>{});
        return;
    }

    const links = document.querySelectorAll('a[href*="cliente_login.php"], a[href*="login.php"]');
    links.forEach(a => {
        a.href = "cliente/mi_cuenta.php";
        a.innerHTML = "Hola, <strong>" + nombre + "</strong>";
    });
}

async function micaConsultarSesion(){
    try{
        const res = await fetch("cliente/session_api.php", {cache:"no-store"});
        const data = await res.json();

        if(data.ok && data.logueado && data.cliente){
            micaLoginActualizarHeader(data.cliente.nombre);
        }
    }catch(e){}
}

document.addEventListener("click", async function(e){
    const link = e.target.closest("a");
    if(!link) return;

    const href = link.getAttribute("href") || "";

    if(href.includes("cliente/cliente_login.php") || href === "login.php" || href.includes("/login.php")){
        e.preventDefault();
        abrirLoginModal();
        return;
    }

    if(href.includes("cliente/cliente_logout.php")){
        e.preventDefault();

        try{
            await fetch("cliente/logout_api.php", {
                method:"POST",
                cache:"no-store"
            });
        }catch(err){}

        micaCartClearAll();
        window.location.href = "tienda_visual_v3.php";
        return;
    }
});

document.addEventListener("DOMContentLoaded", function(){
    const form = document.getElementById("micaLoginForm");

    if(form){
        form.addEventListener("submit", async function(e){
            e.preventDefault();

            const msg = document.getElementById("micaLoginMsg");
            const fd = new FormData(form);

            try{
                const res = await fetch("cliente/login_api.php", {
                    method:"POST",
                    body:fd,
                    cache:"no-store"
                });

                const data = await res.json();

                if(!data.ok){
                    msg.innerHTML = '<div class="mica-login-error">'+data.mensaje+'</div>';
                    return;
                }

                msg.innerHTML = '<div class="mica-login-ok">Sesión iniciada correctamente.</div>';

                if(data.cliente && data.cliente.nombre){
                    micaLoginActualizarHeader(data.cliente.nombre);
                }

                setTimeout(() => {
                    cerrarLoginModal();

                    if(micaLoginDestino){
                        window.location.href = micaLoginDestino;
                    }else{
                        window.location.reload();
                    }
                }, 500);

            }catch(err){
                msg.innerHTML = '<div class="mica-login-error">No se pudo conectar con el servidor.</div>';
            }
        });
    }

    const overlay = document.getElementById("micaLoginOverlay");

    if(overlay){
        overlay.addEventListener("click", function(e){
            if(e.target === overlay){
                cerrarLoginModal();
            }
        });
    }

    micaConsultarSesion();
});

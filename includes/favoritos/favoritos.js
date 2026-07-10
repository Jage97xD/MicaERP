function mostrarToastFavorito(texto, tipo = "ok"){
    let t = document.createElement("div");
    t.className = "fav-toast " + tipo;
    t.textContent = texto;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2300);
}

async function toggleFavorito(productoId, btn){
    const form = new FormData();
    form.append("producto_id", productoId);

    try{
        const res = await fetch("cliente/favorito_toggle.php", {
            method: "POST",
            body: form
        });

        const data = await res.json();

        if(!data.ok && data.login === false){
            mostrarToastFavorito("Debes iniciar sesión para guardar favoritos.", "error");
            setTimeout(() => {
                window.location.href = "cliente/cliente_login.php";
            }, 900);
            return;
        }

        if(!data.ok){
            mostrarToastFavorito(data.mensaje || "No se pudo actualizar favorito.", "error");
            return;
        }

        if(data.favorito){
            btn.classList.add("activo");
            btn.innerHTML = "❤️";
        }else{
            btn.classList.remove("activo");
            btn.innerHTML = "♡";
        }

        actualizarContadorFavoritos();
        mostrarToastFavorito(data.mensaje || "Favoritos actualizado.");
    }catch(e){
        mostrarToastFavorito("Error al conectar con favoritos.", "error");
    }
}

async function actualizarContadorFavoritos(){
    try{
        const res = await fetch("cliente/favoritos_count.php");
        const data = await res.json();
        const el = document.getElementById("contadorFavoritos");
        if(el && data.ok){
            el.textContent = data.total;
        }
    }catch(e){}
}

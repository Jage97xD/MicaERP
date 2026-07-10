let slideActual = 0;
let intervaloCarrusel = null;
let MICA_CART_KEY = "mica_cart_invitado";

const slides = document.querySelectorAll(".v3-slide");
const dots = document.querySelectorAll(".v3-dots button");
const categoriaActual = window.MICA_CATEGORIA_ACTUAL || "";

async function resolverCartKey(){
    try{
        const res = await fetch("cliente/session_api.php", {cache:"no-store"});
        const data = await res.json();

        if(data.ok && data.cart_key){
            MICA_CART_KEY = data.cart_key;
        }
    }catch(e){
        MICA_CART_KEY = "mica_cart_invitado";
    }
}

function getCart(){
    return JSON.parse(localStorage.getItem(MICA_CART_KEY) || "[]");
}

function setCart(cart){
    localStorage.setItem(MICA_CART_KEY, JSON.stringify(cart));
}

function marcarMenuPorSlug(slug){
    document.querySelectorAll(".v3-menu a").forEach(a => {
        const menuSlug = a.dataset.slug || "";
        a.classList.remove("active");

        if(slug === "" && menuSlug === ""){
            a.classList.add("active");
        }

        if(slug !== "" && menuSlug === slug){
            a.classList.add("active");
        }
    });
}

function mostrarSlide(i, reiniciar=false){
    if(slides.length === 0) return;

    slides.forEach(s => s.classList.remove("active"));
    dots.forEach(d => d.classList.remove("active"));

    slideActual = (i + slides.length) % slides.length;

    slides[slideActual].classList.add("active");
    if(dots[slideActual]) dots[slideActual].classList.add("active");

    const slug = slides[slideActual].dataset.slug || "";
    marcarMenuPorSlug(slug);

    if(reiniciar){
        reiniciarCarrusel();
    }
}

function moverSlide(dir, reiniciar=false){
    mostrarSlide(slideActual + dir, reiniciar);
}

function iniciarCarrusel(){
    if(intervaloCarrusel) clearInterval(intervaloCarrusel);
    intervaloCarrusel = setInterval(() => {
        moverSlide(1, false);
    }, 6000);
}

function reiniciarCarrusel(){
    iniciarCarrusel();
}

function iniciarSlideSegunCategoria(){
    if(categoriaActual){
        let encontrado = false;

        slides.forEach((slide, index) => {
            if((slide.dataset.slug || "") === categoriaActual && !encontrado){
                slideActual = index;
                encontrado = true;
            }
        });
    }

    mostrarSlide(slideActual, false);
    iniciarCarrusel();
}

function actualizarContadorBuilder(){
    let carrito = getCart();
    let total = 0;

    carrito.forEach(i => total += Number(i.qty || 0));

    const c = document.getElementById("contadorCarrito");
    if(c) c.textContent = total;
}

function agregarCotizacion(id, nombre){
    let cart = getCart();
    const item = cart.find(p => Number(p.id) === Number(id));

    if(item){ item.qty++; }
    else{ cart.push({id, nombre, qty:1}); }

    setCart(cart);
    actualizarContadorBuilder();

    let toast = document.createElement("div");
    toast.className = "v3-toast";
    toast.textContent = "✅ Producto agregado a cotización";
    document.body.appendChild(toast);
    setTimeout(()=>toast.remove(), 2200);
}

resolverCartKey().then(() => {
    actualizarContadorBuilder();
    iniciarSlideSegunCategoria();
});


/* Menú V2 */
function toggleMenuV3(){
    const menu = document.getElementById("menuPrincipal");
    if(menu){
        menu.classList.toggle("open");
    }
}

function toggleCategoriasV3(e){
    if(e) e.stopPropagation();
    const dd = document.getElementById("categoriaDropdown");
    if(dd){
        dd.classList.toggle("open");
    }
}

document.addEventListener("click", function(e){
    const dd = document.getElementById("categoriaDropdown");
    const menu = document.getElementById("menuPrincipal");

    if(dd && !e.target.closest("#categoriaDropdown")){
        dd.classList.remove("open");
    }

    if(menu && !e.target.closest("#menuPrincipal") && window.innerWidth <= 900){
        menu.classList.remove("open");
    }
});

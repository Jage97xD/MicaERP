let slideActual = 0;
const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".hero-dots button");

function mostrarSlide(i){
    if(slides.length === 0) return;
    slides.forEach(s => s.classList.remove("active"));
    dots.forEach(d => d.classList.remove("active"));
    slideActual = (i + slides.length) % slides.length;
    slides[slideActual].classList.add("active");
    if(dots[slideActual]) dots[slideActual].classList.add("active");
}

function moverSlide(dir){
    mostrarSlide(slideActual + dir);
}

setInterval(() => moverSlide(1), 6000);

function actualizarContador(){
    let cart = JSON.parse(localStorage.getItem("mica_cart_mysql") || "[]");
    let total = cart.reduce((s, p) => s + Number(p.qty || 0), 0);
    const c = document.getElementById("contadorCarrito");
    if(c) c.textContent = total;
}

function agregarCotizacion(id, nombre){
    let cart = JSON.parse(localStorage.getItem("mica_cart_mysql") || "[]");
    const item = cart.find(p => Number(p.id) === Number(id));

    if(item){ item.qty++; }
    else{ cart.push({id, nombre, qty:1}); }

    localStorage.setItem("mica_cart_mysql", JSON.stringify(cart));
    actualizarContador();

    let toast = document.createElement("div");
    toast.className = "toast";
    toast.textContent = "✅ Producto agregado a cotización";
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2200);
}

actualizarContador();

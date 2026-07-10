
const grid = document.querySelector("[data-grid]");
const buscador = document.getElementById("buscador");
const btnBuscar = document.querySelector(".search-box button");
const noResults = document.querySelector(".no-results");
const filtros = document.querySelectorAll(".filter-btn");

function money(n){
    if(!n || n === 0) return "";
    return "S/ " + Number(n).toLocaleString("es-PE", {minimumFractionDigits:2, maximumFractionDigits:2});
}

function cardProducto(p){
    const badgeClass=p.oferta?"offer":(p.tag==="Más vendido"?"best":"");
    const old=p.precioAnterior?`<span class="old-price">${money(p.precioAnterior)}</span>`:"";
    return `<article class="product-card" data-cat="${p.subcategoria}" data-linea="${p.categoria}">
    <a class="product-img-wrap" href="producto.html?id=${p.id}"><span class="badge ${badgeClass}">${p.tag||"Nuevo"}</span><img src="${p.imagen}" alt="${p.nombre}"></a>
    <small>${p.linea} · ${p.subcategoria}</small><h3><a href="producto.html?id=${p.id}">${p.nombre}</a></h3>
    <div class="price">${money(p.precio)} ${old}</div><div class="stock">✔ Stock referencial: ${p.stock}</div>
    <a class="whatsapp-btn" href="https://wa.me/51920137707?text=Hola,%20quiero%20consultar%20por%20${encodeURIComponent(p.nombre)}" target="_blank">Consultar por WhatsApp</a>
    <button class="cart-btn" onclick="agregarCarrito(${p.id})">Agregar a cotización</button></article>`;
}

function getParams(){
    return new URLSearchParams(window.location.search);
}

function productoFiltrados(){
    const params = getParams();
    const pagina = document.body.dataset.page;
    const sub = params.get("sub");
    const cat = params.get("cat");
    let lista = [...productos];

    if(pagina && pagina !== "index" && pagina !== "ofertas"){
        lista = lista.filter(p => p.categoria === pagina);
    }

    if(pagina === "ofertas"){
        lista = lista.filter(p => p.oferta);
    }

    if(cat){
        lista = lista.filter(p => p.categoria === cat);
    }

    if(sub){
        lista = lista.filter(p => p.subcategoria === sub);
    }

    return lista;
}

function render(lista){
    if(!grid) return;
    grid.innerHTML = lista.map(cardProducto).join("") + `<div class="no-results">No encontramos productos con esa búsqueda o filtro.</div>`;
}

function aplicarBusquedaYFiltro(){
    if(!grid) return;
    const texto = buscador ? buscador.value.toLowerCase().trim() : "";
    const filtroActivo = document.querySelector(".filter-btn.active");
    const filtro = filtroActivo ? filtroActivo.dataset.filter : "todos";
    let lista = productoFiltrados();

    if(filtro !== "todos"){
        lista = lista.filter(p => p.subcategoria === filtro || p.categoria === filtro);
    }

    if(texto){
        lista = lista.filter(p =>
            p.nombre.toLowerCase().includes(texto) ||
            p.marca.toLowerCase().includes(texto) ||
            p.linea.toLowerCase().includes(texto) ||
            p.subcategoria.toLowerCase().includes(texto)
        );
    }

    render(lista);

    const nr = document.querySelector(".no-results");
    if(nr) nr.style.display = lista.length === 0 ? "block" : "none";
}

if(grid){
    aplicarBusquedaYFiltro();
}

if(buscador) buscador.addEventListener("keyup", aplicarBusquedaYFiltro);
if(btnBuscar) btnBuscar.addEventListener("click", () => {
    aplicarBusquedaYFiltro();
    const s = document.getElementById("productos");
    if(s) s.scrollIntoView({behavior:"smooth"});
});
filtros.forEach(btn => btn.addEventListener("click", () => {
    filtros.forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
    aplicarBusquedaYFiltro();
}));

const overlay = document.querySelector(".overlay");
const megaMenu = document.querySelector(".mega-menu");
const openMenu = document.querySelector(".category-toggle");
const closeMenu = document.querySelector(".close-menu");
const tabs = document.querySelectorAll(".mega-tab");
const panels = document.querySelectorAll(".mega-panel");
function abrirMega(){ if(megaMenu && overlay){megaMenu.classList.add("open");overlay.classList.add("show");}}
function cerrarMega(){ if(megaMenu && overlay){megaMenu.classList.remove("open");overlay.classList.remove("show");}}
if(openMenu) openMenu.addEventListener("click", abrirMega);
if(closeMenu) closeMenu.addEventListener("click", cerrarMega);
if(overlay) overlay.addEventListener("click", cerrarMega);
tabs.forEach(tab => tab.addEventListener("click", () => {
    tabs.forEach(t => t.classList.remove("active"));
    panels.forEach(p => p.classList.remove("active"));
    tab.classList.add("active");
    const panel = document.querySelector(`[data-panel="${tab.dataset.target}"]`);
    if(panel) panel.classList.add("active");
}));
document.addEventListener("keydown", e => { if(e.key === "Escape") cerrarMega(); });

const backTop = document.querySelector(".back-top");
window.addEventListener("scroll", () => { if(backTop) backTop.classList.toggle("show", window.scrollY > 400); });
if(backTop) backTop.addEventListener("click", () => window.scrollTo({top:0, behavior:"smooth"}));


/* Login demo modal */
const loginButtons = document.querySelectorAll(".login-demo");
const loginModal = document.querySelector(".login-modal");
const loginClose = document.querySelector(".login-close");

loginButtons.forEach(btn => btn.addEventListener("click", (e) => {
    e.preventDefault();
    if(loginModal) loginModal.classList.add("show");
}));

if(loginClose){
    loginClose.addEventListener("click", () => loginModal.classList.remove("show"));
}

if(loginModal){
    loginModal.addEventListener("click", (e) => {
        if(e.target === loginModal) loginModal.classList.remove("show");
    });
}

function obtenerCarrito(){return JSON.parse(localStorage.getItem("mica_cart")||"[]")}
function guardarCarrito(cart){localStorage.setItem("mica_cart",JSON.stringify(cart));actualizarContadorCarrito()}
function agregarCarrito(id){const cart=obtenerCarrito();const item=cart.find(x=>x.id===id);if(item)item.qty+=1;else cart.push({id:id,qty:1});guardarCarrito(cart);mostrarToast("Producto agregado a la cotización")}
function actualizarContadorCarrito(){const c=obtenerCarrito().reduce((s,x)=>s+x.qty,0);document.querySelectorAll(".cart-count").forEach(e=>e.textContent=c)}
actualizarContadorCarrito();
document.querySelectorAll("[data-home-line]").forEach(container=>{const linea=container.dataset.homeLine;const lista=productos.filter(p=>p.categoria===linea&&(p.nuevo||p.oferta||p.tag==="Más vendido")).slice(0,4);container.innerHTML=lista.map(cardProducto).join("")});

function mostrarToast(texto){
    let toast = document.querySelector(".toast");
    if(!toast){
        toast = document.createElement("div");
        toast.className = "toast";
        document.body.appendChild(toast);
    }
    toast.textContent = "✅ " + texto;
    toast.classList.add("show");
    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(()=>toast.classList.remove("show"), 2200);
}



/* Carrusel principal premium */
const carousel = document.querySelector(".carousel");
if(carousel){
    const track = carousel.querySelector(".carousel-track");
    const slides = [...carousel.querySelectorAll(".carousel-slide")];
    const dots = [...carousel.querySelectorAll(".carousel-dot")];
    const prev = carousel.querySelector(".carousel-prev");
    const next = carousel.querySelector(".carousel-next");
    let index = 0;
    let autoSlide = null;
    let touchStartX = 0;
    let touchEndX = 0;

    function goTo(i){
        index = (i + slides.length) % slides.length;
        track.style.transform = `translateX(-${index * 100}%)`;
        dots.forEach((d,n)=>d.classList.toggle("active", n === index));
        slides.forEach((s,n)=>s.classList.toggle("is-active", n === index));

        const theme = slides[index].dataset.theme || "tecnologia";
        document.body.classList.remove(
            "theme-dynamic-tecnologia",
            "theme-dynamic-ferreteria",
            "theme-dynamic-hogar",
            "theme-dynamic-belleza",
            "theme-dynamic-ofertas"
        );
        document.body.classList.add("theme-dynamic-" + theme);

        reiniciarProgreso();
    }

    function startCarousel(){
        clearInterval(autoSlide);
        autoSlide = setInterval(()=>goTo(index + 1), 5000);
        reiniciarProgreso();
    }

    function stopCarousel(){
        clearInterval(autoSlide);
    }

    function manualGoTo(i){
        goTo(i);
        startCarousel();
    }

    function reiniciarProgreso(){
        const bar = carousel.querySelector(".carousel-progress-bar");
        if(!bar) return;
        bar.style.animation = "none";
        void bar.offsetWidth;
        bar.style.animation = "carouselProgress 5s linear forwards";
    }

    if(prev) prev.addEventListener("click",()=>manualGoTo(index - 1));
    if(next) next.addEventListener("click",()=>manualGoTo(index + 1));
    dots.forEach((d,n)=>d.addEventListener("click",()=>manualGoTo(n)));

    carousel.addEventListener("mouseenter", stopCarousel);
    carousel.addEventListener("mouseleave", startCarousel);

    carousel.addEventListener("touchstart", e=>{
        touchStartX = e.changedTouches[0].screenX;
        stopCarousel();
    }, {passive:true});

    carousel.addEventListener("touchend", e=>{
        touchEndX = e.changedTouches[0].screenX;
        const diff = touchStartX - touchEndX;
        if(Math.abs(diff) > 50){
            if(diff > 0) manualGoTo(index + 1);
            else manualGoTo(index - 1);
        }else{
            startCarousel();
        }
    }, {passive:true});

    goTo(0);
    startCarousel();
}


const contenedor = document.getElementById("detalle-producto");
const relacionadosGrid = document.getElementById("relacionados");

function money(n){
    if(!n || n === 0) return "";
    return "S/ " + Number(n).toLocaleString("es-PE", {minimumFractionDigits:2, maximumFractionDigits:2});
}

function renderDetalle(){
    const params = new URLSearchParams(window.location.search);
    const id = Number(params.get("id"));
    const p = productos.find(item => item.id === id) || productos[0];

    document.title = `${p.nombre} - Mica Store`;

    if(p.categoria === "belleza") document.body.classList.add("theme-belleza");
    if(p.categoria === "hogar") document.body.classList.add("theme-hogar");
    if(p.categoria === "ferreteria") document.body.classList.add("theme-ferreteria");

    const old = p.precioAnterior ? `<span class="detail-old">${money(p.precioAnterior)}</span>` : "";
    const badgeClass = p.oferta ? "offer" : (p.tag === "Más vendido" ? "best" : "");

    contenedor.innerHTML = `
    <div class="breadcrumb"><a href="index.html">Inicio</a> / <a href="${p.categoria}.html">${p.linea}</a> / ${p.nombre}</div>
    <section class="detail-card">
        <div class="detail-image">
            <span class="badge ${badgeClass}">${p.tag}</span>
            <img src="${p.imagen}" alt="${p.nombre}">
        </div>
        <div class="detail-info">
            <span class="detail-line">${p.linea}</span>
            <h1>${p.nombre}</h1>
            <div class="detail-price">${money(p.precio)} ${old}</div>
            <p class="detail-description">${p.descripcion}</p>
            <div class="stock">✔ Stock referencial disponible: ${p.stock} unidades</div>
            <div class="detail-actions">
                <a class="whatsapp-btn" href="https://wa.me/51920137707?text=Hola,%20quiero%20consultar%20por%20${encodeURIComponent(p.nombre)}" target="_blank">Consultar por WhatsApp</a>
                <a class="secondary-btn" href="${p.categoria}.html">Volver a ${p.linea}</a>
            </div>
        </div>
    </section>
    <section class="spec-grid">
        <div class="spec-box">
            <h3>Características referenciales</h3>
            <ul>${p.caracteristicas.map(c => `<li>${c}</li>`).join("")}</ul>
        </div>
        <div class="spec-box">
            <h3>Información de compra</h3>
            <ul>
                <li>Precio y stock referencial.</li>
                <li>Confirmar disponibilidad por WhatsApp.</li>
                <li>Entrega coordinada desde Mercado La Chacra.</li>
                <li>Atención de lunes a sábado.</li>
            </ul>
        </div>
    </section>`;

    const relacionados = productos.filter(x => x.categoria === p.categoria && x.id !== p.id).slice(0,4);
    relacionadosGrid.innerHTML = relacionados.map(x => `
    <article class="product-card">
        <a class="product-img-wrap" href="producto.html?id=${x.id}">
            <span class="badge ${x.oferta ? "offer" : ""}">${x.tag}</span>
            <img src="${x.imagen}" alt="${x.nombre}">
        </a>
        <small>${x.linea}</small>
        <h3><a href="producto.html?id=${x.id}">${x.nombre}</a></h3>
        <div class="price">${money(x.precio)}</div>
        <a class="whatsapp-btn" href="https://wa.me/51920137707?text=Hola,%20quiero%20consultar%20por%20${encodeURIComponent(x.nombre)}" target="_blank">Consultar</a>
    </article>`).join("");
}

renderDetalle();

<?php
require_once "config/db.php";
if(session_status() === PHP_SESSION_NONE){ session_start(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi cotización - Mica Store</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#edf4fb;font-family:Arial,Helvetica,sans-serif;color:#07162f}
a{text-decoration:none;color:inherit}
.top{background:#07162f;color:white;height:38px;display:flex;align-items:center;justify-content:space-between;padding:0 40px;font-weight:bold;font-size:14px}
.header{height:110px;background:white;display:flex;align-items:center;justify-content:space-between;padding:0 50px;box-shadow:0 2px 12px rgba(15,23,42,.06)}
.brand{display:flex;align-items:center;gap:12px;font-size:28px;font-weight:900}
.brand span{width:55px;height:55px;border-radius:14px;background:#008ee6;color:white;display:flex;align-items:center;justify-content:center}
.header a.btn{background:#111827;color:white;padding:14px 20px;border-radius:12px;font-weight:bold}
.wrap{max-width:1250px;margin:35px auto;padding:0 25px}
.title{display:flex;align-items:center;justify-content:space-between;margin-bottom:25px}
.title h1{font-size:38px;margin:0}
.grid{display:grid;grid-template-columns:1.4fr .8fr;gap:28px}
.card{background:white;border-radius:22px;box-shadow:0 10px 28px rgba(15,23,42,.09);padding:24px}
.product-row{display:grid;grid-template-columns:95px 1fr 130px;gap:18px;align-items:center;border-bottom:1px solid #e5e7eb;padding:18px 0}
.product-row:last-child{border-bottom:0}
.product-row img{width:95px;height:95px;border-radius:14px;object-fit:cover;background:#f8fafc}
.product-row h3{margin:0 0 5px;font-size:20px}
.product-row small{color:#64748b}
.price{font-weight:900;color:#0057d9;font-size:20px}
.qty{display:flex;align-items:center;gap:8px;margin-top:10px}
.qty button{width:34px;height:34px;border:0;border-radius:10px;background:#e8eefb;font-weight:900;cursor:pointer}
.qty span{font-weight:bold;min-width:28px;text-align:center}
.remove{background:#fee2e2!important;color:#b91c1c!important;width:auto!important;padding:0 10px}
.summary-row{display:flex;justify-content:space-between;margin:14px 0;font-size:18px}
.total{font-size:28px;font-weight:900;color:#0057d9;border-top:1px solid #e5e7eb;padding-top:18px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
label{font-weight:bold}
input,select,textarea{padding:14px;border:1px solid #d8dee9;border-radius:12px;font-size:15px}
textarea{min-height:90px}
.actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:18px}
.actions button,.actions a{border:0;border-radius:14px;padding:16px 18px;text-align:center;font-weight:900;cursor:pointer}
.save{background:#22c55e;color:white}
.wa{background:#111827;color:white}
.empty{text-align:center;padding:55px;background:white;border-radius:22px}
.empty h2{font-size:28px}
.toast{position:fixed;top:25px;right:25px;background:#16a34a;color:white;padding:14px 20px;border-radius:12px;font-weight:bold;z-index:9999}
.error{background:#fee2e2;color:#991b1b;padding:14px;border-radius:12px;font-weight:bold;margin-bottom:14px}
.ok{background:#dcfce7;color:#166534;padding:14px;border-radius:12px;font-weight:bold;margin-bottom:14px}
@media(max-width:900px){.grid{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.header{height:auto;padding:20px;gap:15px;flex-direction:column}.product-row{grid-template-columns:80px 1fr}.product-row .right{grid-column:2}.top{display:none}}
</style>
</head>
<body>

<div class="top">
    <div>🚚 Envíos a todo el Perú &nbsp;&nbsp; 🛡️ Garantía en productos</div>
    <div>Facebook &nbsp;&nbsp; Instagram &nbsp;&nbsp; TikTok</div>
</div>

<header class="header">
    <a class="brand" href="tienda_visual_v3.php"><span>M</span> Mica Store</a>
    <a class="btn" href="tienda_visual_v3.php">Seguir comprando →</a>
</header>

<main class="wrap">
    <div class="title">
        <h1>Mi cotización</h1>
        <strong id="cantidadResumen">0 producto(s)</strong>
    </div>

    <div id="mensaje"></div>

    <section id="contenido"></section>
</main>

<script>
let productosBD = [];
const CLIENTE_ID = <?= isset($_SESSION['cliente_web_id']) ? (int)$_SESSION['cliente_web_id'] : 0 ?>;
const MICA_CART_KEY = CLIENTE_ID > 0 ? "mica_cart_cliente_" + CLIENTE_ID : "mica_cart_invitado";
let carrito = JSON.parse(localStorage.getItem(MICA_CART_KEY) || "[]");

function moneda(n){
    return "S/ " + Number(n || 0).toFixed(2);
}

function guardarCarrito(){
    localStorage.setItem(MICA_CART_KEY, JSON.stringify(carrito));
}

function idsCarrito(){
    return carrito.map(i => i.id).join(",");
}

async function cargarProductos(){
    if(carrito.length === 0){
        renderVacio();
        return;
    }

    const form = new FormData();
    form.append("ids", idsCarrito());

    const res = await fetch("cotizacion_productos_api.php", {method:"POST", body:form});
    const data = await res.json();

    productosBD = data.productos || [];
    render();
}

function productoPorId(id){
    return productosBD.find(p => Number(p.id) === Number(id));
}

function precioProducto(p){
    return Number(p.precio_oferta) > 0 ? Number(p.precio_oferta) : Number(p.precio);
}

function renderVacio(){
    document.getElementById("cantidadResumen").textContent = "0 producto(s)";
    document.getElementById("contenido").innerHTML = `
        <div class="empty">
            <h2>Tu cotización está vacía</h2>
            <p>Agrega productos desde el catálogo.</p>
            <br>
            <a class="wa" style="display:inline-block;padding:14px 22px;border-radius:14px;" href="tienda_visual_v3.php">Ir al catálogo</a>
        </div>
    `;
}

function render(){
    if(carrito.length === 0){
        renderVacio();
        return;
    }

    let total = 0;
    let cantidadTotal = 0;

    const rows = carrito.map(item => {
        const p = productoPorId(item.id);
        if(!p) return "";

        const precio = precioProducto(p);
        const subtotal = precio * Number(item.qty || 1);
        total += subtotal;
        cantidadTotal += Number(item.qty || 1);

        return `
        <div class="product-row">
            <img src="${p.imagen_principal || 'img/banners/slide-tecnologia.svg'}">
            <div>
                <h3>${p.nombre}</h3>
                <small>${p.categoria || ''} ${p.marca ? '· ' + p.marca : ''}</small>
                <div class="qty">
                    <button onclick="cambiarQty(${p.id}, -1)">-</button>
                    <span>${item.qty}</span>
                    <button onclick="cambiarQty(${p.id}, 1)">+</button>
                    <button class="remove" onclick="eliminarItem(${p.id})">Eliminar</button>
                </div>
            </div>
            <div class="right">
                <div class="price">${moneda(precio)}</div>
                <small>Subtotal</small>
                <div><strong>${moneda(subtotal)}</strong></div>
            </div>
        </div>`;
    }).join("");

    document.getElementById("cantidadResumen").textContent = cantidadTotal + " producto(s)";

    document.getElementById("contenido").innerHTML = `
    <div class="grid">
        <div class="card">
            <h2>Productos seleccionados</h2>
            ${rows}
        </div>

        <div class="card">
            <h2>Datos para cotizar</h2>

            <div class="form-grid">
                <div class="form-group full">
                    <label>Nombre completo *</label>
                    <input id="nombre" placeholder="Ejemplo: Jhon Alfaro">
                </div>

                <div class="form-group">
                    <label>DNI o RUC *</label>
                    <input id="documento" placeholder="12345678">
                </div>

                <div class="form-group">
                    <label>Celular *</label>
                    <input id="celular" placeholder="964546833">
                </div>

                <div class="form-group full">
                    <label>Correo</label>
                    <input id="correo" placeholder="correo@empresa.com">
                </div>

                <div class="form-group full">
                    <label>Tipo de entrega</label>
                    <select id="tipo_entrega">
                        <option>Recojo en tienda</option>
                        <option>Envío a domicilio</option>
                        <option>Envío a provincia</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Dirección</label>
                    <input id="direccion" placeholder="Dirección de entrega">
                </div>

                <div class="form-group">
                    <label>Distrito</label>
                    <input id="distrito">
                </div>

                <div class="form-group">
                    <label>Provincia</label>
                    <input id="provincia">
                </div>

                <div class="form-group full">
                    <label>Observaciones</label>
                    <textarea id="observaciones" placeholder="Detalle adicional"></textarea>
                </div>

                <div class="form-group full" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px;">
                    <strong>Preferencias comerciales</strong>
                    <label style="display:flex;gap:10px;align-items:flex-start;font-weight:normal;margin-top:12px;">
                        <input type="checkbox" id="acepta_ofertas" style="width:auto;margin-top:3px;">
                        <span>Deseo recibir ofertas, promociones y novedades de Mica Store.</span>
                    </label>
                    <label style="display:flex;gap:10px;align-items:flex-start;font-weight:normal;margin-top:10px;">
                        <input type="checkbox" id="acepta_contacto" style="width:auto;margin-top:3px;">
                        <span>Acepto que Mica Store me contacte para ofrecerme productos o servicios relacionados.</span>
                    </label>
                </div>
            </div>

            <div class="summary-row"><span>Subtotal</span><strong>${moneda(total)}</strong></div>
            <div class="summary-row"><span>Envío</span><strong>A coordinar</strong></div>
            <div class="summary-row total"><span>Total</span><strong>${moneda(total)}</strong></div>

            <div class="actions">
                <button class="save" onclick="guardarCotizacion()">Guardar cotización</button>
                <a class="wa" id="waDirecto" target="_blank" href="#">WhatsApp</a>
            </div>
        </div>
    </div>`;

    document.getElementById("waDirecto").href = crearWhatsAppLocal(total);
}

function cambiarQty(id, n){
    const item = carrito.find(i => Number(i.id) === Number(id));
    if(!item) return;

    item.qty = Number(item.qty || 1) + n;
    if(item.qty < 1) item.qty = 1;

    guardarCarrito();
    render();
}

function eliminarItem(id){
    carrito = carrito.filter(i => Number(i.id) !== Number(id));
    guardarCarrito();
    render();
}

function clienteForm(){
    return {
        nombre: document.getElementById("nombre")?.value || "",
        documento: document.getElementById("documento")?.value || "",
        celular: document.getElementById("celular")?.value || "",
        correo: document.getElementById("correo")?.value || "",
        tipo_entrega: document.getElementById("tipo_entrega")?.value || "",
        direccion: document.getElementById("direccion")?.value || "",
        distrito: document.getElementById("distrito")?.value || "",
        provincia: document.getElementById("provincia")?.value || "",
        observaciones: document.getElementById("observaciones")?.value || "",
        acepta_ofertas: document.getElementById("acepta_ofertas")?.checked ? 1 : 0,
        acepta_contacto: document.getElementById("acepta_contacto")?.checked ? 1 : 0
    };
}

function crearWhatsAppLocal(total){
    let texto = "Hola, deseo cotizar:%0A";
    carrito.forEach(item => {
        const p = productoPorId(item.id);
        if(!p) return;
        const subtotal = precioProducto(p) * Number(item.qty || 1);
        texto += `- ${p.nombre} x ${item.qty} = ${moneda(subtotal)}%0A`;
    });
    texto += `%0ATotal: ${moneda(total)}`;
    return "https://wa.me/51920137707?text=" + texto;
}

async function guardarCotizacion(){
    const cliente = clienteForm();

    if(!cliente.nombre || !cliente.documento || !cliente.celular){
        document.getElementById("mensaje").innerHTML = `<div class="error">Completa nombre, documento y celular.</div>`;
        window.scrollTo({top:0, behavior:"smooth"});
        return;
    }

    const payload = {
        cliente,
        items: carrito
    };

    const res = await fetch("guardar_cotizacion_pro.php", {
        method:"POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify(payload)
    });

    const data = await res.json();

    if(!data.ok){
        document.getElementById("mensaje").innerHTML = `<div class="error">${data.mensaje}</div>`;
        window.scrollTo({top:0, behavior:"smooth"});
        return;
    }

    document.getElementById("mensaje").innerHTML = `<div class="ok">✅ Cotización ${data.numero} guardada correctamente. Total: ${moneda(data.total)}</div>`;
    localStorage.removeItem(MICA_CART_KEY);
    carrito = [];
    window.open(data.whatsapp, "_blank");
    setTimeout(renderVacio, 900);
}

cargarProductos();
</script>

</body>
</html>


function money(n){
    if(!n||n===0)return"S/ 0.00";
    return"S/ "+Number(n).toLocaleString("es-PE",{minimumFractionDigits:2,maximumFractionDigits:2});
}
function obtenerCarrito(){return JSON.parse(localStorage.getItem("mica_cart")||"[]")}
function guardarCarrito(c){localStorage.setItem("mica_cart",JSON.stringify(c));renderCarrito()}
function calcularSubtotal(cart){
    return cart.reduce((s,i)=>{const p=productos.find(x=>x.id===i.id);return s+((p?.precio||0)*i.qty)},0);
}
function costoEnvio(){
    const tipo=document.getElementById("tipo-entrega")?.value;
    if(tipo==="envio-lima") return 10;
    return 0;
}
function renderCarrito(){
    const c=obtenerCarrito(),box=document.getElementById("cart-items"),totalEl=document.getElementById("cart-total");
    const envioEl=document.getElementById("cart-envio");
    const subtotalEl=document.getElementById("cart-subtotal");
    if(c.length===0){
        box.innerHTML='<div class="empty-cart">Tu cotización está vacía.</div>';
        if(subtotalEl) subtotalEl.textContent="S/ 0.00";
        if(envioEl) envioEl.textContent="S/ 0.00";
        totalEl.textContent="S/ 0.00";
        return;
    }
    let subtotal=0;
    box.innerHTML=c.map(i=>{
        const p=productos.find(x=>x.id===i.id);if(!p)return"";
        subtotal+=(p.precio||0)*i.qty;
        return`<div class="cart-item"><img src="${p.imagen}"><div><h3>${p.nombre}</h3><p>${p.linea} · ${p.subcategoria}</p><strong>${money(p.precio)}</strong><div class="qty-control"><button onclick="cambiarQty(${p.id},-1)">-</button><span>${i.qty}</span><button onclick="cambiarQty(${p.id},1)">+</button><button class="remove-item" onclick="eliminarItem(${p.id})">Eliminar</button></div></div><strong>${money((p.precio||0)*i.qty)}</strong></div>`
    }).join("");
    const envio=costoEnvio();
    if(subtotalEl) subtotalEl.textContent=money(subtotal);
    if(envioEl){
        const tipo=document.getElementById("tipo-entrega")?.value;
        envioEl.textContent= tipo==="envio-provincia" ? "A coordinar" : money(envio);
    }
    totalEl.textContent=money(subtotal+envio);
}
function cambiarQty(id,d){const c=obtenerCarrito(),i=c.find(x=>x.id===id);if(!i)return;i.qty+=d;guardarCarrito(i.qty<=0?c.filter(x=>x.id!==id):c)}
function eliminarItem(id){guardarCarrito(obtenerCarrito().filter(x=>x.id!==id))}
const tipoEntrega=document.getElementById("tipo-entrega"),bloqueDistrito=document.getElementById("bloque-distrito"),bloqueProvincia=document.getElementById("bloque-provincia"),notaEnvio=document.getElementById("nota-envio");
function actualizarEntrega(){
    if(!tipoEntrega)return;
    const tipo=tipoEntrega.value;
    bloqueDistrito.style.display=tipo==="envio-lima"?"block":"none";
    bloqueProvincia.style.display=tipo==="envio-provincia"?"block":"none";
    if(notaEnvio){
        if(tipo==="envio-lima") notaEnvio.textContent="Envío en Lima: se suma S/ 10.00 al total referencial.";
        else if(tipo==="envio-provincia") notaEnvio.textContent="Envío a provincia: costo a coordinar según peso, volumen y destino.";
        else notaEnvio.textContent="Recojo en tienda: sin costo de envío.";
    }
    renderCarrito();
}
if(tipoEntrega){tipoEntrega.addEventListener("change",actualizarEntrega);}
document.getElementById("checkout-form").addEventListener("submit",e=>{
    e.preventDefault();
    const c=obtenerCarrito();if(c.length===0){alert("Tu cotización está vacía.");return}
    const tipo=document.getElementById("tipo-entrega").value,distrito=document.getElementById("cliente-distrito").value,provincia=document.getElementById("cliente-provincia").value;
    let entregaTexto="Recojo en tienda";
    if(tipo==="envio-lima") entregaTexto="Envío en Lima - Distrito: "+distrito+" - Costo referencial S/ 10.00";
    if(tipo==="envio-provincia") entregaTexto="Envío a provincia - Destino: "+provincia+" - Costo a coordinar";
    let msg=`Hola, quiero realizar una cotización.%0A%0ACliente: ${encodeURIComponent(document.getElementById("cliente-nombre").value)}%0ADNI/RUC: ${encodeURIComponent(document.getElementById("cliente-dni").value)}%0ATeléfono: ${encodeURIComponent(document.getElementById("cliente-telefono").value)}%0AEntrega: ${encodeURIComponent(entregaTexto)}%0A%0AProductos:%0A`;
    c.forEach(i=>{const p=productos.find(x=>x.id===i.id);if(p)msg+=`- ${encodeURIComponent(p.nombre)} x ${i.qty} - ${encodeURIComponent(money(p.precio))}%0A`});
    msg+=`%0ASubtotal: ${encodeURIComponent(document.getElementById("cart-subtotal").textContent)}%0AEnvío: ${encodeURIComponent(document.getElementById("cart-envio").textContent)}%0ATotal referencial: ${encodeURIComponent(document.getElementById("cart-total").textContent)}%0AComentarios: ${encodeURIComponent(document.getElementById("cliente-comentarios").value||"Sin comentarios")}%0AIndíqueme el total final y datos para depósito/transferencia.`;
    window.open(`https://wa.me/51920137707?text=${msg}`,"_blank")
});
renderCarrito();actualizarEntrega();

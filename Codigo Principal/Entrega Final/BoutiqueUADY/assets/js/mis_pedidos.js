// RF-08: historial y estado de los pedidos del usuario (pedidos_api.php GET).
document.addEventListener('DOMContentLoaded', async () => {
    const lista = document.getElementById('lista-pedidos');
    if (!lista) { return; }
    const sinSesion = document.getElementById('pedidos-sin-sesion');
    const vacio = document.getElementById('pedidos-vacio');

    const crear = (etiqueta, clase, texto) => {
        const el = document.createElement(etiqueta);
        if (clase) { el.className = clase; }
        if (texto !== undefined) { el.textContent = texto; }
        return el;
    };

    try {
        const respuesta = await fetch('pedidos_api.php');
        if (respuesta.status === 401) { sinSesion.hidden = false; return; }
        const pedidos = await respuesta.json();
        if (!Array.isArray(pedidos) || pedidos.length === 0) { vacio.hidden = false; return; }

        const tabla = crear('table', 'tabla-carrito');
        const cabeza = tabla.createTHead().insertRow();
        ['Pedido', 'Fecha', 'Sucursal', 'Total', 'Estado', 'Comprobante'].forEach(t => cabeza.appendChild(crear('th', '', t)));
        const cuerpo = tabla.createTBody();
        pedidos.forEach(p => {
            const fila = cuerpo.insertRow();
            fila.insertCell().textContent = '#' + p.idPedido;
            fila.insertCell().textContent = p.fecha;
            fila.insertCell().textContent = p.sucursal;
            fila.insertCell().textContent = '$' + Number(p.total).toFixed(2);
            const estado = crear('span', 'estado-pedido estado-' + p.estado.replace(/ /g, '-'), p.estado);
            fila.insertCell().appendChild(estado);
            const enlace = crear('a', 'boton', 'Descargar .txt');
            enlace.href = 'comprobante.php?id=' + p.idPedido;
            fila.insertCell().appendChild(enlace);
        });
        lista.appendChild(tabla);
    } catch (err) {
        vacio.textContent = 'No se pudieron cargar tus pedidos. Inténtalo de nuevo.';
        vacio.hidden = false;
    }
});

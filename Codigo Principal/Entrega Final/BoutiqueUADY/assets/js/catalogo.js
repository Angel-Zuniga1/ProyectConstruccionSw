// Catálogo dinámico: lee los productos de productos_api.php (MySQL) y los dibuja en la rejilla.
// Los filtros de sucursal y talla se resuelven en el servidor; búsqueda y categoría, aquí.
document.addEventListener('DOMContentLoaded', () => {
    const rejilla = document.getElementById('rejilla');
    if (!rejilla) { return; }

    const q = document.getElementById('q');
    const cat = document.getElementById('cat');
    const selSucursal = document.getElementById('sucursal');
    const selTalla = document.getElementById('talla');
    const vacio = document.getElementById('vacio');
    const errorCatalogo = document.getElementById('error-catalogo');
    const modal = document.getElementById('modalDetalle');
    const contenidoDetalle = document.getElementById('contenidoDetalle');
    let productos = [];

    const crear = (etiqueta, clase, texto) => {
        const el = document.createElement(etiqueta);
        if (clase) { el.className = clase; }
        if (texto !== undefined) { el.textContent = texto; }
        return el;
    };

    // Nombres de las sucursales donde hay existencias del producto
    const sucursalesConStock = (p) => {
        const nombres = [];
        p.disponibilidad.forEach(d => {
            if (d.existencias > 0 && !nombres.includes(d.sucursal)) { nombres.push(d.sucursal); }
        });
        return nombres;
    };

    // Tallas con existencias (vacío para productos sin talla)
    const tallasConStock = (p) => {
        const tallas = [];
        p.disponibilidad.forEach(d => {
            if (d.talla && d.existencias > 0 && !tallas.includes(d.talla)) { tallas.push(d.talla); }
        });
        return tallas;
    };

    const tieneTallas = (p) => p.disponibilidad.some(d => d.talla !== '');

    const dibujarTarjeta = (p) => {
        const tarjeta = crear('article', 'tarjeta');
        const foto = crear('div', 'foto');
        const img = document.createElement('img');
        img.src = p.imagen;
        img.alt = p.nombre;
        img.onerror = () => { img.onerror = null; img.src = 'fotos boutique/logotipoapp.jpg'; };
        foto.appendChild(img);

        const info = crear('div', 'info');
        info.appendChild(crear('span', 'cat', p.categoria));
        info.appendChild(crear('h3', '', p.nombre));
        info.appendChild(crear('p', '', p.descripcion));

        const sucursales = sucursalesConStock(p);
        const lineaSucursal = crear('p', 'sucursales');
        lineaSucursal.appendChild(crear('b', '', sucursales.length > 1 ? 'Sucursales: ' : 'Sucursal: '));
        lineaSucursal.appendChild(document.createTextNode(sucursales.length ? sucursales.join(', ') : 'Agotado'));
        info.appendChild(lineaSucursal);

        if (tieneTallas(p)) {
            const select = crear('select', 'select-talla');
            select.setAttribute('aria-label', 'Talla');
            select.appendChild(new Option('Selecciona talla', ''));
            tallasConStock(p).forEach(t => select.appendChild(new Option(t, t)));
            info.appendChild(select);
        }

        const fila = crear('div', 'fila');
        fila.appendChild(crear('strong', '', '$' + p.precio.toFixed(2)));
        const agregar = crear('button', 'boton-agregar', 'Agregar');
        agregar.type = 'button';
        agregar.dataset.id = p.id;
        agregar.dataset.nombre = p.nombre;
        agregar.dataset.precio = p.precio;
        agregar.dataset.imagen = p.imagen;
        if (sucursales.length === 0) { agregar.disabled = true; agregar.textContent = 'Agotado'; }
        fila.appendChild(agregar);
        info.appendChild(fila);

        const detalle = crear('button', 'boton-detalle', 'Ver detalle');
        detalle.type = 'button';
        detalle.addEventListener('click', () => abrirDetalle(p.id));
        info.appendChild(detalle);

        tarjeta.append(foto, info);
        return tarjeta;
    };

    const dibujar = () => {
        const texto = q.value.trim().toLowerCase();
        const lista = productos.filter(p =>
            (texto === '' || p.nombre.toLowerCase().includes(texto)) &&
            (cat.value === '0' || String(p.idCategoria) === cat.value));
        rejilla.innerHTML = '';
        lista.forEach(p => rejilla.appendChild(dibujarTarjeta(p)));
        vacio.hidden = lista.length > 0;
    };

    // Pide los productos al servidor con los filtros de sucursal y talla
    const cargar = async () => {
        const params = new URLSearchParams();
        if (selSucursal.value) { params.set('sucursal', selSucursal.value); }
        if (selTalla.value) { params.set('talla', selTalla.value); }
        try {
            const respuesta = await fetch('productos_api.php?' + params.toString());
            if (!respuesta.ok) { throw new Error('HTTP ' + respuesta.status); }
            productos = await respuesta.json();
            errorCatalogo.hidden = true;
        } catch (err) {
            console.error('No se pudo cargar el catálogo', err);
            productos = [];
            errorCatalogo.hidden = false;
        }
        dibujar();
    };

    // ---------- detalle del producto ----------
    const abrirDetalle = async (id) => {
        try {
            const respuesta = await fetch('productos_api.php?id=' + id);
            if (!respuesta.ok) { throw new Error('HTTP ' + respuesta.status); }
            const p = await respuesta.json();
            contenidoDetalle.innerHTML = '';
            const img = document.createElement('img');
            img.src = p.imagen;
            img.alt = p.nombre;
            img.className = 'detalle-img';
            contenidoDetalle.append(img, crear('h2', '', p.nombre), crear('p', '', p.descripcion),
                crear('p', 'detalle-precio', '$' + p.precio.toFixed(2)),
                crear('h3', '', 'Disponibilidad por sucursal'));

            const tabla = crear('table', 'tabla-carrito');
            const cabeza = tabla.createTHead().insertRow();
            ['Sucursal', 'Talla', 'Existencias'].forEach(t => cabeza.appendChild(crear('th', '', t)));
            const cuerpo = tabla.createTBody();
            p.disponibilidad.forEach(d => {
                const fila = cuerpo.insertRow();
                fila.insertCell().textContent = d.sucursal;
                fila.insertCell().textContent = d.talla || 'Única';
                fila.insertCell().textContent = d.existencias > 0 ? d.existencias : 'Agotado';
            });
            contenidoDetalle.appendChild(tabla);
            modal.hidden = false;
        } catch (err) {
            alert('No se pudo cargar el detalle del producto.');
        }
    };
    const cerrarDetalle = () => { modal.hidden = true; };
    document.getElementById('cerrarDetalle').addEventListener('click', cerrarDetalle);
    modal.addEventListener('click', (e) => { if (e.target === modal) { cerrarDetalle(); } });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { cerrarDetalle(); } });

    // ---------- arranque ----------
    const params = new URLSearchParams(location.search);
    if (params.get('categoria')) { cat.value = params.get('categoria'); }

    fetch('sucursales_api.php').then(r => r.json()).then(lista => {
        lista.forEach(s => selSucursal.appendChild(new Option(s.nombre, s.id)));
    }).catch(() => { /* el filtro queda solo con "Todas" */ });

    q.addEventListener('input', dibujar);
    cat.addEventListener('change', dibujar);
    selSucursal.addEventListener('change', cargar);
    selTalla.addEventListener('change', cargar);
    document.getElementById('filtros').addEventListener('submit', e => e.preventDefault());
    cargar();
});

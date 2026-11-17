document.addEventListener("DOMContentLoaded", () => {
    
    // El carrito vive en la base de datos (carrito_api.php) y pertenece al usuario con sesion
    let carrito = [];
    let carritoCargado = false;
    const haySesion = () => document.cookie.split(';').some(c => c.trim().startsWith('usuario_nombre='));

    // Se descarta el carrito viejo que antes se guardaba en el navegador
    localStorage.removeItem('carritoUADY');

    // Repara productos guardados antes con rutas de imagen incorrectas
    const rutasCorregidas = {
        'fotos boutique/Playera-clasica-uady.jpg': 'fotos boutique/Playera-clasicauady.jpg',
        'fotos boutique/playera-deportiva-uady.jpg': 'fotos boutique/Playera deportiva uady.jpg',
        'fotos boutique/sudadera con capucha.jpg': 'fotos boutique/sudadera con capuccha.jpg',
        'fotos boutique/Playera fmat uady.jpg': 'fotos boutique/Playera fmat.jpg',
        'fotos boutique/Abánico uady.jpg': 'fotos boutique/Abanico uady.jpg'
    };
    const repararRutas = () => {
        carrito.forEach(item => {
            if (rutasCorregidas[item.imagen]) { item.imagen = rutasCorregidas[item.imagen]; }
        });
    };

    // Actualizar el contador de la barra de navegación
    const actualizarContador = () => {
        const contador = document.getElementById('contador-carrito');
        if (contador) {
            const totalItems = carrito.reduce((total, item) => total + item.cantidad, 0);
            contador.textContent = totalItems;
        }
    };

    // Enviar el carrito completo a la base de datos
    const enviarCarrito = () => fetch('carrito_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(carrito)
    }).then(respuesta => {
        if (!respuesta.ok) { throw new Error('HTTP ' + respuesta.status); }
    });

    // Guardar: actualiza el contador y manda el carrito a la base de datos
    const guardarCarrito = () => {
        actualizarContador();
        enviarCarrito().catch(() => {
            alert('No se pudo guardar tu carrito. Revisa tu sesión e inténtalo de nuevo.');
        });
    };

    // ==========================================
    // LÓGICA PARA LA PÁGINA DEL CATÁLOGO
    // ==========================================
    // Delegación: las tarjetas se crean dinámicamente desde catalogo.js
    {
            document.addEventListener('click', (e) => {
                const boton = e.target.closest('.boton-agregar');
                if (!boton || boton.disabled) { return; }
                // Sin sesion iniciada no se permite agregar (cookie creada por login.php)
                if (!haySesion()) {
                    alert('Debes iniciar sesión primero para agregar productos al carrito.');
                    return;
                }

                // Si el carrito no se pudo cargar, no se agrega para no borrar el guardado
                if (!carritoCargado) {
                    alert('Tu carrito no esta disponible todavía. Recarga la página e inténtalo de nuevo.');
                    return;
                }

                // Ropa: es obligatorio elegir talla (XS a 2XL) antes de agregar
                const tarjetaInfo = boton.closest('.info');
                const selectTalla = tarjetaInfo ? tarjetaInfo.querySelector('.select-talla') : null;
                let talla = '';
                if (selectTalla) {
                    talla = selectTalla.value;
                    if (!talla) {
                        alert('Selecciona una talla antes de agregar el producto al carrito.');
                        selectTalla.focus();
                        return;
                    }
                }

                const id = boton.dataset.id;
                const nombre = boton.dataset.nombre;
                const precio = parseFloat(boton.dataset.precio);
                const imagen = boton.dataset.imagen;

                // Mismo producto y misma talla se suman; otra talla es otra linea del carrito
                const productoExistente = carrito.find(item => item.id === id && (item.talla || '') === talla);

                if (productoExistente) {
                    productoExistente.cantidad += 1;
                } else {
                    carrito.push({ id, nombre, precio, imagen, talla, cantidad: 1 });
                }

                guardarCarrito();
                
                // Pequeña animación de confirmación
                const textoOriginal = boton.textContent;
                boton.textContent = '¡Agregado!';
                boton.style.backgroundColor = '#1f6b3a';
                setTimeout(() => {
                    boton.textContent = textoOriginal;
                    boton.style.backgroundColor = '';
                }, 1000);
            });
    }

    // ==========================================
    // LÓGICA PARA LA PÁGINA DEL CARRITO
    // ==========================================
    const listaCarrito = document.getElementById('lista-carrito');
    const totalCarrito = document.getElementById('total-carrito');
    const carritoVacio = document.getElementById('carrito-vacio');
    const carritoContenido = document.getElementById('carrito-contenido');

    const renderizarCarrito = () => {
        if (!listaCarrito) return; // Si no estamos en la página del carrito, no hacer nada

        listaCarrito.innerHTML = ''; // Limpiar tabla

        if (carrito.length === 0) {
            carritoVacio.hidden = false;
            carritoContenido.hidden = true;
            return;
        }

        carritoVacio.hidden = true;
        carritoContenido.hidden = false;

        let total = 0;

        carrito.forEach((item, index) => {
            const subtotal = item.precio * item.cantidad;
            total += subtotal;

            // Texto de talla solo para productos que la tienen
            const etiquetaTalla = item.talla ? `<br><small>Talla: ${item.talla}</small>` : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <img src="${item.imagen}" alt="${item.nombre}" onerror="this.onerror=null;this.src='fotos boutique/logotipoapp.jpg'" style="width:50px; height:50px; object-fit:contain; background:white; border-radius:5px;">
                        <span>${item.nombre}${etiquetaTalla}</span>
                    </div>
                </td>
                <td>$${item.precio.toFixed(2)}</td>
                <td>
                    <input type="number" min="1" value="${item.cantidad}" data-index="${index}" class="input-cantidad">
                </td>
                <td>$${subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn-eliminar" data-index="${index}">Borrar</button>
                </td>
            `;
            listaCarrito.appendChild(tr);
        });

        totalCarrito.textContent = `$${total.toFixed(2)}`;

        // Eventos para modificar cantidad
        document.querySelectorAll('.input-cantidad').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = e.target.dataset.index;
                const nuevaCantidad = parseInt(e.target.value);
                
                if (nuevaCantidad > 0) {
                    carrito[index].cantidad = nuevaCantidad;
                    guardarCarrito();
                    renderizarCarrito();
                } else {
                    e.target.value = 1; // Evitar cantidades negativas o cero
                }
            });
        });

        // Eventos para eliminar producto
        document.querySelectorAll('.btn-eliminar').forEach(boton => {
            boton.addEventListener('click', (e) => {
                const index = e.target.dataset.index;
                carrito.splice(index, 1); // Eliminar del array
                guardarCarrito();
                renderizarCarrito();
            });
        });
    };

    // Sucursales activas para elegir dónde recoger el pedido
    const selectSucursal = document.getElementById('sucursal-pedido');
    if (selectSucursal) {
        fetch('sucursales_api.php').then(r => r.json()).then(lista => {
            lista.forEach(s => {
                const op = document.createElement('option');
                op.value = s.id;
                op.textContent = s.nombre;
                selectSucursal.appendChild(op);
            });
        }).catch(() => { /* el select queda vacío y se avisa al confirmar */ });
    }

    // Confirmar el pedido: el servidor revisa existencias, descuenta inventario y crea el pedido
    const btnProceder = document.getElementById('btn-proceder-pago');
    const mensajePedido = document.getElementById('mensaje-pedido');
    if (btnProceder) {
        btnProceder.addEventListener('click', async () => {
            if (!haySesion()) {
                alert('Debes iniciar sesión para confirmar tu pedido.');
                return;
            }
            if (!selectSucursal.value) {
                alert('Elige la sucursal donde recogerás tu pedido.');
                selectSucursal.focus();
                return;
            }
            btnProceder.disabled = true;
            try {
                const respuesta = await fetch('pedidos_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idSucursal: parseInt(selectSucursal.value, 10) })
                });
                const datos = await respuesta.json();
                if (!respuesta.ok) {
                    alert(datos.error || 'No se pudo confirmar el pedido.');
                    return;
                }
                carrito = [];
                actualizarContador();
                renderizarCarrito();
                carritoContenido.hidden = true;
                carritoVacio.hidden = true;
                mensajePedido.hidden = false;
                mensajePedido.innerHTML = '';
                const titulo = document.createElement('h2');
                titulo.textContent = '¡Pedido confirmado! No. ' + datos.idPedido;
                const texto = document.createElement('p');
                texto.textContent = 'Total: $' + Number(datos.total).toFixed(2) + ' · Recógelo y págalo en la sucursal ' + datos.sucursal + '.';
                const enlace = document.createElement('a');
                enlace.className = 'boton';
                enlace.href = 'comprobante.php?id=' + datos.idPedido;
                enlace.textContent = 'Descargar comprobante (.txt)';
                const verPedidos = document.createElement('a');
                verPedidos.className = 'boton secundario';
                verPedidos.href = 'mis_pedidos.html';
                verPedidos.textContent = 'Ver mis pedidos';
                verPedidos.style.marginLeft = '10px';
                mensajePedido.append(titulo, texto, enlace, verPedidos);
            } catch (err) {
                alert('No se pudo confirmar el pedido. Inténtalo de nuevo.');
            } finally {
                btnProceder.disabled = false;
            }
        });
    }

    // Cargar el carrito del usuario desde la base de datos
    // Devuelve true si el carrito quedo listo (o si no hay sesion)
    const cargarCarrito = async () => {
        try {
            const respuesta = await fetch('carrito_api.php');
            if (respuesta.status === 401) {
                // La sesion de PHP expiro: se limpia la cookie del saludo
                document.cookie = 'usuario_nombre=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
                return true;
            }
            if (!respuesta.ok) { throw new Error('HTTP ' + respuesta.status); }
            carrito = await respuesta.json();
            repararRutas();
            return true;
        } catch (err) {
            console.error('No se pudo cargar el carrito', err);
            return false;
        }
    };

    // Inicializar la vista
    if (!haySesion()) {
        // Sin sesion el carrito se ve en cero
        carritoCargado = true;
        actualizarContador();
        renderizarCarrito();
    } else {
        cargarCarrito().then(ok => {
            carritoCargado = ok;
            actualizarContador();
            renderizarCarrito();
        });
    }
});
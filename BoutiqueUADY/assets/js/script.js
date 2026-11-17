/* ==========================================
   TEMA CLARO / OSCURO
   Se aplica el tema guardado apenas carga el script.
   ========================================== */
(function () {
    try {
        if (localStorage.getItem('temaUADY') === 'claro') {
            document.documentElement.setAttribute('data-tema', 'claro');
        }
    } catch (err) {
        // sin acceso a localStorage: se queda el tema oscuro
    }
})();

document.addEventListener("DOMContentLoaded", () => {
    console.log("Script cargado correctamente.");

    // ==========================================
    // 1. LÓGICA DEL LIGHTBOX (Ver imagen grande)
    // ==========================================
    const lightbox = document.getElementById("miLightbox");
    const imgLightbox = document.getElementById("imgLightbox");
    const cerrarBtn = document.querySelector(".cerrar-lightbox");
    
    if (lightbox && imgLightbox && cerrarBtn) {
        // Delegación: las tarjetas del catálogo se crean después, desde catalogo.js
        document.addEventListener("click", function(e) {
            const img = e.target.closest(".foto img");
            if (img) {
                lightbox.style.display = "flex";
                imgLightbox.src = img.src;
                imgLightbox.alt = img.alt;
            }
        });

        const cerrarLightbox = () => lightbox.style.display = "none";
        cerrarBtn.addEventListener("click", cerrarLightbox);
        lightbox.addEventListener("click", (e) => {
            if (e.target !== imgLightbox) cerrarLightbox();
        });
    }
});

/* ==========================================
   BARRA SUPERIOR: saludo con sesion y boton de tema.
   La sesion se lee de la cookie usuario_nombre que crea login.php
   (las paginas .html no pueden leer la sesion del servidor).
   /sesion_api.php confirma con el servidor que la sesion sigue activa: igual que lo hace index.php en el servidor.
   ========================================== */
document.addEventListener("DOMContentLoaded", () => {
    const nav = document.querySelector(".barra nav");
    if (!nav) return;

    const leerNombre = () => {
        const coincidencia = document.cookie.match(/(?:^|;\s*)usuario_nombre=([^;]*)/);
        if (!coincidencia) return null;
        try { return decodeURIComponent(coincidencia[1]); } catch (err) { return coincidencia[1]; }
    };

    // Boton de tema (luna)
    const botonTema = document.createElement("button");
    botonTema.type = "button";
    botonTema.className = "boton-tema";
    botonTema.textContent = "🌙";
    botonTema.title = "Cambiar fondo claro u oscuro";
    botonTema.setAttribute("aria-label", "Cambiar fondo claro u oscuro");
    botonTema.addEventListener("click", () => {
        const raiz = document.documentElement;
        const eraClaro = raiz.getAttribute("data-tema") === "claro";
        if (eraClaro) { raiz.removeAttribute("data-tema"); } else { raiz.setAttribute("data-tema", "claro"); }
        try { localStorage.setItem("temaUADY", eraClaro ? "oscuro" : "claro"); } catch (err) { /* ignorar */ }
    });

    // Con sesion activa se reemplazan los enlaces de invitado por el saludo
    const mostrarSaludo = (nombre) => {
        nav.querySelectorAll('a[href="login.php"], a[href="registro.php"]').forEach(a => a.remove());
        let saludo = nav.querySelector(".saludo");
        if (!saludo) {
            saludo = document.createElement("span");
            saludo.className = "saludo";
            nav.insertBefore(saludo, botonTema);

            const salir = document.createElement("a");
            salir.href = "logout.php";
            salir.className = "enlace-salir";
            salir.textContent = "Cerrar sesión";
            nav.insertBefore(salir, botonTema);
        }
        saludo.textContent = "Hola, " + nombre;
    };

    // Sin sesion se muestran los enlaces de invitado
    const mostrarInvitado = () => {
        nav.querySelectorAll(".saludo, .enlace-salir").forEach(el => el.remove());
        if (!nav.querySelector('a[href="login.php"]')) {
            const login = document.createElement("a");
            login.href = "login.php";
            login.textContent = "Iniciar sesión";
            nav.insertBefore(login, botonTema);
        }
        if (!nav.querySelector('a[href="registro.php"]')) {
            const registro = document.createElement("a");
            registro.href = "registro.php";
            registro.className = "boton";
            registro.textContent = "Crear cuenta";
            nav.insertBefore(registro, botonTema);
        }
    };

    nav.appendChild(botonTema);

    const nombreCookie = leerNombre();
    if (nombreCookie) { mostrarSaludo(nombreCookie); }

    // Confirmar con el servidor (crea la cookie si faltaba y la borra si la sesion ya expiro)
    fetch("sesion_api.php", { headers: { "Accept": "application/json" }, cache: "no-store" })
        .then(r => r.json())
        .then(sesion => {
            if (sesion.autenticado) {
                mostrarSaludo(sesion.nombre);
            } else if (nombreCookie) {
                mostrarInvitado();
            }
        })
        .catch(() => { /* sin servidor: se queda lo que dice la cookie */ });
});

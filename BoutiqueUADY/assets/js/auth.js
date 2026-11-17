// Envía login.php y registro.php por fetch para mostrar los mensajes sin recargar la página
// (mismo comportamiento que la versión Java). Si JavaScript falla, el formulario se envía normal.
// Las validaciones con alert() las hace validacion.js (se carga antes); aquí solo se envía el formulario.
document.addEventListener("DOMContentLoaded", () => {

    const mensaje = document.getElementById("mensaje");
    const form = document.getElementById("formulario");

    const mostrarMensaje = (clase, contenido) => {
        if (!mensaje) return;
        mensaje.innerHTML = "";
        const div = document.createElement("div");
        div.className = clase;
        if (typeof contenido === "string") {
            div.textContent = contenido;
        } else {
            div.appendChild(contenido);
        }
        mensaje.appendChild(div);
    };

    // Si ya hay sesión iniciada se manda al catálogo (como hacían login.php y registro.php).
    // sesion_api.php también vuelve a crear la cookie del saludo si faltaba.
    const redirigirSiHaySesion = async () => {
        try {
            const resp = await fetch("sesion_api.php", { headers: { "Accept": "application/json" } });
            const sesion = await resp.json();
            if (sesion.autenticado) {
                window.location.replace("catalogo.html");
            }
        } catch (e) { /* sin conexión: se deja ver el formulario */ }
    };

    if (!form || !form.dataset.accion) return;
    const accion = form.dataset.accion;   // "login" o "registro"

    redirigirSiHaySesion();

    form.addEventListener("submit", async (e) => {
        // validacion.js ya mostró su alert y canceló el envío
        if (e.defaultPrevented) return;
        e.preventDefault();
        try {
            const resp = await fetch(accion + ".php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
                           "X-Requested-With": "fetch" },
                body: new URLSearchParams(new FormData(form)).toString()
            });
            const datos = await resp.json();
            if (datos.redirigir) {
                window.location.href = datos.redirigir;
                return;
            }
            if (accion === "registro" && datos.ok) {
                const span = document.createElement("span");
                span.appendChild(document.createTextNode("¡Cuenta creada con éxito! Ya puedes "));
                const a = document.createElement("a");
                a.href = "login.php";
                a.textContent = "iniciar sesión";
                span.appendChild(a);
                span.appendChild(document.createTextNode("."));
                mostrarMensaje("ok", span);
            } else {
                mostrarMensaje("error", datos.mensaje);
            }
            form.reset(); // la página original se recargaba con el formulario vacío
        } catch (err) {
            mostrarMensaje("error", "No se pudo conectar con el servidor. Intenta de nuevo.");
        }
    });
});

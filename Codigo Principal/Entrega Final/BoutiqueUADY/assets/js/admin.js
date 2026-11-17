// Avisos del panel de administración con alert() y confirm(), como pide el proyecto.
document.addEventListener('DOMContentLoaded', () => {
    // 1. Los mensajes ?ok= / ?error= de cada acción también se avisan con alert
    const params = new URLSearchParams(location.search);
    if (params.get('ok')) { alert(params.get('ok')); }
    if (params.get('error')) {
        alert(params.get('error') === 'permiso' ? 'No tienes permiso para esa sección.' : params.get('error'));
    }
    if (params.get('ok') || params.get('error')) {
        // Se limpia la URL para que no se repita el aviso al recargar
        params.delete('ok'); params.delete('error');
        const limpia = location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState(null, '', limpia);
    }

    // 2. Confirmación antes de acciones importantes (data-confirmar en el formulario)
    document.querySelectorAll('form[data-confirmar]').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm(form.dataset.confirmar)) { e.preventDefault(); }
        });
    });

    // 3. Formularios con campos obligatorios vacíos (data-validar)
    document.querySelectorAll('form[data-validar]').forEach(form => {
        form.addEventListener('submit', (e) => {
            for (const campo of form.querySelectorAll('[required]')) {
                if (String(campo.value).trim() === '') {
                    e.preventDefault();
                    alert('Por favor, llena el campo "' + (campo.closest('label')?.firstChild?.textContent.trim() || campo.name) + '".');
                    campo.focus();
                    return;
                }
            }
            const correo = form.elements['correo'];
            if (correo && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.value)) {
                e.preventDefault(); alert('El correo no es válido.'); correo.focus(); return;
            }
            const pass = form.elements['password'];
            if (pass && pass.value.length > 0 && pass.value.length < 6) {
                e.preventDefault(); alert('La contraseña debe tener al menos 6 caracteres.'); pass.focus(); return;
            }
        });
    });
});

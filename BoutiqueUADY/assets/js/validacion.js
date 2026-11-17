// Validación de formularios con alert (sin los avisos del navegador).
const form = document.getElementById('formulario');
const nombres = {
  nombre: 'el nombre', apellidos: 'los apellidos', correo: 'el correo',
  password: 'la contraseña', password2: 'la confirmación de contraseña'
};

form.addEventListener('submit', function (e) {
  const campos = form.querySelectorAll('input[name]');
  for (const c of campos) {
    if (c.name === 'telefono') continue;               // opcional
    if (c.value.trim() === '') {
      e.preventDefault();
      alert('Por favor, llena ' + (nombres[c.name] || 'todos los campos') + '.');
      c.focus();
      return;
    }
  }
  const correo = form.elements['correo'];
  if (correo && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.value)) {
    e.preventDefault(); alert('El correo no es válido.'); correo.focus(); return;
  }
  const p1 = form.elements['password'], p2 = form.elements['password2'];
  if (p2) {                                            // solo en registro
    if (p1.value.length < 8) {
      e.preventDefault(); alert('La contraseña debe tener al menos 8 caracteres.'); p1.focus(); return;
    }
    if (p1.value !== p2.value) {
      e.preventDefault(); alert('Las contraseñas no coinciden.'); p2.focus(); return;
    }
  }
});

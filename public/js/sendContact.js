document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('.contact-section form');
  if (!form) return;
  const msgBox = document.getElementById('form-message');
  const button = form.querySelector('button');

  // Helper para mostrar errores debajo de cada input
  function showError(input, message) {
    let error = input.parentElement.querySelector('.input-error');
    if (!error) {
      error = document.createElement('span');
      error.className = 'input-error';
      input.parentElement.appendChild(error);
    }
    error.textContent = message;
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
  }

  function clearError(input) {
    let error = input.parentElement.querySelector('.input-error');
    if (error) error.remove();
    input.classList.remove('is-invalid');
    input.classList.remove('is-valid');
  }

  function setValid(input) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    let error = input.parentElement.querySelector('.input-error');
    if (error) error.remove();
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    let valid = true;
    msgBox.innerHTML = '';
    button.disabled = true;
    button.innerText = 'Enviando...';

    let nombre = form.querySelector('#nombre');
    let correo = form.querySelector('#correo');
    let mensaje = form.querySelector('#mensaje');

    // Validación nombre
    if (!nombre.value.trim() || nombre.value.length < 3) {
      showError(nombre, 'Ingrese un nombre válido (mínimo 3 caracteres).');
      valid = false;
    } else {
      setValid(nombre);
    }

    // Validación correo
    const emailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
    if (!correo.value.trim() || !emailRegex.test(correo.value)) {
      showError(correo, 'Ingrese un correo electrónico válido.');
      valid = false;
    } else {
      setValid(correo);
    }

    // Validación mensaje
    if (!mensaje.value.trim() || mensaje.value.length < 10) {
      showError(mensaje, 'El mensaje debe tener al menos 10 caracteres.');
      valid = false;
    } else {
      setValid(mensaje);
    }

    if (!valid) {
      button.disabled = false;
      button.innerText = 'Enviar consulta';
      return;
    }

    // Si es válido, enviar al backend
    const contactData = {
      name: nombre.value.trim(),
      email: correo.value.trim(),
      message: mensaje.value.trim()
    };

    try {
      const response = await fetch('https://email.michofer.com.ar/api/contact', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(contactData),
      });

      if (response.ok) {
        form.reset();
        [nombre, correo, mensaje].forEach(clearError);
        [nombre, correo, mensaje].forEach(input => input.classList.remove('is-valid'));
        msgBox.innerHTML = '<div class="alert alert-success">✅ ¡Mensaje enviado correctamente!</div>';
      } else {
        const errorData = await response.json();
        msgBox.innerHTML = `<div class="alert alert-danger">❌ Error: ${errorData.message || 'verificá los campos'}</div>`;
      }
    } catch (error) {
      console.error('Error de red:', error);
      msgBox.innerHTML = '<div class="alert alert-danger">❌ No se pudo enviar el mensaje. Intentalo más tarde.</div>';
    } finally {
      button.disabled = false;
      button.innerText = 'Enviar consulta';
    }
  });

  // Validación en tiempo real
  form.querySelectorAll('input, textarea').forEach(input => {
    input.addEventListener('input', () => {
      clearError(input);
      input.classList.remove('is-valid');
    });
  });
});

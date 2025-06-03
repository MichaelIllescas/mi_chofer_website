document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
  const msg = document.getElementById("form-message");
  const button = form.querySelector("button");

  if (!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // Limpiar mensajes anteriores
    msg.innerHTML = "";
    button.disabled = true;
    button.innerText = "Enviando...";

    const name = document.getElementById("nombre").value.trim();
    const email = document.getElementById("correo").value.trim();
    const message = document.getElementById("mensaje").value.trim();

    const contactData = { name, email, message };

    try {
      const response = await fetch("https://email.michofer.com.ar/api/contact", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(contactData),
      });

      if (response.ok) {
        form.reset();
        msg.innerHTML = `<div class="alert alert-success">✅ ¡Mensaje enviado correctamente!</div>`;
      } else {
        const errorData = await response.json();
        msg.innerHTML = `<div class="alert alert-danger">❌ Error: ${errorData.message || "verificá los campos"}</div>`;
      }
    } catch (error) {
      console.error("Error de red:", error);
      msg.innerHTML = `<div class="alert alert-danger">❌ No se pudo enviar el mensaje. Intentalo más tarde.</div>`;
    } finally {
      button.disabled = false;
      button.innerText = "Enviar consulta";
    }
  });
});


/*function iniciarSesion() {
    window.location.href = "menu.php";
}
    

 
function cerrarSesion() {
    window.location.href = "inicio_sesion.php";
}
 */   

function mostrarContrasena() {
    const input = document.getElementById("password");
    if (input.type === "password") {
      input.type = "text";
    } else {
      input.type = "password";
    }
  }


/*Busqueda*/
 document.addEventListener("DOMContentLoaded", function () {
  const inputBusqueda = document.getElementById("busqueda");
  const filas = document.querySelectorAll(".tabla-usuarios tbody tr");

  if (inputBusqueda) {
    inputBusqueda.addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();

      filas.forEach(function (fila) {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? "" : "none";
      });
    });
  }
}); 
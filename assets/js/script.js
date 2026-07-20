function mostrarContrasena() {
    const input = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    if (input.type === "password") {
        input.type = "text";
        if (icon) {
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        }
    } else {
        input.type = "password";
        if (icon) {
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const inputBusqueda = document.getElementById("busqueda");
    const filas = document.querySelectorAll("table tbody tr");

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

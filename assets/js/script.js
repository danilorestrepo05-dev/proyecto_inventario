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

// Al enfocar un campo numérico con valor "0", dejarlo vacío para poder
// escribir el precio directamente sin borrar el cero manualmente.
// No se usa select() porque los inputs type="number" de navegadores
// modernos (Chrome) no soportan selección de texto (InvalidStateError).
document.addEventListener("focusin", function (e) {
    const el = e.target;
    if (el && el.matches && el.matches('input[type="number"]') && el.value !== "" && parseFloat(el.value) === 0) {
        el.value = "";
    }
});

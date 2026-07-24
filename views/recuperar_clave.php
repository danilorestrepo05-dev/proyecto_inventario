<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if ($_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='usuarios.php';</script>";
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>alert('Usuario no especificado'); window.location='usuarios.php';</script>";
    exit();
}

$id_usuario = intval($_GET['id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Cambiar Clave</title>
    <link rel="icon" type="image/png" href="../assets/img/compumasterldlogo.png">
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-key me-2"></i>Cambiar Clave</h5>
            <a href="usuarios.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../controllers/procesar_cambiar_clave.php" method="POST" id="formCambiarClave">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ID_usuario" value="<?php echo $id_usuario; ?>">
                <div class="mb-3">
                    <label class="form-label">Nueva Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="nueva_clave" id="nueva_clave"
                               placeholder="Mínimo 8 caracteres (letras y números)"
                               minlength="8" pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$"
                               title="Debe contener al menos 8 caracteres, incluyendo letras y números" required>
                        <span class="input-group-text icono_ojo" style="cursor:pointer;">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                    <small class="form-text text-muted">
                        Mínimo 8 caracteres, debe incluir letras y números
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar Nueva Contraseña</label>
                    <input type="password" class="form-control" name="confirmar_clave" id="confirmar_clave"
                           placeholder="Confirmar contraseña" minlength="8" required>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-circle me-1"></i> Cambiar Clave
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const formCambiarClave = document.getElementById('formCambiarClave');
    const nuevaClave = document.getElementById('nueva_clave');
    const confirmarClave = document.getElementById('confirmar_clave');
    const toggleIcon = document.getElementById('toggleIcon');
    const iconoOjo = document.querySelector('.icono_ojo');

    iconoOjo.addEventListener('click', function() {
        if (nuevaClave.type === 'password') {
            nuevaClave.type = 'text';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            nuevaClave.type = 'password';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    });

    formCambiarClave.addEventListener('submit', function(e) {
        if (nuevaClave.value !== confirmarClave.value) {
            e.preventDefault();
            alert('Las contraseñas no coinciden');
            return false;
        }
        if (nuevaClave.value.length < 8) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 8 caracteres');
            return false;
        }
        const tieneLetras = /[A-Za-z]/.test(nuevaClave.value);
        const tieneNumeros = /[0-9]/.test(nuevaClave.value);
        if (!tieneLetras || !tieneNumeros) {
            e.preventDefault();
            alert('La contraseña debe contener letras y números');
            return false;
        }
    });

    confirmarClave.addEventListener('input', function() {
        if (this.value !== nuevaClave.value && this.value.length > 0) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else if (this.value.length > 0) {
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            this.classList.remove('is-valid');
        }
    });
</script>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
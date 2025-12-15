<?php
include("../config/conexion.php");
session_start();

// Solo el admin puede cambiar claves
if ($_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='usuarios.php';</script>";
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>alert('Usuario no especificado'); window.location='usuarios.php';</script>";
    exit();
}

$id_usuario = $_GET['id']; // ID del usuario a modificar

// Si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_clave = $_POST['nueva_clave'];

    $sql = "UPDATE usuario SET clave='$nueva_clave' WHERE ID_usuario='$id_usuario'";
    if ($conn->query($sql)) {
        header("Location: usuarios.php?mensaje=Clave actualizada correctamente");
        exit();
    } else {
        $error = "Error al actualizar la clave: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar clave</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    
<style>
        body {
            background-color: #a0e7f0;
        }
        .card {
            max-width: 440px;
            width: 100%;
        }
        .btn {
            padding: 8px;
            margin: 2px;
        }
        @media (max-width: 768px) {
            .card {
                margin: 0 10px; /* margen lateral pequeño en móviles */
            }
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow p-4">
        <h4 class="text-center mb-4">Cambiar clave del usuario: <?php echo $id_usuario; ?></h4>

        <?php if (!empty($error)) echo "<div class='alert alert-danger text-center'>$error</div>"; ?>

        <!-- Formulario para cambiar contraseña -->
<form action="../controllers/procesar_cambiar_clave.php" method="POST" id="formCambiarClave">
    <!-- ID del usuario (oculto) -->
    <input type="hidden" name="ID_usuario" value="<?php echo $id_usuario; ?>">
    
    <!-- Nueva contraseña -->
    <div class="datos_registro">
        <label for="nueva_clave">Nueva Contraseña</label>
        <input type="password" 
               name="nueva_clave" 
               id="nueva_clave" 
               placeholder="Mínimo 8 caracteres (letras y números)"
               minlength="8"
               pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$"
               title="Debe contener al menos 8 caracteres, incluyendo letras y números"
               required>
        <small style="color: #666; font-size: 12px;">
            Mínimo 8 caracteres, debe incluir letras y números
        </small>
    </div>
    
    <!-- Confirmar nueva contraseña -->
    <div class="datos_registro">
        <label for="confirmar_clave">Confirmar Nueva Contraseña</label>
        <input type="password" 
               name="confirmar_clave" 
               id="confirmar_clave" 
               placeholder="Confirmar contraseña"
               minlength="8"
               required>
    </div>
    
    <button type="submit" class="btn btn-primary w-100 boton_login">Cambiar clave</button>
    <a href="usuarios.php" class="btn btn-outline-secondary w-100">Cancelar</a>
</form>

<script>
    const formCambiarClave = document.getElementById('formCambiarClave');
    const nuevaClave = document.getElementById('nueva_clave');
    const confirmarClave = document.getElementById('confirmar_clave');
    
    // Validar al enviar
    formCambiarClave.addEventListener('submit', function(e) {
        // Verificar que coincidan
        if (nuevaClave.value !== confirmarClave.value) {
            e.preventDefault();
            alert('❌ Las contraseñas no coinciden');
            return false;
        }
        
        // Validar longitud
        if (nuevaClave.value.length < 8) {
            e.preventDefault();
            alert('❌ La contraseña debe tener al menos 8 caracteres');
            return false;
        }
        
        // Validar letras y números
        const tieneLetras = /[A-Za-z]/.test(nuevaClave.value);
        const tieneNumeros = /[0-9]/.test(nuevaClave.value);
        
        if (!tieneLetras || !tieneNumeros) {
            e.preventDefault();
            alert('❌ La contraseña debe contener letras y números');
            return false;
        }
    });
    
    // Mostrar si coinciden en tiempo real
    confirmarClave.addEventListener('input', function() {
        if (this.value !== nuevaClave.value && this.value.length > 0) {
            this.style.borderColor = 'red';
        } else {
            this.style.borderColor = '#2bb8ca';
        }
    });
</script>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
include("../config/conexion.php");

// Verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Verificar que solo el rol 'Admin' pueda acceder
if ($_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado: solo permitido para administradores'); window.location='menu.php';</script>";
    exit();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de usuarios</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="registro_usuario">
    <div class="registrarse">
        <h1>Regístrate</h1>
        
        <form action="../controllers/procesar_registro.php" method="POST" class="formulario_registro">
            <div class="datos_registro">
                <label>Nombres</label>
                <input type="text" name="nombre" id="nombres" placeholder="Nombres" required>
            </div>
            
            <div class="datos_registro">
                <label>Apellidos</label>
                <input type="text" name="apellido" id="apellidos" placeholder="Apellidos" required>
            </div>
            
            <div class="datos_registro">
                <label>Número de documento</label>
                <input type="text" name="documento" id="documento" placeholder="Documento" required>
            </div>
            
            <div class="datos_registro">
                <label>Correo electrónico</label>
                <input type="email" name="correo" id="email" placeholder="Correo electrónico" required>
            </div>
            
            <!-- Campo de contraseña -->
<div class="datos_registro">
    <label for="clave">Contraseña</label>
    <input type="password" 
           name="clave" 
           id="password" 
           placeholder="Mínimo 8 caracteres (letras y números)" 
           minlength="8"
           pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$"
           title="Debe contener al menos 8 caracteres, incluyendo letras y números" required>
           <span class="icono_ojo" onclick="mostrarContrasena()">👁</span>
           <small style="color: #666; font-size: 12px;">
            Mínimo 8 caracteres, debe incluir letras y números
           </small>
</div>


<!-- Script de validación en tiempo real -->
<script>
    const clave = document.getElementById('clave');
      
        // Validar longitud
        if (clave.value.length < 8) {
            e.preventDefault();
            alert('❌ La contraseña debe tener al menos 8 caracteres');
            return false;
        }
        
        // Validar letras y números
        const tieneLetras = /[A-Za-z]/.test(clave.value);
        const tieneNumeros = /[0-9]/.test(clave.value);
        
        if (!tieneLetras || !tieneNumeros) {
            e.preventDefault();
            alert('❌ La contraseña debe contener letras y números');
            return false;
        }
    });
    });
</script>
            
            <div class="datos_registro">
                <label for="tipo_usuario">Tipo de usuario</label>
                <select name="rol" id="tipo_usuario" required>
                    <option value="" disabled selected>Seleccione una opción</option>
                    <option value="Admin">Admin</option>
                    <option value="Operario">Operario</option>
                </select>
            </div>
            
            <button type="submit" class="boton_registro">Registrarse</button>
            
            <div class="links">
                ¿Ya tienes cuenta? <a href="../menu.php">Inicia sesión</a>
            </div>
            
        </form>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>
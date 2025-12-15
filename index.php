<?php
include("config/conexion.php");

session_start();
// Si ya está logueado, redirigir al menú
if (isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <title>Inicio de sesión</title>
</head>
<body class="custom-body d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h1 class="text-center mb-4">Sistema de Gestión de Inventarios</h1>

 <?php
        // Mostrar mensaje de error si existe
        if (isset($_GET['error'])) {
            $error = htmlspecialchars($_GET['error']);
            echo "<div style='background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; text-align: center;'>
                    ❌ $error
                  </div>";
        }
        
        // Mostrar mensaje de éxito si existe
        if (isset($_GET['mensaje'])) {
            $mensaje = htmlspecialchars($_GET['mensaje']);
            echo "<div style='background-color: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; text-align: center;'>
                    ✅ $mensaje
                  </div>";
        }
        ?>
        
        <form action="controllers/procesar_login.php" method="POST">
            <div class="mb-3">
                <label for="usuario" class="form-label">Documento</label>
                <input type="text" name="documento" id="usuario" class="form-control user_data" placeholder="Ingrese su documento" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="password" name="clave" id="password" class="form-control user_data" placeholder="Ingrese su contraseña" required>
                    <span class="input-group-text user_data" style="cursor:pointer;" onclick="mostrarContrasena()">👁</span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 boton_login">Iniciar sesión</button>
            
        </form>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
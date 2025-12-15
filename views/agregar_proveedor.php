<?php 

include("../config/conexion.php");

// Verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$consulta = "SELECT * FROM proveedor";
$resultado = $conn->query($consulta);

//if ($resultado->num_rows == 0) {
  //  echo "Usuario no encontrado.";
    //exit;
//}

$fila_usuario = $resultado->fetch_assoc();


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar nuevo proveedor</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="registro_usuario">
    <div class="registrarse">
        <h1>Información del nuevo proveedor</h1>
        
        <form action="../controllers/procesar_nuevo_proveedor.php" method="POST" class="formulario_registro">
            

            <div class="datos_registro">
                <input type="text" name="nombre" id="nombres" placeholder="Nombres" required>
            </div>
            
            <div class="datos_registro">
                <input type="text" name="correo" id="email" placeholder="Correo electrónico" required>
            </div>
            
            <div class="datos_registro">
                <input type="text" name="telefono" id="telefono" placeholder="Teléfono" required>
            </div>

            <div class="datos_registro">
                <input type="text" name="direccion" id="direccion" placeholder="Dirección" required>
            </div>
            
            
            <button type="submit" class="boton_registro">Guardar proveedor</button>
            
        </form>
    </div>
</body>
</html>
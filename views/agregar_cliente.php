<?php 

include("../config/conexion.php");

// Verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$consulta = "SELECT * FROM cliente";
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
    <title>Agregar nuevo cliente</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="registro_usuario">
    <div class="registrarse">
        <h1>Información del nuevo cliente</h1>
        
        <form action="../controllers/procesar_nuevo_cliente.php" method="POST" class="formulario_registro">
            

            <div class="datos_registro">
                <label for="tipo_usuario">Nombre</label>
                <input type="text" name="nombre" id="nombres" placeholder="Nombres" required>
            </div>
            
            <div class="datos_registro">
                <label for="tipo_usuario">Apellido</label>
                <input type="text" name="apellido" id="apellidos" placeholder="Apellidos" required>
            </div>
            
            <div class="datos_registro">
                <label for="tipo_usuario">Correo Electrónico</label>
                <input type="email" name="correo" id="email" placeholder="Correo electrónico" required>
            </div>

            <div class="datos_registro">
                <label for="tipo_usuario">Teléfono</label>
                <input type="text" name="telefono" id="telefono" placeholder="Teléfono" required>
            </div>
            
            
            <button type="submit" class="boton_registro">Guardar cliente</button>
            
        </form>
    </div>
</body>
</html>
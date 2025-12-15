<?php 

include("../config/conexion.php");

// Verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$consulta = "SELECT * FROM producto";
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
    <title>Agregar nuevo producto</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="registro_usuario">
    <div class="registrarse">
        <h1>Información del nuevo producto</h1>
        
        <form action="../controllers/procesar_nuevo_producto.php" method="POST" class="formulario_registro">
            

            <div class="datos_registro">
                <label for="nombre">Producto</label>
                <input type="text" name="nombre" id="nombres" placeholder="Nombre" required>
            </div>
            
            <div class="datos_registro">
                <label for="stock">Cantidad</label>
                <input type="number" name="stock" id="stock" placeholder="cantidad" min="0" required>
            </div>
            
            <div class="datos_registro">
                <label for="precio">Precio unitario</label>
                <input type="number" name="precio" id="precio" placeholder="Precio Unidad" min="0" required>
            </div>

            <div class="datos_registro">
                <label for="descripcion">Descripción</label>
                <input name="descripcion" id="descripcion" placeholder="Descripción" required>
            </div>

            <div class="datos_registro">
                <input type="date" name="fecha" id="fecha" placeholder="" required>
            </div>

            
            <button type="submit" class="boton_registro">Guardar producto</button>
            
        </form>
    </div>
</body>
</html>
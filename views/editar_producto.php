<?php 
include("../config/conexion.php");
$codigo = $_REQUEST['id'];

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='productos.php';</script>";
    exit();
}

$consulta = "SELECT * FROM producto WHERE ID_producto = '$codigo'";
$resultado = $conn->query($consulta);

if ($resultado->num_rows == 0) {
    echo "Usuario no encontrado.";
    exit;
}

$fila_usuario = $resultado->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="registro_usuario">
    <div class="registrarse">
        <h1>Modificar información del producto</h1>
        
        <form action="../controllers/procesar_edicion_producto.php" method="POST" class="formulario_registro">
            
            <div class="datos_registro">
                <input type="text" name="id" value="<?php echo $fila_usuario['ID_producto']; ?>"readonly>
            </div>

            <div class="datos_registro">
                <label for="nombre">Producto</label>
                <input type="text" name="nombre" value="<?php echo $fila_usuario['nombre']; ?>" id="nombres" placeholder="Nombre" required>
            </div>

            <div class="datos_registro">
                <label for="stock">Cantidad</label>
                <input type="number" name="stock" value="<?php echo $fila_usuario['stock']; ?>" id="nombres" placeholder="Cantidad" min="0" required>
            </div>
            
            <div class="datos_registro">
                <label for="precio">Precio unitario</label>
                <input type="number" name="precio" value="<?php echo $fila_usuario['precio']; ?>" id="email" placeholder="Precio unidad" min="0" required>
            </div>
            
            <div class="datos_registro">
                <label for="descripcion">Descripción</label>
                <input type="text" name="descripcion" value="<?php echo $fila_usuario['descripcion']; ?>" id="telefono" placeholder="Descripción" required>
            </div>

            <div class="datos_registro">
                <input type="date" name="fecha" value="<?php echo $fila_usuario['fecha']; ?>" id="direccion" placeholder="" required>
            </div>
            
            
            <button type="submit" class="boton_registro">Guardar cambios</button>
            
        </form>
    </div>
</body>
</html>
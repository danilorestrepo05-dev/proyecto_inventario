<?php 
include("../config/conexion.php");
$codigo = $_REQUEST['id'];

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='productos.php';</script>";
    exit();
}

$consulta = "SELECT * FROM proveedor WHERE ID_proveedor = '$codigo'";
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
    <title>Editar Proveedor</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="registro_usuario">
    <div class="registrarse">
        <h1>Modificar información del proveedor</h1>
        
        <form action="../controllers/procesar_edicion_proveedor.php" method="POST" class="formulario_registro">
            
            <div class="datos_registro">
                <input type="text" name="id" value="<?php echo $fila_usuario['ID_proveedor']; ?>"readonly>
            </div>

            <div class="datos_registro">
                <input type="text" name="nombre" value="<?php echo $fila_usuario['nombre_proveedor']; ?>" id="nombres" placeholder="Nombres">
            </div>
            
            <div class="datos_registro">
                <input type="email" name="correo" value="<?php echo $fila_usuario['correo']; ?>" id="email" placeholder="Correo electrónico">
            </div>
            
            <div class="datos_registro">
                <input type="text" name="telefono" value="<?php echo $fila_usuario['telefono']; ?>" id="telefono" placeholder="Teléfono">
            </div>

            <div class="datos_registro">
                <input type="text" name="direccion" value="<?php echo $fila_usuario['direccion']; ?>" id="direccion" placeholder="Dirección">
            </div>
            
            
            <button type="submit" class="boton_registro">Guardar cambios</button>
            
        </form>
    </div>
</body>
</html>
<?php 
include("../config/conexion.php");
$codigo = $_REQUEST['id'];

$consulta = "SELECT * FROM usuario WHERE ID_usuario = '$codigo'";
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
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="registro_usuario">
    <div class="registrarse">
        <h1>Modificar información de usuario</h1>
        
        <form action="../controllers/procesar_edicion_usuario.php" method="POST" class="formulario_registro">
            
            <div class="datos_registro">
                <input type="text" name="id" value="<?php echo $fila_usuario['ID_usuario']; ?>"readonly>
            </div>

            <div class="datos_registro">
                <label for="tipo_usuario">Nombre</label>
                <input type="text" name="nombre" value="<?php echo $fila_usuario['nombre']; ?>" id="nombres" placeholder="Nombres">
            </div>
            
            <div class="datos_registro">
                <label for="tipo_usuario">Apellido</label>
                <input type="text" name="apellido" value="<?php echo $fila_usuario['apellido']; ?>" id="apellidos" placeholder="Apellidos">
            </div>
            
            <div class="datos_registro">
                <label for="tipo_usuario">Correo Electrónico</label>
                <input type="email" name="correo" value="<?php echo $fila_usuario['correo']; ?>" id="email" placeholder="Correo electrónico">
            </div>
            
            <div class="datos_registro">
                <label for="tipo_usuario">Tipo de usuario</label>
                <select name="rol" value="<?php echo $fila_usuario['rol']; ?>" id="tipo_usuario" required>
                    <option value="" disabled selected>Seleccione una opción</option>
                    <option value="Admin">Admin</option>
                    <option value="Operario">Operario</option>
                </select>
            </div>
            
            <button type="submit" class="boton_registro">Guardar cambios</button>
            
        </form>
    </div>
</body>
</html>
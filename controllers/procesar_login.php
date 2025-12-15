<?php
include("../config/conexion.php");
session_start();

$documento = $_POST['documento'];
$clave = $_POST['clave'];

// Consulta para obtener el usuario por documento
$sql = "SELECT * FROM usuario WHERE documento='$documento'";
$resultado = $conn->query($sql);

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();
    
    // Verificar la contraseña cifrada
    if (password_verify($clave, $fila['clave'])) {
        // Contraseña correcta - Iniciar sesión
        $_SESSION['usuario'] = $documento;
        $_SESSION['rol'] = $fila['rol'];
        $_SESSION['nombre_completo'] = $fila['nombre'] . ' ' . $fila['apellido'];
        
        $conn->close();
        header("Location: ../menu.php?mensaje=Sesión iniciada exitosamente!");
        exit();
    } else {
        // Contraseña incorrecta
        $conn->close();
        header("Location: ../index.php?error=Usuario o contraseña incorrectos");
        exit();
    }
} else {
    // Usuario no encontrado
    $conn->close();
    header("Location: ../index.php?error=Usuario o contraseña incorrectos");
    exit();
}
?>
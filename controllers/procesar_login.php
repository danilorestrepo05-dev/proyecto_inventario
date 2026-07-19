<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../index.php?error=Token CSRF inválido");
    exit();
}

$documento = $_POST['documento'];
$clave = $_POST['clave'];

// Consulta para obtener el usuario por documento
$sql = "SELECT * FROM usuario WHERE documento = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $documento);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();
    
    // Verificar la contraseña cifrada
    if (password_verify($clave, $fila['clave'])) {
        // Contraseña correcta - Iniciar sesión
        $_SESSION['usuario'] = $documento;
        $_SESSION['rol'] = $fila['rol'];
        $_SESSION['nombre_completo'] = $fila['nombre'] . ' ' . $fila['apellido'];
        
        // Regenerar token CSRF después de login
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
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
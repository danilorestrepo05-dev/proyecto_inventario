<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/rate_limit.php");

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../index.php?error=Token CSRF inválido");
    exit();
}

$documento = $_POST['documento'];
$clave = $_POST['clave'];
$ip = $_SERVER['REMOTE_ADDR'];

// Verificar rate limiting
$bloqueo = verificar_bloqueo($conn, $ip, $documento);
if ($bloqueo !== true) {
    $conn->close();
    header("Location: ../index.php?error=" . urlencode($bloqueo));
    exit();
}

// Consulta para obtener el usuario por documento
$sql = "SELECT * FROM usuario WHERE documento = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $documento);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();

    // Verificar si el usuario está activo
    if (!$fila['activo']) {
        $conn->close();
        header("Location: ../index.php?error=Tu cuenta está desactivada. Contacta al administrador.");
        exit();
    }
    
    // Verificar la contraseña cifrada
    if (password_verify($clave, $fila['clave'])) {
        limpiar_intento($conn, $ip, $documento);

        $_SESSION['usuario'] = $documento;
        $_SESSION['id_usuario'] = $fila['ID_usuario'];
        $_SESSION['rol'] = $fila['rol'];
        $_SESSION['nombre_completo'] = $fila['nombre'] . ' ' . $fila['apellido'];
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        $conn->close();
        header("Location: ../menu.php?mensaje=Sesión iniciada exitosamente!");
        exit();
    } else {
        registrar_intento_fallido($conn, $ip, $documento);
        $conn->close();
        header("Location: ../index.php?error=Usuario o contraseña incorrectos");
        exit();
    }
} else {
    registrar_intento_fallido($conn, $ip, $documento);
    $conn->close();
    header("Location: ../index.php?error=Usuario o contraseña incorrectos");
    exit();
}
?>

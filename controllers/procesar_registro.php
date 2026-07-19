<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../menu.php?error=Token CSRF inválido");
    exit();
}

$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$documento = $_POST['documento'];
$correo = $_POST['correo'];
$clave = $_POST['clave'];
$rol = $_POST['rol'];

// VALIDACIONES DE SEGURIDAD

// 1. Validar longitud mínima (8 caracteres)
if (strlen($clave) < 8) {
    mysqli_close($conn);
    echo "<script>
            alert('Error: La contraseña debe tener al menos 8 caracteres');
            window.history.back();
          </script>";
    exit();
}

// 2. Validar que contenga letras y números
if (!preg_match('/[A-Za-z]/', $clave) || !preg_match('/[0-9]/', $clave)) {
    mysqli_close($conn);
    echo "<script>
            alert('Error: La contraseña debe contener letras y números');
            window.history.back();
          </script>";
    exit();
}

// CIFRAR LA CONTRASEÑA
$clave_cifrada = password_hash($clave, PASSWORD_DEFAULT);

// GUARDAR EN LA BASE DE DATOS
$sql = "INSERT INTO usuario (nombre, apellido, documento, correo, clave, rol) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $nombre, $apellido, $documento, $correo, $clave_cifrada, $rol);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../menu.php?mensaje=Usuario registrado correctamente.");
    exit();
} else {
    $stmt->close();
    mysqli_close($conn);
    echo "Error al registrar: " . $conn->error;
}
?>
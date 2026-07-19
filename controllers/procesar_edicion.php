<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/usuarios.php?error=Token CSRF inválido");
    exit();
}

$codigo = intval($_REQUEST['id']);
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$rol = $_POST['rol'];

$consulta = "UPDATE usuario SET nombre = ?, apellido = ?, correo = ?, rol = ? WHERE ID_usuario = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("ssssi", $nombre, $apellido, $correo, $rol, $codigo);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/usuarios.php?mensaje=Usuario actualizado correctamente");
    exit();
} else {
    echo "Error al modificar los datos del usuario: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
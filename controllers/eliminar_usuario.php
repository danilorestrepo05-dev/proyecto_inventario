<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/usuarios.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/usuarios.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);
$inactivos = isset($_GET['inactivos']) ? '&inactivos=1' : '';

$check = $conn->prepare("SELECT nombre, apellido, activo FROM usuario WHERE ID_usuario = ?");
$check->bind_param("i", $codigo);
$check->execute();
$usuario = $check->get_result()->fetch_assoc();
$check->close();

$nueva_accion = $usuario['activo'] ? 'desactivar' : 'activar';
$desc = 'Usuario "' . $usuario['nombre'] . ' ' . $usuario['apellido'] . '" ' . ($nueva_accion === 'activar' ? 'activado' : 'desactivado');

$sql = "UPDATE usuario SET activo = NOT activo WHERE ID_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    registrar_cambio($conn, 'usuario', $nueva_accion, $codigo, $desc);
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/usuarios.php?mensaje=Estado del usuario actualizado$inactivos");
    exit();
} else {
    echo "Error al cambiar estado del usuario: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
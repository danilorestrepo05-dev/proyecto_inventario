<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/usuarios.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/usuarios.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);

$sql = "UPDATE usuario SET activo = NOT activo WHERE ID_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/usuarios.php?mensaje=Estado del usuario actualizado");
    exit();
} else {
    echo "Error al cambiar estado del usuario: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
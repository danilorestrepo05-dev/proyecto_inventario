<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    header("Location: ../views/usuarios.php?error=Token CSRF inválido");
    exit();
}

$codigo = intval($_REQUEST['id']);

$consulta = "DELETE FROM usuario WHERE ID_usuario = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/usuarios.php?mensaje=Usuario eliminado correctamente");
    exit();
} else {
    echo "Error al eliminar el usuario: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
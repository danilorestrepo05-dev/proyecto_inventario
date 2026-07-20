<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/productos.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/productos.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);

$sql = "UPDATE producto SET activo = NOT activo WHERE ID_producto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/productos.php?mensaje=Estado del producto actualizado");
    exit();
} else {
    echo "Error al cambiar estado del producto: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
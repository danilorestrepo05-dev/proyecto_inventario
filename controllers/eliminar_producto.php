<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/productos.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/productos.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);
$inactivos = isset($_GET['inactivos']) ? '&inactivos=1' : '';

$check = $conn->prepare("SELECT nombre, activo FROM producto WHERE ID_producto = ?");
$check->bind_param("i", $codigo);
$check->execute();
$producto = $check->get_result()->fetch_assoc();
$check->close();

$nueva_accion = $producto['activo'] ? 'desactivar' : 'activar';
$desc = 'Producto "' . $producto['nombre'] . '" ' . ($nueva_accion === 'activar' ? 'activado' : 'desactivado');

$sql = "UPDATE producto SET activo = NOT activo WHERE ID_producto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    registrar_cambio($conn, 'producto', $nueva_accion, $codigo, $desc);
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/productos.php?mensaje=Estado del producto actualizado$inactivos");
    exit();
} else {
    echo "Error al cambiar estado del producto: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/proveedores.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/proveedores.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);
$inactivos = isset($_GET['inactivos']) ? '&inactivos=1' : '';

$check = $conn->prepare("SELECT nombre_proveedor, activo FROM proveedor WHERE ID_proveedor = ?");
$check->bind_param("i", $codigo);
$check->execute();
$proveedor = $check->get_result()->fetch_assoc();
$check->close();

$nueva_accion = $proveedor['activo'] ? 'desactivar' : 'activar';
$desc = 'Proveedor "' . $proveedor['nombre_proveedor'] . '" ' . ($nueva_accion === 'activar' ? 'activado' : 'desactivado');

$sql = "UPDATE proveedor SET activo = NOT activo WHERE ID_proveedor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    registrar_cambio($conn, 'proveedor', $nueva_accion, $codigo, $desc);
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/proveedores.php?mensaje=Estado del proveedor actualizado$inactivos");
    exit();
} else {
    echo "Error al cambiar estado del proveedor: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
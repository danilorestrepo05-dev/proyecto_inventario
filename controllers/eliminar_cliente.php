<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/clientes.php';</script>";
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    echo "<script>alert('Token CSRF inválido'); window.location='../views/clientes.php';</script>";
    exit();
}

$codigo = intval($_REQUEST['id']);
$inactivos = isset($_GET['inactivos']) ? '&inactivos=1' : '';

$check = $conn->prepare("SELECT nombre, apellido, activo FROM cliente WHERE ID_cliente = ?");
$check->bind_param("i", $codigo);
$check->execute();
$cliente = $check->get_result()->fetch_assoc();
$check->close();

$nueva_accion = $cliente['activo'] ? 'desactivar' : 'activar';
$desc = 'Cliente "' . $cliente['nombre'] . ' ' . $cliente['apellido'] . '" ' . ($nueva_accion === 'activar' ? 'activado' : 'desactivado');

$sql = "UPDATE cliente SET activo = NOT activo WHERE ID_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $codigo);

if ($stmt->execute()) {
    registrar_cambio($conn, 'cliente', $nueva_accion, $codigo, $desc);
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/clientes.php?mensaje=Estado del cliente actualizado$inactivos");
    exit();
} else {
    echo "Error al cambiar estado del cliente: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
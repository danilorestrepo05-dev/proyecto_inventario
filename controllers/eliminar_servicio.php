<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_GET['csrf_token'] ?? '')) {
    header("Location: ../views/reparaciones.php?error=Token CSRF invalido");
    exit();
}

if ($rol !== 'Admin') {
    header("Location: ../views/reparaciones.php?error=Sin permisos");
    exit();
}

$id_servicio = intval($_GET['id'] ?? 0);
if ($id_servicio <= 0) {
    header("Location: ../views/reparaciones.php");
    exit();
}

$rol = $_SESSION['rol'] ?? '';

// Obtener info antes de eliminar
$sql = "SELECT nombre FROM servicio WHERE ID_servicio=?";
$stmt = @$conn->prepare($sql);
$stmt->bind_param("i", $id_servicio);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fila) {
    header("Location: ../views/reparaciones.php");
    exit();
}

// Soft delete
$sql_del = "UPDATE servicio SET activo = 0 WHERE ID_servicio=?";
$stmt_del = @$conn->prepare($sql_del);
$stmt_del->bind_param("i", $id_servicio);

if ($stmt_del->execute()) {
    registrar_cambio($conn, 'servicio', 'eliminar', $id_servicio, 'Servicio "' . $fila['nombre'] . '" eliminado');
    $stmt_del->close();
    mysqli_close($conn);
    header("Location: ../views/reparaciones.php?mensaje=Servicio eliminado correctamente");
    exit();
} else {
    mysqli_close($conn);
    header("Location: ../views/reparaciones.php?error=Error al eliminar");
    exit();
}

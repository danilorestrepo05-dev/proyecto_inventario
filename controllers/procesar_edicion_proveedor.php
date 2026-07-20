<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/proveedores.php?error=Token CSRF inválido");
    exit();
}

$codigo = intval($_REQUEST['id']);
$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];

$consulta = "UPDATE proveedor SET nombre_proveedor = ?, correo = ?, telefono = ?, direccion = ? WHERE ID_proveedor = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("ssssi", $nombre, $correo, $telefono, $direccion, $codigo);

if ($stmt->execute()) {
    registrar_cambio($conn, 'proveedor', 'editar', $codigo, 'Proveedor "' . $nombre . '" editado');
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/proveedores.php?mensaje=Proveedor actualizado correctamente");
    exit();
} else {
    echo "Error al modificar los datos del proveedor: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
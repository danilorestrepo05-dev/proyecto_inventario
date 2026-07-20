<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/proveedores.php?error=Token CSRF inválido");
    exit();
}

$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];

$sql = "INSERT INTO proveedor (nombre_proveedor, correo, telefono, direccion) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $nombre, $correo, $telefono, $direccion);

if ($stmt->execute()) {
    $id_proveedor = $conn->insert_id;
    registrar_cambio($conn, 'proveedor', 'crear', $id_proveedor, 'Proveedor "' . $nombre . '" creado');
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/proveedores.php?mensaje=Proveedor agregado correctamente");
    exit();
} else {
    echo "Error al registrar el proveedor: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
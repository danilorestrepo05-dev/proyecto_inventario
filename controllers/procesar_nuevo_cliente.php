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
    header("Location: ../views/clientes.php?error=Token CSRF inválido");
    exit();
}

$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$telefono = $_POST['telefono'];
$tipo_identificacion = $_POST['tipo_identificacion'] ?? 'ninguno';
$identificacion = trim($_POST['identificacion'] ?? '');

$tipos_permitidos = ['cc', 'nit', 'otro', 'ninguno'];
if (!in_array($tipo_identificacion, $tipos_permitidos)) {
    $tipo_identificacion = 'ninguno';
}

$sql = "INSERT INTO cliente (nombre, apellido, correo, telefono, tipo_identificacion, identificacion) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $nombre, $apellido, $correo, $telefono, $tipo_identificacion, $identificacion);

if ($stmt->execute()) {
    $id_cliente = $conn->insert_id;
    registrar_cambio($conn, 'cliente', 'crear', $id_cliente, 'Cliente "' . $nombre . ' ' . $apellido . '" creado');
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/clientes.php?mensaje=Cliente agregado correctamente");
    exit();
} else {
    echo "Error al registrar el cliente: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
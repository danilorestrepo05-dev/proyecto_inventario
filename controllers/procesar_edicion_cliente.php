<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/clientes.php?error=Token CSRF inválido");
    exit();
}

$codigo = intval($_REQUEST['id']);
$nombre = $_POST['nombre'];
$apellido = $_POST['apellido'];
$correo = $_POST['correo'];
$telefono = $_POST['telefono'];

$consulta = "UPDATE cliente SET nombre = ?, apellido = ?, correo = ?, telefono = ? WHERE ID_cliente = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("ssssi", $nombre, $apellido, $correo, $telefono, $codigo);

if ($stmt->execute()) {
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/clientes.php?mensaje=Cliente actualizado correctamente");
    exit();
} else {
    echo "Error al modificar los datos del cliente: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>
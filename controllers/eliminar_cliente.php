<?php
include("../config/conexion.php");
$codigo = $_REQUEST['id'];

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/clientes.php';</script>";
    exit();
}

// Verificar si el cliente tiene ventas asociadas
$sql_verificar = "SELECT COUNT(*) as ventas FROM orden_venta WHERE ID_cliente = '$codigo'";
$resultado_verificar = $conn->query($sql_verificar);
$verificacion = $resultado_verificar->fetch_assoc();

// Si tiene ventas registradas, NO permitir eliminación
if ($verificacion['ventas'] > 0) {
    $mensaje = "❌ No se puede eliminar este cliente\\n\\n";
    $mensaje .= "Tiene " . $verificacion['ventas'] . " venta(s) registrada(s)\\n\\n";
    $mensaje .= "No es posible eliminar clientes con historial de compras para mantener la integridad de los datos.";
    
    mysqli_close($conn);
    echo "<script>
            alert('$mensaje');
            window.location.href = '../views/clientes.php';
          </script>";
    exit();
}

// Si NO tiene ventas asociadas, permitir eliminación
$consulta = "DELETE FROM cliente WHERE ID_cliente = '$codigo'";

if ($conn->query($consulta) === TRUE) {
    mysqli_close($conn);
    header("Location: ../views/clientes.php?mensaje=Cliente eliminado correctamente");
    exit();
} else {
    echo "Error al eliminar el cliente: " . $conn->error;
    mysqli_close($conn);
}
?>
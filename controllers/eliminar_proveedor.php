<?php
include("../config/conexion.php");
$codigo = $_REQUEST['id'];

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/proveedores.php';</script>";
    exit();
}

// Verificar si el proveedor tiene compras o productos asociados
$sql_verificar = "SELECT 
    (SELECT COUNT(*) FROM orden_compra WHERE ID_proveedor = '$codigo') as compras,
    (SELECT COUNT(*) FROM producto WHERE ID_proveedor = '$codigo') as productos";
$resultado_verificar = $conn->query($sql_verificar);
$verificacion = $resultado_verificar->fetch_assoc();

// Si tiene registros asociados, NO permitir eliminación
if ($verificacion['compras'] > 0 || $verificacion['productos'] > 0) {
    $mensaje = "❌ No se puede eliminar este proveedor\\n\\n";
    $mensaje .= "Tiene registros asociados:\\n";
    if ($verificacion['compras'] > 0) {
        $mensaje .= "• " . $verificacion['compras'] . " compra(s)\\n";
    }
    if ($verificacion['productos'] > 0) {
        $mensaje .= "• " . $verificacion['productos'] . " producto(s)\\n";
    }
    $mensaje .= "\\nNo es posible eliminar proveedores con historial de transacciones para mantener la integridad de los datos.";
    
    mysqli_close($conn);
    echo "<script>
            alert('$mensaje');
            window.location.href = '../views/proveedores.php';
          </script>";
    exit();
}

// Si NO tiene registros asociados, permitir eliminación
$consulta = "DELETE FROM proveedor WHERE ID_proveedor = '$codigo'";

if ($conn->query($consulta) === TRUE) {
    mysqli_close($conn);
    header("Location: ../views/proveedores.php?mensaje=Proveedor eliminado correctamente");
    exit();
} else {
    echo "Error al eliminar el proveedor: " . $conn->error;
    mysqli_close($conn);
}
?>
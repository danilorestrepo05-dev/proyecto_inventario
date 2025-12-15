<?php
include("../config/conexion.php");
$codigo = $_REQUEST['id'];

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado'); window.location='../views/productos.php';</script>";
    exit();
}

// Verificar si el producto tiene ventas o compras asociadas
$sql_verificar = "SELECT 
    (SELECT COUNT(*) FROM detalle_orden_venta WHERE ID_producto = '$codigo') as ventas,
    (SELECT COUNT(*) FROM detalle_orden_compra WHERE ID_producto = '$codigo') as compras";
$resultado_verificar = $conn->query($sql_verificar);
$verificacion = $resultado_verificar->fetch_assoc();

// Si tiene registros asociados, NO permitir eliminación
if ($verificacion['ventas'] > 0 || $verificacion['compras'] > 0) {
    $mensaje = "❌ No se puede eliminar este producto\\n\\n";
    $mensaje .= "Tiene registros asociados:\\n";
    if ($verificacion['ventas'] > 0) {
        $mensaje .= "• " . $verificacion['ventas'] . " venta(s)\\n";
    }
    if ($verificacion['compras'] > 0) {
        $mensaje .= "• " . $verificacion['compras'] . " compra(s)\\n";
    }
    $mensaje .= "\\nNo es posible eliminar productos con historial de transacciones para mantener la integridad de los datos.";
    
    mysqli_close($conn);
    echo "<script>
            alert('$mensaje');
            window.location.href = '../views/productos.php';
          </script>";
    exit();
}

// Si NO tiene registros asociados, permitir eliminación
$consulta = "DELETE FROM producto WHERE ID_producto = '$codigo'";

if ($conn->query($consulta) === TRUE) {
    mysqli_close($conn);
    header("Location: ../views/productos.php?mensaje=Producto eliminado correctamente");
    exit();
} else {
    echo "Error al eliminar el producto: " . $conn->error;
    mysqli_close($conn);
}
?>
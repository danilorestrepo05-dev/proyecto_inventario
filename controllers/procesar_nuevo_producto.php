<?php

include("../config/conexion.php");
session_start();

$nombre = $_POST ['nombre'];
$cantidad = $_POST ['stock'];
$precio = $_POST ['precio'];
$descripcion = $_POST ['descripcion'];
$fecha = $_POST ['fecha'];

// VALIDACIÓN: Stock y precio no pueden ser negativos
if ($cantidad < 0) {
    mysqli_close($conn);
    echo "<script>
            alert('❌ Error: El stock no puede ser negativo');
            window.history.back();
          </script>";
    exit();
}

if ($precio < 0) {
    mysqli_close($conn);
    echo "<script>
            alert('❌ Error: El precio no puede ser negativo');
            window.history.back();
          </script>";
    exit();
}

$conn->query("INSERT INTO producto (nombre, stock, precio, descripcion, fecha) VALUES ('$nombre', '$cantidad', '$precio', '$descripcion', '$fecha')");

if (mysqli_connect_errno()!=0){

    echo "Error al modificar los datos del producto" . mysqli_connect_errno() . " - " . mysqli_connect_error();
    mysqli_close($conn);
} else{
    mysqli_close($conn);
    header("Location: ../views/productos.php?mensaje=Producto agregado correctamente");
    exit;
}

?>
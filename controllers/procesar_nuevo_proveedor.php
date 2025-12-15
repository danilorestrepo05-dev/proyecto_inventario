<?php

include("../config/conexion.php");
session_start();

$nombre = $_POST ['nombre'];
$correo = $_POST ['correo'];
$telefono = $_POST ['telefono'];
$direccion = $_POST ['direccion'];

$conn->query("INSERT INTO proveedor (nombre_proveedor, correo, telefono, direccion) VALUES ('$nombre', '$correo', '$telefono', '$direccion')");

if (mysqli_connect_errno()!=0){

    echo "Error al modificar los datos del proveedor" . mysqli_connect_errno() . " - " . mysqli_connect_error();
    mysqli_close($conn);
} else{
    mysqli_close($conn);
    header("Location: ../views/proveedores.php");
    exit;
}

?>
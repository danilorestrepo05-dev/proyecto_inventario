<?php

include("../config/conexion.php");
session_start();

$codigo = $_REQUEST ['id'];
$nombre = $_POST ['nombre'];
$correo = $_POST ['correo'];
$telefono = $_POST ['telefono'];
$direccion = $_POST ['direccion'];

$consulta = "UPDATE proveedor set nombre_proveedor = '$nombre', correo = '$correo', telefono = '$telefono', direccion = '$direccion' WHERE ID_proveedor = '$codigo'";
$conn->query($consulta);

if (mysqli_connect_errno()!=0){

    echo "Error al modificar los datos del proveedor" . mysqli_connect_errno() . " - " . mysqli_connect_error();
    mysqli_close($conn);
} else{
    mysqli_close($conn);
    header("Location: ../views/proveedores.php");
    exit;
}

?>
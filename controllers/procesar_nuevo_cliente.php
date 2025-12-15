<?php

include("../config/conexion.php");
session_start();

$nombre = $_POST ['nombre'];
$apellido = $_POST ['apellido'];
$correo = $_POST ['correo'];
$telefono = $_POST ['telefono'];

$conn->query("INSERT INTO cliente (nombre, apellido, correo, telefono) VALUES ('$nombre', '$apellido', '$correo', '$telefono')");

if (mysqli_connect_errno()!=0){

    echo "Error al modificar los datos del cliente" . mysqli_connect_errno() . " - " . mysqli_connect_error();
    mysqli_close($conn);
} else{
    mysqli_close($conn);
    header("Location: ../views/clientes.php");
    exit;
}

?>
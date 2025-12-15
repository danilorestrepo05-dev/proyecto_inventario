<?php

include("../config/conexion.php");
session_start();

$codigo = $_REQUEST ['id'];
$nombre = $_POST ['nombre'];
$apellido = $_POST ['apellido'];
$correo = $_POST ['correo'];
$telefono = $_POST ['telefono'];

$consulta = "UPDATE cliente set nombre = '$nombre', apellido = '$apellido', correo = '$correo', telefono = '$telefono' WHERE ID_cliente = '$codigo'";
$conn->query($consulta);

if (mysqli_connect_errno()!=0){

    echo "Error al modificar los datos del cliente" . mysqli_connect_errno() . " - " . mysqli_connect_error();
    mysqli_close($conn);
} else{
    mysqli_close($conn);
    header("Location: ../views/clientes.php");
    exit;
}

?>
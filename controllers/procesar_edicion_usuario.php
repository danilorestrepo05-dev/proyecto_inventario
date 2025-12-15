<?php

include("../config/conexion.php");
session_start();

$codigo = $_REQUEST ['id'];
$nombre = $_POST ['nombre'];
$apellido = $_POST ['apellido'];
$correo = $_POST ['correo'];
$rol = $_POST ['rol'];

$consulta = "UPDATE usuario set nombre = '$nombre', apellido = '$apellido', correo = '$correo', rol = '$rol' WHERE ID_usuario = '$codigo'";
$conn->query($consulta);

if (mysqli_connect_errno()!=0){

    echo "Error al modificar los datos del usuario" . mysqli_connect_errno() . " - " . mysqli_connect_error();
    mysqli_close($conn);
} else{
    mysqli_close($conn);
    header("Location: ../views/usuarios.php");
    exit;
}

?>
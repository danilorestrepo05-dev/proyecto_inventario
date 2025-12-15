<?php

include("../config/conexion.php");
$codigo = $_REQUEST['id'];

$consulta = "DELETE FROM usuario where ID_usuario = '$codigo'";
$conn->query($consulta);

if(mysqli_connect_errno() !=0)
{
    echo "Error al eliminar el usuario" . mysqli_connect_errno() . " - " . mysqli_connect_error();
    mysqli_close($conn);
} else {
    mysqli_close($conn);
    header("Location: ../views/usuarios.php");
    exit;

}

?>
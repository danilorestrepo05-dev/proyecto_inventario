<?php
date_default_timezone_set('America/Bogota');

$servername = "localhost";
$username = "root";
$password = "";
$db = "inventariodb";

// Crear conexión
$conn = mysqli_connect($servername, $username, $password, $db);

// Verificar conexión
if (!$conn) {
  die("Conexión fallida: " . mysqli_connect_error());
}
echo "";
?>
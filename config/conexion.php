<?php
// Zona horaria del servidor para que fechas y registros sean consistentes
date_default_timezone_set('America/Bogota');

// Credenciales de conexión al servidor MySQL local (XAMPP)
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

// Charset UTF-8 para tildes y ñ
mysqli_set_charset($conn, "utf8mb4");
?>
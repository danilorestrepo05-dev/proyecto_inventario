<?php
session_start();

// Prevenir caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_destroy();
header("Location: ../index.php");
exit();
?>
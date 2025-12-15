<?php

include("../config/conexion.php");
// Verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Obtener el rol de la sesión
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

$consulta = "SELECT * FROM cliente";
$resultado = $conn->query($consulta);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Gestión de clientes</title>
</head>
<body class="custom-body">

<div class="container my-4">
  <!-- Encabezado con botones -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch mb-3">
    <h1 class="mb-3 mb-md-0 fs-3">Gestión de clientes</h1>
    <div class="d-flex flex-column flex-sm-row gap-2">
      <div class="col-md-auto">
        <form action="../controllers/cerrar_sesion.php" method="POST">
          <button class="btn btn-secondary w-100 rounded-pill">Cerrar Sesión</button>
        </form>
      </div>
      <div class="col-md-auto">
        <a href="../menu.php" class="btn btn-success w-100 rounded-pill">Volver al menú</a>
      </div>
      <div class="col-md-auto">
        <a href="agregar_cliente.php" class="btn btn-primary w-100 rounded-pill">Agregar cliente</a>
      </div>
    </div>
  </div>

  <!-- Buscador -->
  <div class="mb-3">
    <input type="text" id="busqueda" placeholder="Buscar..." class="form-control-lg rounded-pill">
  </div>

  <!-- Tabla -->
  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle tabla-usuarios">
      <thead class="table-primary">
        <tr>
          <th>Código</th>
          <th>Nombre</th>
          <th>Apellido</th>
          <th>Correo electrónico</th>
          <th>Teléfono</th>
          <th>Opciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($resultado->num_rows > 0) {
          while ($fila = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$fila['ID_cliente']}</td>";
            echo "<td>{$fila['nombre']}</td>";
            echo "<td>{$fila['apellido']}</td>";
            echo "<td>{$fila['correo']}</td>";
            echo "<td>{$fila['telefono']}</td>";
            echo "<td>";
            if ($rol === 'Admin') {
              echo "<a href='editar_cliente.php?id={$fila['ID_cliente']}' class='btn btn-sm btn-warning'>Editar</a> 
                    <a href='../controllers/eliminar_cliente.php?id={$fila['ID_cliente']}' onclick=\"return confirm('¿Estás seguro de eliminar este cliente?')\" class='btn btn-sm btn-danger'>Eliminar</a>";
            } else {
              echo "Sin permisos";
            }
            echo "</td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='6' class='text-center'>Sin datos aún</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js" defer></script>
<script src="../assets/js/script.js" defer></script>
</body>
</html>
<?php

include("../config/conexion.php");
// Verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$mostrar_alerta = '';
if (isset($_GET['mensaje'])) {
    $mensaje = htmlspecialchars($_GET['mensaje']);
    $mostrar_alerta = "
        <div class='alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3' style='z-index: 9999; width: auto;' role='alert'>
            <i class='bi bi-check-circle-fill'></i> $mensaje
        </div>
    ";
}

// Verificar que solo el rol 'Admin' pueda acceder
if ($_SESSION['rol'] !== 'Admin') {
    echo "<script>alert('Acceso denegado: solo permitido para administradores'); window.location='menu.php';</script>";
    exit();
}

$consulta = "SELECT * FROM usuario";
$resultado = $conn->query($consulta);
?>

<script>
// Auto-ocultar mensajes después de 5 segundos
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.remove();
            // Limpiar la URL sin recargar la página
            if (window.history.replaceState) {
                const url = window.location.href.split('?')[0];
                window.history.replaceState({}, document.title, url);
            }
        }, 500);
    });
}, 5000); // 5000 = 5 segundos
</script>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Gestión de usuarios</title>
</head>

<body class="custom-body">
<?php echo $mostrar_alerta; ?>
<div class="container my-4">
  <!-- Encabezado con botones -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch mb-3">
    <h1 class="mb-3 mb-md-0 fs-3">Gestión de usuarios</h1>
    <div class="d-flex flex-column flex-sm-row gap-2">
      <div class="col-md-auto">
        <form action="../controllers/cerrar_sesion.php" method="POST">
          <button class="btn btn-secondary w-100 rounded-pill">Cerrar Sesión</button>
        </form>
      </div>
      <div class="col-md-auto">
        <a href="../menu.php" class="btn btn-success w-100 rounded-pill">Volver al menú</a>
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
          <th>ID</th>
          <th>Nombre</th>
          <th>Apellido</th>
          <th>Correo electrónico</th>
          <th>Tipo de usuario</th>
          <th>Opciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($resultado->num_rows > 0) {
          while ($fila = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$fila['ID_usuario']}</td>";
            echo "<td>{$fila['nombre']}</td>";
            echo "<td>{$fila['apellido']}</td>";
            echo "<td>{$fila['correo']}</td>";
            echo "<td>{$fila['rol']}</td>";
            echo "<td>
                    <a href='editar_usuario.php?id={$fila['ID_usuario']}' class='btn btn-sm btn-warning'>Editar</a>
                    <a href='../controllers/eliminar_usuario.php?id={$fila['ID_usuario']}' onclick=\"return confirm('¿Estás seguro de eliminar este usuario?')\" class='btn btn-sm btn-danger'>Eliminar</a>
                    <a href='recuperar_clave.php?id={$fila['ID_usuario']}' class='btn btn-sm btn-info'>🔐</a>
                  </td>";
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
    

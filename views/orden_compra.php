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

$mostrar_error = '';
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
    $mostrar_error = "
        <div class='alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3' style='z-index: 9999; width: auto;' role='alert'>
            <i class='bi bi-check-circle-fill'></i> $error
        </div>
    ";
}

// Obtener el rol de la sesión
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';

$consulta = "
SELECT 
    oc.ID_orden_compra,
    oc.ID_proveedor,
    oc.estado,
    oc.total,
    oc.fecha,
    doc.cantidad,
    p.nombre AS nombre_producto
FROM orden_compra oc
JOIN detalle_orden_compra doc ON oc.ID_orden_compra = doc.ID_orden_compra
JOIN producto p ON doc.ID_producto = p.ID_producto
ORDER BY oc.ID_orden_compra DESC
";
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" href="../assets/css/estilos.css"> -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Orden de compra</title>
</head>
<body class="custom-body">
<?php echo $mostrar_alerta; ?>
<?php echo $mostrar_error; ?>
<div class="container my-4">
  <!-- Encabezado con botones -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch mb-3">
    <h1 class="mb-3 mb-md-0 fs-3">Órdenes de compra</h1>
    <div class="d-flex flex-column flex-sm-row gap-2">
      <div class=" col-md-auto">
    <form action="../controllers/cerrar_sesion.php" method="POST">
      <button class="btn btn-secondary w-100 rounded-pill">Cerrar Sesión</button>
    </form>
  </div>
      <div class=" col-md-auto">
    <a href="../menu.php" class="btn btn-success w-100 rounded-pill">Volver al menú</a>
  </div>
  <div class=" col-md-auto">
    <a href="agregar_orden_compra.php" class="btn btn-primary w-100 rounded-pill">Agregar orden</a>
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
          <th>ID Orden</th>
          <th>Producto</th>
          <th>Proveedor</th>
          <th>Estado</th>
          <th>Cantidad</th>
          <th>Total</th>
          <th>Fecha</th>
          <th>Opciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($resultado->num_rows > 0) {
          while ($fila = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$fila['ID_orden_compra']}</td>";
            echo "<td>{$fila['nombre_producto']}</td>";
            echo "<td>{$fila['ID_proveedor']}</td>";
            echo "<td>{$fila['estado']}</td>";
            echo "<td>{$fila['cantidad']}</td>";
            echo "<td>$" . number_format($fila['total']) . "</td>";
            echo "<td>{$fila['fecha']}</td>";
            echo "<td>";
            if ($rol === 'Admin') {
              echo "<a href='editar_orden.php?id={$fila['ID_orden_compra']}' class='btn btn-sm btn-warning'>Editar</a> 
                    <a href='../controllers/eliminar_orden.php?id={$fila['ID_orden_compra']}' onclick=\"return confirm('¿Estás seguro?')\" class='btn btn-sm btn-danger'>Eliminar</a>";
            } else {
              echo "Sin permisos";
            }
            echo "</td>";
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='8' class='text-center'>Sin datos aún</td></tr>";
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
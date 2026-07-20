<?php
include("../config/conexion.php");
session_start();
include("../config/csrf.php");
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
$mostrar_inactivos = isset($_GET['inactivos']) && $_GET['inactivos'] == '1';

$filtro = $mostrar_inactivos ? "" : "WHERE activo = 1";
$consulta = "SELECT * FROM cliente $filtro ORDER BY activo ASC, ID_cliente DESC";
$resultado = $conn->query($consulta);

// Paginación
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$total_registros = $resultado->num_rows;
$total_paginas = max(1, ceil($total_registros / $por_pagina));
$inicio = ($pagina_actual - 1) * $por_pagina;

// Re-consultar con LIMIT
$consulta_paginada = $consulta . " LIMIT $inicio, $por_pagina";
$resultado_paginado = $conn->query($consulta_paginada);

$params_base = $mostrar_inactivos ? 'inactivos=1&' : '';
$inactivos_param = $mostrar_inactivos ? '&inactivos=1' : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Gestión de clientes</title>
</head>
<body class="custom-body">

<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container my-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch mb-3">
    <h2 class="mb-3 mb-md-0"><i class="bi bi-people me-2"></i>Gestión de clientes</h2>
    <div class="d-flex flex-column flex-sm-row gap-2">
      <?php if ($rol === 'Admin'): ?>
      <a href="agregar_cliente.php" class="btn btn-primary rounded-pill">
        <i class="bi bi-plus-circle me-1"></i> Agregar cliente
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex flex-column flex-sm-row gap-3 mb-3 align-items-stretch align-items-sm-center">
    <input type="text" id="busqueda" placeholder="Buscar..." class="form-control-lg rounded-pill" style="max-width: 300px;">
    <?php if ($rol === 'Admin'): ?>
    <a href="?<?php echo $mostrar_inactivos ? '' : 'inactivos=1'; ?>" class="btn btn-sm <?php echo $mostrar_inactivos ? 'btn-outline-secondary' : 'btn-outline-dark'; ?> rounded-pill whitespace-nowrap">
      <i class="bi bi-eye<?php echo $mostrar_inactivos ? '-slash' : ''; ?> me-1"></i>
      <?php echo $mostrar_inactivos ? 'Ocultar inactivos' : 'Mostrar inactivos'; ?>
    </a>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle tabla-usuarios">
      <thead class="table-dark">
        <tr>
          <th>Código</th>
          <th>Nombre</th>
          <th>Apellido</th>
          <th>Correo electrónico</th>
          <th>Teléfono</th>
          <?php if ($rol === 'Admin'): ?><th class="th-opciones">Opciones</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($total_registros > 0) {
          while ($fila = $resultado_paginado->fetch_assoc()) {
            $es_inactivo = !$fila['activo'];
            $clase_fila = $es_inactivo ? 'table-secondary' : '';
            $texto_estado = $es_inactivo ? '<span class="badge bg-secondary">Inactivo</span> ' : '';
            echo "<tr class='$clase_fila'>";
            echo "<td>{$fila['ID_cliente']}</td>";
            echo "<td>" . $texto_estado . "{$fila['nombre']}</td>";
            echo "<td>{$fila['apellido']}</td>";
            echo "<td>{$fila['correo']}</td>";
            echo "<td>{$fila['telefono']}</td>";
            if ($rol === 'Admin') {
            echo "<td class='td-opciones'>";
              echo "<a href='editar_cliente.php?id={$fila['ID_cliente']}' class='btn btn-sm btn-warning'><i class='bi bi-pencil'></i></a> ";
              $icono_toggle = $es_inactivos ?? $es_inactivo ? 'bi-arrow-counterclockwise' : 'bi-toggle-on';
              $clase_toggle = $es_inactivo ? 'btn-success' : 'btn-outline-danger';
              $titulo_toggle = $es_inactivo ? 'Restaurar' : 'Desactivar';
              echo "<a href='../controllers/eliminar_cliente.php?id={$fila['ID_cliente']}&csrf_token=" . csrf_token() . "$inactivos_param' class='btn btn-sm $clase_toggle' title='$titulo_toggle'><i class='bi $icono_toggle'></i></a>";
            echo "</td>";
            }
            echo "</tr>";
          }
        } else {
          echo "<tr><td colspan='" . ($rol === 'Admin' ? 6 : 5) . "' class='text-center'>Sin datos aún</td></tr>";
        }
        ?>
      </tbody>
    </table>
<?php if ($total_paginas > 1): ?>
<div class="pagination-container">
    <nav aria-label="Paginación">
        <ul class="pagination mb-0">
            <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?<?php echo $params_base; ?>pagina=<?php echo $pagina_actual - 1; ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo $params_base; ?>pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                <a class="page-link" href="?<?php echo $params_base; ?>pagina=<?php echo $pagina_actual + 1; ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
</div>
<p class="pagination-info">Mostrando <?php echo $inicio + 1; ?>-<?php echo min($inicio + $por_pagina, $total_registros); ?> de <?php echo $total_registros; ?> registros</p>
<?php endif; ?>
  </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js" defer></script>
<script src="../assets/js/script.js" defer></script>
</body>
</html>
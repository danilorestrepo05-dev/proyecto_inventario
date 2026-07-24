<?php 
include("../config/conexion.php");
session_start();
include('../config/csrf.php');
if (!isset($_SESSION['usuario'])) { header("Location: ../index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Agregar Cliente</title>
    <link rel="icon" type="image/png" href="../assets/img/compumasterldlogo.png">
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Agregar Cliente</h5>
            <a href="clientes.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../controllers/procesar_nuevo_cliente.php" method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" name="nombre" placeholder="Nombres" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Apellido</label>
                    <input type="text" class="form-control" name="apellido" placeholder="Apellidos" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tipo de Identificaci&oacute;n</label>
                        <select name="tipo_identificacion" class="form-select">
                            <option value="ninguno" selected>Ninguno</option>
                            <option value="cc">C&eacute;dula de Ciudadan&iacute;a (CC)</option>
                            <option value="nit">NIT</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">N&uacute;mero de Identificaci&oacute;n</label>
                        <input type="text" class="form-control" name="identificacion" placeholder="Opcional">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo Electr&oacute;nico</label>
                    <input type="email" class="form-control" name="correo" placeholder="Correo electr&oacute;nico">
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" name="telefono" placeholder="Tel&eacute;fono">
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-circle me-1"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
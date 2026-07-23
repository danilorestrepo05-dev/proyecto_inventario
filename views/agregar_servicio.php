<?php
include("../config/conexion.php");
session_start();
include('../config/csrf.php');
if (!isset($_SESSION['usuario'])) { header("Location: ../index.php"); exit(); }

$clientes = $conn->query("SELECT ID_cliente, nombre, apellido FROM cliente WHERE activo = 1 ORDER BY nombre");
$usuarios = $conn->query("SELECT ID_usuario, nombre, apellido FROM usuario WHERE activo = 1 ORDER BY nombre");
$tecnico_default = $_SESSION['id_usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Nuevo Servicio</title>
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card" style="max-width: 800px;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Nuevo Servicio</h5>
            <a href="reparaciones.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../controllers/procesar_nuevo_servicio.php" method="POST">
                <?php echo csrf_field(); ?>

                <h6 class="text-uppercase fw-bold text-secondary mb-3">
                    <i class="bi bi-person-lines-fill me-1"></i> Informaci&oacute;n del Cliente
                </h6>
                <div class="mb-3">
                    <label class="form-label">Cliente *</label>
                    <select name="cliente" id="cliente" class="form-select" required>
                        <option value="" disabled selected>[Seleccione un cliente]</option>
                        <?php while ($c = $clientes->fetch_assoc()): ?>
                            <option value="<?php echo $c['ID_cliente']; ?>"><?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">T&eacute;cnico Asignado</label>
                    <select name="tecnico" id="tecnico" class="form-select">
                        <?php while ($u = $usuarios->fetch_assoc()): ?>
                            <option value="<?php echo $u['ID_usuario']; ?>" <?php echo intval($u['ID_usuario']) === intval($tecnico_default) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label">Nombre del Servicio</label>
                    <input type="text" class="form-control" name="nombre_servicio" placeholder="Ej: Mantenimiento trimestral (opcional)">
                </div>

                <h6 class="text-uppercase fw-bold text-secondary mb-3">
                    <i class="bi bi-laptop me-1"></i> Primer Dispositivo
                </h6>
                <div class="mb-3">
                    <label class="form-label">Dispositivo *</label>
                    <input type="text" class="form-control" name="dispositivo" placeholder="Ej: Laptop HP, PC de Escritorio" required>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Marca</label>
                        <input type="text" class="form-control" name="marca" placeholder="Ej: HP, Dell, ASUS">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" class="form-control" name="modelo" placeholder="Ej: Pavilion 15">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">N&uacute;mero de Serie</label>
                        <input type="text" class="form-control" name="numero_serie" placeholder="Opcional">
                    </div>
                </div>

                <h6 class="text-uppercase fw-bold text-secondary mb-3">
                    <i class="bi bi-wrench me-1"></i> Primer Trabajo
                </h6>
                <div class="mb-3">
                    <label class="form-label">Tipo de Trabajo *</label>
                    <select name="tipo_trabajo" id="tipo_trabajo" class="form-select" required>
                        <option value="" disabled selected>[Seleccione un tipo]</option>
                        <option value="General">General</option>
                        <option value="Revisi&oacute;n">Revisi&oacute;n</option>
                        <option value="Mantenimiento HW">Mantenimiento HW</option>
                        <option value="Mantenimiento SW">Mantenimiento SW</option>
                        <option value="Instalaci&oacute;n">Instalaci&oacute;n</option>
                        <option value="Formateo">Formateo</option>
                        <option value="Limpieza">Limpieza</option>
                        <option value="Diagn&oacute;stico">Diagn&oacute;stico</option>
                        <option value="Reparaci&oacute;n">Reparaci&oacute;n</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Problema Reportado *</label>
                    <textarea class="form-control" name="problema_reportado" rows="3" placeholder="Describa el problema que presenta el equipo" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label">Notas Internas</label>
                    <textarea class="form-control" name="notas_internas" rows="2" placeholder="Notas privadas del t&eacute;cnico (opcional)"></textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-circle me-1"></i> Registrar Servicio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

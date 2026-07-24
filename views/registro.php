<?php
include("../config/conexion.php");
session_start();
include('../config/csrf.php');
if (!isset($_SESSION['usuario'])) { header("Location: ../index.php"); exit(); }
if ($_SESSION['rol'] !== 'Admin') { header("Location: ../menu.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Registro de usuarios</title>
    <link rel="icon" type="image/png" href="../assets/img/compumasterldlogo.png">
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Registrar usuario</h5>
            <a href="usuarios.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../controllers/procesar_registro.php" method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Nombres</label>
                    <input type="text" class="form-control" name="nombre" placeholder="Nombres" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Apellidos</label>
                    <input type="text" class="form-control" name="apellido" placeholder="Apellidos" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Número de documento</label>
                    <input type="text" class="form-control" name="documento" placeholder="Documento" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" name="correo" placeholder="Correo electrónico" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="clave" id="password" placeholder="Mínimo 8 caracteres (letras y números)" minlength="8" pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$" title="Debe contener al menos 8 caracteres, incluyendo letras y números" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="mostrarContrasena()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <small class="text-muted">Mínimo 8 caracteres, debe incluir letras y números</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo de usuario</label>
                    <select class="form-select" name="rol" required>
                        <option value="" disabled selected>Seleccione una opción</option>
                        <option value="Admin">Admin</option>
                        <option value="Operario">Operario</option>
                    </select>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-circle me-1"></i> Registrarse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>

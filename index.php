<?php
include("config/conexion.php");
session_start();
include("config/csrf.php");
if (isset($_SESSION['usuario'])) {
    header("Location: menu.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Inicio de sesión</title>
    <link rel="icon" type="image/png" href="assets/img/compumasterldlogo.png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="login-body">

    <nav class="navbar navbar-dark navbar-custom px-3 px-md-4">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <img src="assets/img/sgi-software (1).png" alt="SGI" height="36" class="login-logo-img">
                <span class="fw-bold brand-text">SGI</span>
            </a>
            <span class="text-white-50 small d-none d-sm-inline">Sistema de Gestión Integral</span>
        </div>
    </nav>

    <div class="login-wrapper">
        <div class="card login-card shadow-lg border-0">
            <div class="card-body p-3 p-md-4">
                <div class="text-center mb-2">
                    <img src="assets/img/compumasterldlogo.png" alt="CompuMasterLD" class="login-center-logo mb-2">
                    <h5 class="fw-bold text-dark">Bienvenido</h5>
                    <p class="text-muted small mb-0">Inicia sesión para continuar</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center py-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center py-2" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($_GET['mensaje']); ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <form action="controllers/procesar_login.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="mb-2">
                        <label for="documento" class="form-label fw-semibold small">
                            <i class="bi bi-person me-1"></i> Documento
                        </label>
                        <input type="text" name="documento" id="documento" class="form-control login-input" placeholder="Ingrese su documento" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">
                            <i class="bi bi-lock me-1"></i> Contraseña
                        </label>
                        <div class="input-group">
                            <input type="password" name="clave" id="password" class="form-control login-input" placeholder="Ingrese su contraseña" required>
                            <span class="input-group-text login-input login-eye" onclick="mostrarContrasena()">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 login-btn py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar sesión
                    </button>
                </form>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="bi bi-shield-lock me-1"></i> Acceso restringido a usuarios autorizados
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>

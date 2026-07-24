<?php
include("config/conexion.php");
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
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

$rol = $_SESSION['rol'];
?>

<script>
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.remove();
            if (window.history.replaceState) {
                const url = window.location.href.split('?')[0];
                window.history.replaceState({}, document.title, url);
            }
        }, 500);
    });
}, 5000);
</script>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Sistema de Gestión Integral</title>
    <link rel="icon" type="image/png" href="assets/img/compumasterldlogo.png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="custom-body">

<?php include('views/includes/navbar.php'); ?>

<?php echo $mostrar_alerta; ?>

<div class="container py-3 dashboard-container">
    <div class="text-center mb-3">
        <div class="logo">
            <img src="assets/img/compumasterldlogo.png" alt="CompuMasterLD">
        </div>
        <h5 class="mt-2 fw-semibold">Bienvenido al Sistema</h5>
        <p class="text-muted small mb-0">Seleccione un módulo para continuar</p>
    </div>

    <div class="row g-2 justify-content-center">
        <?php if ($rol === 'Admin'): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="views/usuarios.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-usuarios">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-blue">
                            <i class="bi bi-person-gear"></i>
                        </div>
                        <h6 class="card-title mt-3">Usuarios</h6>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="views/clientes.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-clientes">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-teal">
                            <i class="bi bi-people"></i>
                        </div>
                        <h6 class="card-title mt-3">Clientes</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="views/proveedores.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-proveedores">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-green">
                            <i class="bi bi-building"></i>
                        </div>
                        <h6 class="card-title mt-3">Proveedores</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="views/productos.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-productos">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-orange">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h6 class="card-title mt-3">Productos</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="views/ventas.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-ventas">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-purple">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <h6 class="card-title mt-3">Ventas</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="views/orden_compra.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-ordenes">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-red">
                            <i class="bi bi-cart3"></i>
                        </div>
                        <h6 class="card-title mt-3">Compras</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="reports/informes.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-informes">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-indigo">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </div>
                        <h6 class="card-title mt-3">Informes</h6>
                    </div>
                </div>
            </a>
        </div>

        <?php if ($rol === 'Admin'): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="views/registro.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-registro">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-cyan">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h6 class="card-title mt-3">Registro</h6>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="views/reparaciones.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-reparaciones">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-cyan">
                            <i class="bi bi-tools"></i>
                        </div>
                        <h6 class="card-title mt-3">Soporte T&eacute;cnico</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="views/bitacora_comandos.php" class="text-decoration-none">
                <div class="card dashboard-card shadow-sm border-0 card-bitacora">
                    <div class="card-body text-center">
                        <div class="card-icon card-icon-indigo">
                            <i class="bi bi-command"></i>
                        </div>
                        <h6 class="card-title mt-3">Comandos</h6>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>

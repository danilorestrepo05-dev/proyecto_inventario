<?php
include("config/conexion.php");
session_start();

// Verificar si el usuario ha iniciado sesión
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

// Obtener el rol del usuario desde la sesión
$rol = $_SESSION['rol'];
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
    <title>SGI - Sistema de Gestión de Inventarios</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        .dashboard-container {
            max-width: 1100px;
            margin: auto;
        }
        .dashboard-card {
            border-radius: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-align: center;
            padding: 15px;
            height: 140px;
        }
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .dashboard-card h6 {
            margin-top: 10px;
            font-weight: bold;
            font-size: 14px;
        }
        .logo img {
            max-width: 90px;
        }
    </style>
</head>
<body class="custom-body">
<?php echo $mostrar_alerta; ?>
    <div class="container py-4 dashboard-container">
        <div class="text-center mb-4">
            <div class="logo">
                <img src="assets/img/sgi-software (1).png" alt="SGI">
            </div>
            <h2 class="mt-2">Bienvenido al Sistema</h2>
            <p class="text-muted">Seleccione un módulo para continuar</p>
        </div>

        <!-- Opciones en cuadrícula -->
        <div class="row g-3">
            <?php if ($rol === 'Admin'): ?>
            <div class="col-6 col-md-3">
                <a href="views/usuarios.php" class="text-decoration-none text-dark">
                    <div class="card dashboard-card shadow-sm">
                        <div class="card-body">
                            <span style="font-size:30px;">👤</span>
                            <h6>Usuarios</h6>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <div class="col-6 col-md-3">
                <a href="views/proveedores.php" class="text-decoration-none text-dark">
                    <div class="card dashboard-card shadow-sm">
                        <div class="card-body">
                            <span style="font-size:30px;">🏢</span>
                            <h6>Proveedores</h6>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="views/productos.php" class="text-decoration-none text-dark">
                    <div class="card dashboard-card shadow-sm">
                        <div class="card-body">
                            <span style="font-size:30px;">📦</span>
                            <h6>Productos</h6>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="views/ventas.php" class="text-decoration-none text-dark">
                    <div class="card dashboard-card shadow-sm">
                        <div class="card-body">
                            <span style="font-size:30px;">💰</span>
                            <h6>Ventas</h6>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="views/clientes.php" class="text-decoration-none text-dark">
                    <div class="card dashboard-card shadow-sm">
                        <div class="card-body">
                            <span style="font-size:30px;">👥</span>
                            <h6>Clientes</h6>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="views/orden_compra.php" class="text-decoration-none text-dark">
                    <div class="card dashboard-card shadow-sm">
                        <div class="card-body">
                            <span style="font-size:30px;">🛒</span>
                            <h6>Órdenes</h6>
                        </div>
                    </div>
                </a>
            </div>

            <?php if ($rol === 'Admin'): ?>
            <div class="col-6 col-md-3">
                <a href="views/registro.php" class="text-decoration-none text-dark">
                    <div class="card dashboard-card shadow-sm">
                        <div class="card-body">
                            <span style="font-size:30px;">➕</span>
                            <h6>Registro</h6>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <div class="col-6 col-md-3">
                <a href="reports/informes.php" class="text-decoration-none text-dark">
                    <div class="card dashboard-card shadow-sm">
                        <div class="card-body">
                            <span style="font-size:30px;">📊</span>
                            <h6>Informes</h6>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <form action="controllers/cerrar_sesion.php" method="POST" class="mt-3 text-center">
            <button class="btn btn-danger px-4">Cerrar sesión</button>
        </form>
    </div>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
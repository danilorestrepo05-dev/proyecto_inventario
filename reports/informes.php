<?php
include("../config/conexion.php");
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes - Sistema de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body class="custom-body">
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-12">
                <div class="container mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="bi bi-file-earmark-text"></i> Módulo de Informes</h2>
                        <a href="../menu.php" class="btn btn-secondary rounded-pill">
                            <i class=""></i> Volver al Menú
                        </a>
                    </div>

                    <div class="row g-4">
                        <!-- Tarjeta Informe de Productos -->
                        <div class="col-md-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bi bi-box-seam text-primary" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="card-title">Informe de Productos</h5>
                                    <p class="card-text text-muted">
                                        Consulta el inventario completo de productos con su stock, y precio .
                                    </p>
                                    <a href="informe_productos.php" class="btn btn-primary w-100">
                                        <i class="bi bi-file-earmark-bar-graph"></i> Ver Informe
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta Informe de Ventas -->
                        <div class="col-md-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bi bi-cart-check text-success" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="card-title">Informe de Ventas</h5>
                                    <p class="card-text text-muted">
                                        Revisa las ventas realizadas con filtros por fecha y estado.
                                    </p>
                                    <a href="informe_ventas.php" class="btn btn-success w-100">
                                        <i class="bi bi-file-earmark-bar-graph"></i> Ver Informe
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta Informe de Compras -->
                        <div class="col-md-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bi bi-bag-check text-warning" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="card-title">Informe de Compras</h5>
                                    <p class="card-text text-muted">
                                        Consulta las órdenes de compra realizadas a proveedores.
                                    </p>
                                    <a href="informe_compras.php" class="btn btn-warning w-100">
                                        <i class="bi bi-file-earmark-bar-graph"></i> Ver Informe
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas Rápidas -->
                    <div class="row mt-5">
                        <div class="col-md-12">
                            <h4 class="mb-3">Estadísticas Generales</h4>
                        </div>
                        
                        <?php
                        // Total de productos
                        $query_productos = "SELECT COUNT(*) as total FROM producto";
                        $result_productos = mysqli_query($conn, $query_productos);
                        $total_productos = mysqli_fetch_assoc($result_productos)['total'];

                        // Total de ventas completadas
                        $query_ventas = "SELECT COUNT(*) as total, SUM(total) as monto FROM orden_venta WHERE estado = 'completada'";
                        $result_ventas = mysqli_query($conn, $query_ventas);
                        $data_ventas = mysqli_fetch_assoc($result_ventas);

                        // Total de compras aprobadas
                        $query_compras = "SELECT COUNT(*) as total, SUM(total) as monto FROM orden_compra WHERE estado = 'Aprobado'";
                        $result_compras = mysqli_query($conn, $query_compras);
                        $data_compras = mysqli_fetch_assoc($result_compras);

                        // Productos con bajo stock (menos de 10)
                        $query_bajo_stock = "SELECT COUNT(*) as total FROM producto WHERE stock < 10";
                        $result_bajo_stock = mysqli_query($conn, $query_bajo_stock);
                        $bajo_stock = mysqli_fetch_assoc($result_bajo_stock)['total'];
                        ?>

                        <div class="col-md-3">
                            <div class="card text-white bg-primary shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title">Total Productos</h6>
                                    <h2><?php echo $total_productos; ?></h2>
                                    <small>Productos registrados</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card text-white bg-success shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title">Ventas Completadas</h6>
                                    <h2><?php echo $data_ventas['total']; ?></h2>
                                    <small>$<?php echo number_format($data_ventas['monto'], 0, ',', '.'); ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card text-white bg-warning shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title">Compras Aprobadas</h6>
                                    <h2><?php echo $data_compras['total']; ?></h2>
                                    <small>$<?php echo number_format($data_compras['monto'], 0, ',', '.'); ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card text-white bg-danger shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title">Stock Bajo</h6>
                                    <h2><?php echo $bajo_stock; ?></h2>
                                    <small>Productos con stock < 10</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
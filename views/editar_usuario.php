<?php 
session_start();
include("../config/conexion.php");
include("../config/csrf.php");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../menu.php");
    exit();
}

$codigo = intval($_REQUEST['id']);
$consulta = "SELECT * FROM usuario WHERE ID_usuario = ?";
$stmt = $conn->prepare($consulta);
$stmt->bind_param("i", $codigo);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

if ($resultado->num_rows == 0) { echo "Usuario no encontrado."; exit; }
$fila_usuario = $resultado->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <title>Editar Usuario</title>
</head>
<body class="custom-body">
<?php $nav_base = '..'; include('includes/navbar.php'); ?>

<div class="container py-4">
    <div class="form-card card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editar Usuario</h5>
            <a href="usuarios.php" class="btn btn-sm btn-outline-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
        <div class="card-body p-4">
            <form action="../controllers/procesar_edicion_usuario.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($fila_usuario['ID_usuario']); ?>">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($fila_usuario['nombre']); ?>" placeholder="Nombres" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Apellido</label>
                    <input type="text" class="form-control" name="apellido" value="<?php echo htmlspecialchars($fila_usuario['apellido']); ?>" placeholder="Apellidos" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control" name="correo" value="<?php echo htmlspecialchars($fila_usuario['correo']); ?>" placeholder="Correo electrónico" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo de usuario</label>
                    <select class="form-select" name="rol" required>
                        <option value="" disabled>Seleccione una opción</option>
                        <option value="Admin" <?php echo $fila_usuario['rol'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="Operario" <?php echo $fila_usuario['rol'] === 'Operario' ? 'selected' : ''; ?>>Operario</option>
                    </select>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-circle me-1"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
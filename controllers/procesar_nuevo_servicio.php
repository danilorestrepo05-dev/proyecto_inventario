<?php
// Controlador que crea un servicio completo (servicio + dispositivo + trabajo) en una sola transacción
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

// Redirigir al login si el usuario no está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Bloquear la petición si el token CSRF no coincide con el de sesión
if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/reparaciones.php?error=Token CSRF invalido");
    exit();
}

// Sanitizar y castear cada campo del formulario antes de usarlo en BD
$cliente = intval($_POST['cliente']);
$tecnico = intval($_POST['tecnico']) ?: intval($_SESSION['id_usuario']);
$nombre_servicio = trim($_POST['nombre_servicio'] ?? '');
$dispositivo = trim($_POST['dispositivo']);
$marca = trim($_POST['marca']);
$modelo = trim($_POST['modelo']);
$numero_serie = trim($_POST['numero_serie']);
$tipo_trabajo = trim($_POST['tipo_trabajo'] ?? 'General');
$problema_reportado = trim($_POST['problema_reportado']);
$notas_internas = trim($_POST['notas_internas']);

// Validar campos obligatorios: cliente, dispositivo y problema reportado
if ($cliente <= 0) {
    mysqli_close($conn);
    echo "<script>alert('Error: Debe seleccionar un cliente valido'); window.history.back();</script>";
    exit();
}

if (empty($dispositivo)) {
    mysqli_close($conn);
    echo "<script>alert('Error: El nombre del dispositivo es obligatorio'); window.history.back();</script>";
    exit();
}

if (empty($problema_reportado)) {
    mysqli_close($conn);
    echo "<script>alert('Error: El problema reportado es obligatorio'); window.history.back();</script>";
    exit();
}

// Iniciar transacción para garantizar que servicio + dispositivo + trabajo se creen juntos o ninguno
$conn->begin_transaction();

try {
    // Crear servicio
    $sql_serv = "INSERT INTO servicio (ID_cliente, ID_usuario_tecnico, nombre, notas_internas) VALUES (?, ?, ?, ?)";
    $stmt_serv = @$conn->prepare($sql_serv);
    if (!$stmt_serv) throw new Exception("Error preparando servicio: " . $conn->error);
    $stmt_serv->bind_param("iiss", $cliente, $tecnico, $nombre_servicio, $notas_internas);
    if (!$stmt_serv->execute()) throw new Exception("Error creando servicio: " . $conn->error);
    $id_servicio = $conn->insert_id;
    $stmt_serv->close();

    // Crear dispositivo
    $sql_disp = "INSERT INTO dispositivo_servicio (ID_servicio, dispositivo, marca, modelo, numero_serie) VALUES (?, ?, ?, ?, ?)";
    $stmt_disp = @$conn->prepare($sql_disp);
    if (!$stmt_disp) throw new Exception("Error preparando dispositivo: " . $conn->error);
    $stmt_disp->bind_param("issss", $id_servicio, $dispositivo, $marca, $modelo, $numero_serie);
    if (!$stmt_disp->execute()) throw new Exception("Error creando dispositivo: " . $conn->error);
    $id_dispositivo = $conn->insert_id;
    $stmt_disp->close();

    // Crear trabajo
    $sql_trab = "INSERT INTO trabajo (ID_dispositivo, tipo_trabajo, problema_reportado, notas_internas) VALUES (?, ?, ?, ?)";
    $stmt_trab = @$conn->prepare($sql_trab);
    if (!$stmt_trab) throw new Exception("Error preparando trabajo: " . $conn->error);
    $stmt_trab->bind_param("isss", $id_dispositivo, $tipo_trabajo, $problema_reportado, $notas_internas);
    if (!$stmt_trab->execute()) throw new Exception("Error creando trabajo: " . $conn->error);
    $id_trabajo = $conn->insert_id;
    $stmt_trab->close();

    // Confirmar los 3 inserts en BD y registrar la auditoría
    $conn->commit();

    registrar_cambio($conn, 'servicio', 'crear', $id_servicio, 'Servicio "' . $nombre_servicio . '" creado - Dispositivo: ' . $dispositivo . ' ' . trim($marca . ' ' . $modelo));
    mysqli_close($conn);

    header("Location: ../views/detalle_servicio.php?id=$id_servicio&mensaje=Servicio registrado correctamente");
    exit();

} catch (Exception $e) {
    // Si cualquier paso falla, revertir todos los cambios para mantener integridad
    $conn->rollback();
    mysqli_close($conn);
    echo "<script>alert('" . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    exit();
}

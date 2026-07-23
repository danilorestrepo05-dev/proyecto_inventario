<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/bitacora_comandos.php?error=Token CSRF inválido");
    exit();
}

$accion = $_POST['accion'] ?? 'crear';
$comando = trim($_POST['comando']);
$sistema_operativo = trim($_POST['sistema_operativo']);
$descripcion = trim($_POST['descripcion']);
$categoria = $_POST['categoria'];

$categorias_permitidas = ['optimizacion', 'redes', 'limpieza', 'diagnostico', 'atajo'];

if (empty($comando) || empty($sistema_operativo) || empty($descripcion)) {
    mysqli_close($conn);
    echo "<script>alert('Error: Todos los campos son obligatorios'); window.history.back();</script>";
    exit();
}

if (!in_array($categoria, $categorias_permitidas)) {
    mysqli_close($conn);
    echo "<script>alert('Error: Categoría no válida'); window.history.back();</script>";
    exit();
}

if ($accion === 'editar') {
    $id_comando = intval($_POST['id_comando']);
    $sql = "UPDATE bitacora_conocimiento SET comando=?, sistema_operativo=?, descripcion=?, categoria=? WHERE ID_comando=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $comando, $sistema_operativo, $descripcion, $categoria, $id_comando);
    $mensaje = 'Comando actualizado correctamente';
    $registro_accion = 'editar';
    $registro_id = $id_comando;
} else {
    $sql = "INSERT INTO bitacora_conocimiento (comando, sistema_operativo, descripcion, categoria) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $comando, $sistema_operativo, $descripcion, $categoria);
    $mensaje = 'Comando registrado correctamente';
    $registro_accion = 'crear';
    $registro_id = 0;
}

if ($stmt->execute()) {
    if ($registro_accion === 'crear') {
        $registro_id = $conn->insert_id;
    }
    registrar_cambio($conn, 'bitacora_conocimiento', $registro_accion, $registro_id, 'Comando "' . $comando . '" ' . $registro_accion . 'do');
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/bitacora_comandos.php?mensaje=" . urlencode($mensaje));
    exit();
} else {
    echo "Error al procesar el comando: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}
?>

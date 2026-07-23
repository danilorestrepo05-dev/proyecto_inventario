<?php
function registrar_cambio($conn, $modulo, $accion, $ID_registro, $descripcion) {
    if (!isset($_SESSION['id_usuario'])) return;
    $stmt = @$conn->prepare("INSERT INTO historial_cambios (ID_usuario, modulo, accion, ID_registro, descripcion) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) return;
    $id_usuario = $_SESSION['id_usuario'];
    $stmt->bind_param("issis", $id_usuario, $modulo, $accion, $ID_registro, $descripcion);
    @$stmt->execute();
    $stmt->close();
}

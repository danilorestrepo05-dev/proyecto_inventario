<?php
// Controlador para editar un trabajo de reparación existente, incluyendo gestión de garantía y bitácora
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/reparaciones.php?error=Token CSRF invalido");
    exit();
}

$id_trabajo = intval($_POST['id_trabajo']);
$tipo_trabajo = trim($_POST['tipo_trabajo'] ?? 'General');
$dispositivo = trim($_POST['dispositivo']);
$marca = trim($_POST['marca']);
$modelo = trim($_POST['modelo']);
$numero_serie = trim($_POST['numero_serie']);
$problema_reportado = trim($_POST['problema_reportado']);
$diagnostico = trim($_POST['diagnostico']);
$estado = $_POST['estado'];
$mano_obra_costo = floatval($_POST['mano_obra_costo']);
$notas_internas = trim($_POST['notas_internas']);
$garantia_dias = intval($_POST['garantia_dias']);

// Definir y validar los estados posibles del ciclo de vida de un trabajo
$estados_permitidos = ['ingresado', 'diagnosticado', 'en_progreso', 'reparado', 'entregado', 'cancelado'];
if (!in_array($estado, $estados_permitidos)) {
    mysqli_close($conn);
    echo "<script>alert('Error: Estado no valido'); window.history.back();</script>";
    exit();
}

// Obtener el estado actual para compararlo con el nuevo y detectar cambios
$sql_estado = "SELECT t.estado, t.ID_dispositivo FROM trabajo t WHERE t.ID_trabajo = ?";
$stmt_estado = $conn->prepare($sql_estado);
$stmt_estado->bind_param("i", $id_trabajo);
$stmt_estado->execute();
$result_estado = $stmt_estado->get_result();
$fila_estado = $result_estado->fetch_assoc();
$estado_anterior = $fila_estado['estado'];
$id_dispositivo = $fila_estado['ID_dispositivo'];
$stmt_estado->close();

// Actualizar los datos principales del trabajo (diagnóstico, estado, costos, notas)
$sql = "UPDATE trabajo SET tipo_trabajo=?, problema_reportado=?, diagnostico=?, estado=?, mano_obra_costo=?, notas_internas=? WHERE ID_trabajo=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssdsi", $tipo_trabajo, $problema_reportado, $diagnostico, $estado, $mano_obra_costo, $notas_internas, $id_trabajo);

if ($stmt->execute()) {
    // Sincronizar los datos del dispositivo asociado al trabajo
    $sql_disp = "UPDATE dispositivo_servicio SET dispositivo=?, marca=?, modelo=?, numero_serie=? WHERE ID_dispositivo=?";
    $stmt_disp = $conn->prepare($sql_disp);
    $stmt_disp->bind_param("ssssi", $dispositivo, $marca, $modelo, $numero_serie, $id_dispositivo);
    $stmt_disp->execute();
    $stmt_disp->close();

    // Registrar en bitácora solo si el estado realmente cambió
    if ($estado_anterior !== $estado) {
        $sql_bitacora = "INSERT INTO bitacora_reparacion (ID_trabajo, ID_usuario, estado_anterior, estado_nuevo, observacion) VALUES (?, ?, ?, ?, ?)";
        $stmt_bitacora = $conn->prepare($sql_bitacora);
        $id_usuario = intval($_SESSION['id_usuario']);
        $observacion = 'Estado cambiado de ' . $estado_anterior . ' a ' . $estado;
        $stmt_bitacora->bind_param("iisss", $id_trabajo, $id_usuario, $estado_anterior, $estado, $observacion);
        $stmt_bitacora->execute();
        $stmt_bitacora->close();
    }

    // Marcar la fecha de entrega cuando el trabajo pasa a estado "entregado"
    if ($estado === 'entregado' && $estado_anterior !== 'entregado') {
        $sql_entrega = "UPDATE trabajo SET fecha_entrega = NOW() WHERE ID_trabajo = ?";
        $stmt_entrega = $conn->prepare($sql_entrega);
        $stmt_entrega->bind_param("i", $id_trabajo);
        $stmt_entrega->execute();
        $stmt_entrega->close();
    }

    // Crear o actualizar la garantía del trabajo según los días indicados
    if ($garantia_dias > 0) {
        $sql_check_gar = "SELECT ID_garantia FROM garantia WHERE ID_trabajo = ?";
        $stmt_check = $conn->prepare($sql_check_gar);
        $stmt_check->bind_param("i", $id_trabajo);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $existe_gar = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($existe_gar) {
            $sql_garantia = "UPDATE garantia SET dias=?, fecha_inicio=CURDATE(), fecha_fin=DATE_ADD(CURDATE(), INTERVAL ? DAY) WHERE ID_trabajo=?";
            $stmt_garantia = $conn->prepare($sql_garantia);
            $stmt_garantia->bind_param("iii", $garantia_dias, $garantia_dias, $id_trabajo);
        } else {
            $sql_garantia = "INSERT INTO garantia (ID_trabajo, dias, fecha_inicio, fecha_fin) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY))";
            $stmt_garantia = $conn->prepare($sql_garantia);
            $stmt_garantia->bind_param("iii", $id_trabajo, $garantia_dias, $garantia_dias);
        }
        $stmt_garantia->execute();
        $stmt_garantia->close();
    } else {
        // Si el usuario puso 0 días, eliminar la garantía existente
        $sql_del_gar = "DELETE FROM garantia WHERE ID_trabajo = ?";
        $stmt_del = @$conn->prepare($sql_del_gar);
        if ($stmt_del) {
            $stmt_del->bind_param("i", $id_trabajo);
            $stmt_del->execute();
            $stmt_del->close();
        }
    }

    // Buscar el ID del servicio padre para registrar el historial
    $sql_serv = "SELECT ds.ID_servicio FROM dispositivo_servicio ds WHERE ds.ID_dispositivo=?";
    $stmt_serv = $conn->prepare($sql_serv);
    $stmt_serv->bind_param("i", $id_dispositivo);
    $stmt_serv->execute();
    $res_serv = $stmt_serv->get_result();
    $row_serv = $res_serv->fetch_assoc();
    $stmt_serv->close();
    $id_servicio = $row_serv['ID_servicio'];

    // Recalcular el total de mano de obra del servicio sumando el de todos sus trabajos
    $sql_sum_mo = "SELECT COALESCE(SUM(t.mano_obra_costo), 0) AS total_mo
                   FROM trabajo t
                   INNER JOIN dispositivo_servicio ds ON ds.ID_dispositivo = t.ID_dispositivo
                   WHERE ds.ID_servicio = ?";
    $stmt_sum = $conn->prepare($sql_sum_mo);
    $stmt_sum->bind_param("i", $id_servicio);
    $stmt_sum->execute();
    $res_sum = $stmt_sum->get_result();
    $fila_sum = $res_sum->fetch_assoc();
    $stmt_sum->close();
    $total_mano_obra = floatval($fila_sum['total_mo']);

    // Sincronizar el valor total en el servicio para que el PDF y los resúmenes lo reflejen
    $sql_upd_mo = "UPDATE servicio SET mano_obra_costo = ? WHERE ID_servicio = ?";
    $stmt_upd = $conn->prepare($sql_upd_mo);
    $stmt_upd->bind_param("di", $total_mano_obra, $id_servicio);
    $stmt_upd->execute();
    $stmt_upd->close();

    registrar_cambio($conn, 'servicio', 'editar', $id_servicio, 'Trabajo #' . $id_trabajo . ' actualizado - Estado: ' . $estado . ' - Diagnóstico: ' . substr($diagnostico, 0, 60));
    $stmt->close();
    mysqli_close($conn);
    header("Location: ../views/editar_trabajo.php?id=$id_trabajo&mensaje=Trabajo actualizado correctamente#tab-info");
    exit();
} else {
    echo "Error al actualizar el trabajo: " . $conn->error;
    $stmt->close();
    mysqli_close($conn);
}

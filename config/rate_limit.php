<?php
define('MAX_INTENTOS', 5);
define('BLOQUEO_MINUTOS', 15);

function verificar_bloqueo($conn, $ip, $documento) {
    $stmt = $conn->prepare("SELECT intentos, bloqueado_hasta FROM login_attempts WHERE ip_address = ? AND documento = ?");
    $stmt->bind_param("ss", $ip, $documento);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $stmt->close();

    if (!$fila) return true;

    if ($fila['bloqueado_hasta'] !== null) {
        $ahora = new DateTime();
        $bloqueado = new DateTime($fila['bloqueado_hasta']);
        if ($ahora < $bloqueado) {
            $restante = $ahora->diff($bloqueado)->i;
            return "Demasiados intentos fallidos. Intenta de nuevo en {$restante} minuto(s).";
        }
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND documento = ?");
        $stmt->bind_param("ss", $ip, $documento);
        $stmt->execute();
        $stmt->close();
    }

    return true;
}

function registrar_intento_fallido($conn, $ip, $documento) {
    $stmt = $conn->prepare("SELECT intentos FROM login_attempts WHERE ip_address = ? AND documento = ?");
    $stmt->bind_param("ss", $ip, $documento);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $stmt->close();

    if ($fila) {
        $nuevos_intentos = $fila['intentos'] + 1;
        if ($nuevos_intentos >= MAX_INTENTOS) {
            $hasta = date('Y-m-d H:i:s', strtotime('+' . BLOQUEO_MINUTOS . ' minutes'));
            $stmt = $conn->prepare("UPDATE login_attempts SET intentos = ?, ultimo_intento = NOW(), bloqueado_hasta = ? WHERE ip_address = ? AND documento = ?");
            $stmt->bind_param("isss", $nuevos_intentos, $hasta, $ip, $documento);
        } else {
            $stmt = $conn->prepare("UPDATE login_attempts SET intentos = ?, ultimo_intento = NOW() WHERE ip_address = ? AND documento = ?");
            $stmt->bind_param("iss", $nuevos_intentos, $ip, $documento);
        }
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, documento, intentos) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $ip, $documento);
        $stmt->execute();
        $stmt->close();
    }
}

function limpiar_intento($conn, $ip, $documento) {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND documento = ?");
    $stmt->bind_param("ss", $ip, $documento);
    $stmt->execute();
    $stmt->close();
}

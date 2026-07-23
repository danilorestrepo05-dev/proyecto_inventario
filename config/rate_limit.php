<?php
// Límites de seguridad para prevenir fuerza bruta en el login
define('MAX_INTENTOS', 5);
define('BLOQUEO_MINUTOS', 15);

// Verifica si la IP+documento están bloqueados; retorna true si permitido o string de error si bloqueado
function verificar_bloqueo($conn, $ip, $documento) {
    $stmt = $conn->prepare("SELECT intentos, bloqueado_hasta FROM login_attempts WHERE ip_address = ? AND documento = ?");
    $stmt->bind_param("ss", $ip, $documento);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $stmt->close();

    if (!$fila) return true;

    // Si ya existe un bloqueo activo, calcula el tiempo restante y lo informa
    if ($fila['bloqueado_hasta'] !== null) {
        $ahora = new DateTime();
        $bloqueado = new DateTime($fila['bloqueado_hasta']);
        if ($ahora < $bloqueado) {
            $restante = $ahora->diff($bloqueado)->i;
            return "Demasiados intentos fallidos. Intenta de nuevo en {$restante} minuto(s).";
        }
        // Si el bloqueo ya expiró, limpia el registro para permitir nuevos intentos
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND documento = ?");
        $stmt->bind_param("ss", $ip, $documento);
        $stmt->execute();
        $stmt->close();
    }

    return true;
}

// Registra cada login fallido; si alcanza el máximo, aplica bloqueo temporal
function registrar_intento_fallido($conn, $ip, $documento) {
    $stmt = $conn->prepare("SELECT intentos FROM login_attempts WHERE ip_address = ? AND documento = ?");
    $stmt->bind_param("ss", $ip, $documento);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $stmt->close();

    if ($fila) {
        $nuevos_intentos = $fila['intentos'] + 1;
        // Si alcanza el máximo de intentos, calcula la fecha de desbloqueo y guarda el bloqueo
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
        // Primera vez que falla esta IP+documento, crea un nuevo registro con 1 intento
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, documento, intentos) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $ip, $documento);
        $stmt->execute();
        $stmt->close();
    }
}

// Elimina todos los registros de intentos para una IP+documento tras login exitoso
function limpiar_intento($conn, $ip, $documento) {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND documento = ?");
    $stmt->bind_param("ss", $ip, $documento);
    $stmt->execute();
    $stmt->close();
}

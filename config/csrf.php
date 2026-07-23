<?php

// Asegurar que la sesión esté iniciada antes de usar tokens CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Genera un token único de 64 caracteres y lo almacena en sesión para reuse
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Retorna el campo hidden HTML que se inyecta en cada formulario protegido
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Compara el token enviado con el de sesión usando comparación segura contra timing attacks
function csrf_validate($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

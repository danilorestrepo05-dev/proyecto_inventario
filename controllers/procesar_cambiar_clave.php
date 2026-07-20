<?php
session_start();
include("../config/conexion.php");
include("../config/csrf.php");
include("../config/historial.php");

// Verificar que sea Admin
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        header("Location: ../views/usuarios.php?error=Token CSRF inválido");
        exit();
    }

    $id_usuario = intval($_POST['ID_usuario']);
    $nueva_clave = $_POST['nueva_clave'];
    $confirmar_clave = isset($_POST['confirmar_clave']) ? $_POST['confirmar_clave'] : '';
    
    // VALIDACIONES DE SEGURIDAD
    
    // Verificar las contraseñas 
    if ($nueva_clave !== $confirmar_clave) {
        mysqli_close($conn);
        echo "<script>
                alert('Error: Las contraseñas no coinciden');
                window.history.back();
              </script>";
        exit();
    }
    
    // 1. Validar longitud mínima (8 caracteres)
    if (strlen($nueva_clave) < 8) {
        mysqli_close($conn);
        echo "<script>
                alert('Error: La contraseña debe tener al menos 8 caracteres');
                window.history.back();
              </script>";
        exit();
    }
    
    // 2. Validar que contenga letras y números
    if (!preg_match('/[A-Za-z]/', $nueva_clave) || !preg_match('/[0-9]/', $nueva_clave)) {
        mysqli_close($conn);
        echo "<script>
                alert('Error: La contraseña debe contener letras y números');
                window.history.back();
              </script>";
        exit();
    }
    
    // CIFRAR LA NUEVA CONTRASEÑA
    $clave_cifrada = password_hash($nueva_clave, PASSWORD_DEFAULT);
    
    // ACTUALIZAR EN LA BASE DE DATOS
    $sql = "UPDATE usuario SET clave = ? WHERE ID_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $clave_cifrada, $id_usuario);
    
    if ($stmt->execute()) {
        registrar_cambio($conn, 'usuario', 'editar', $id_usuario, 'Contraseña del usuario actualizada');
        $stmt->close();
        mysqli_close($conn);
        header("Location: ../views/usuarios.php?mensaje=Contraseña actualizada correctamente");
        exit();
    } else {
        $stmt->close();
        mysqli_close($conn);
        echo "Error al actualizar la contraseña: " . $conn->error;
    }
} else {
    header("Location: ../views/usuarios.php");
    exit();
}
?>
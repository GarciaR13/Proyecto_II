<?php
// Iniciamos sesión antes de cualquier salida de texto
session_start();

include 'conexion.php';

//Inicio de verificación
// Verificamos que esté conectado y que el rol asignado en index.php sea 'admin'
if (!isset($_SESSION['Conectado']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    // Si no es admin, lo mandamos de vuelta al login
    header('Location: prototipo_login.php'); 
    exit();
}
//fin de verificación

$mensaje = '';

//POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Eliminar un usuario
    if ($accion === "eliminar_usuario") {
        $id = intval($_POST["id_usuario"] ?? 0);
        
        /*Validación de seguridad*/
        // Primero consultamos el correo del usuario que se intenta borrar
        $check = $conexion->prepare("SELECT correo FROM usuarios WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $resCheck = $check->get_result();
        $userABorrar = $resCheck->fetch_assoc();
        
        // Evitar que el admin se elimine a sí mismo por error (comparando correo y sesión)
        if ($id > 0 && ($userABorrar['correo'] ?? '') !== "admin@correo.com") {
             $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
             $stmt->bind_param("i", $id);
             if ($stmt->execute()) {
                 $mensaje = "Usuario eliminado con éxito.";
             } else {
                 $mensaje = "Error al eliminar: " . $conexion->error;
             }
             $stmt->close();
        } else {
             $mensaje = "No se puede eliminar la cuenta principal de administrador.";
        }
        $check->close();
    }

    // Editar usuario (Nombre y Correo)
    if ($accion === "editar_usuario_admin") {
        $id = intval($_POST["id"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? '');
        $correo = trim($_POST["correo"] ?? '');
        
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, correo = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $correo, $id);
        if ($stmt->execute()) {
            $mensaje = "Datos del usuario actualizados.";
        } else {
            $mensaje = "Error al actualizar: " . $conexion->error;
        }
        $stmt->close();
    }
}

// Consulta para mostrar la tabla
$resUsuarios = $conexion->query("SELECT id, nombre, correo, img, rol FROM usuarios");
?>
<?php
//iniciar la sesión
session_start();
//incluir la conexión creada
include 'conexion.php';

//error sólo declarado
$error = '';
//Para el requets de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //trim es para eliminar espacios en blanco al incio y al final
    $correo = trim($_POST['correo']);
    $contra = $_POST['contra'];

    //Esto es para indicarle a MYQSL la consulta que va a realizar, obtendremos con el correo
    $stmt = $conexion->prepare("SELECT contra FROM usuarios WHERE correo = ?");
    //aquí se indica el tipo de dato que se está enviando para revisar
    $stmt->bind_param('s', $correo);
    //se lanza la consulta
    $stmt->execute();
    //guarda los datos
    $res = $stmt->get_result(); 

    //NOTA ORIGINAL:
    /* Ahora, al tener los datos del select, la contra es comparada para poder revisar si tiene similitud
    con el dato que estamos agregando, con el que esta encriptado en la base de datos*/
    //filas devueltas sea 1
    if ($res && $res->num_rows === 1) {
        //obtener la fila
        $row = $res->fetch_assoc();
        //extraer el hash de la contraseña
        $hash = $row['contra'];

        //si la contraseña entrada y la de la Base de datos funciona
        if (password_verify($contra, $hash)) {
            session_regenerate_id(true); //autenticación de la sesión
            //datos de la sesión
            $_SESSION['Conectado'] = '1234567890';
            $_SESSION['correo'] = $correo;

            /*Inicio de edición Gabriel*/
            
            // Definimos el correo que tendrá privilegios de administrador
            $correoAdmin = "admin@correo.com"; 

            if ($correo === $correoAdmin) {
                $_SESSION['rol'] = 'admin';
                // Redirigir al Panel de Administración 
                header('Location: admin.php');
            } else {
                $_SESSION['rol'] = 'cliente';
                // Redirigir al catálogo normal
                header('Location: prototipo_catalogo.php');
            }
            
            /*Fin de Edición Gabriel*/
            
            exit();
        } else {
            $error = 'Contraseña incorrecta.';
            header('Location: prototipo_login.php');
            exit();
        }
    } else {
        $error = 'Usuario no encontrado.';
        header('Location: prototipo_login.php');
        exit();
    }
}
?>
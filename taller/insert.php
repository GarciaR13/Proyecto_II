<?php
include 'conexion.php';

       $nombre_usuario = $_POST["nombre"];
       $contra = password_hash($_POST["contra"], PASSWORD_DEFAULT);
       $nombre_imagen = $_FILES["img"]["name"];
       $ruta_temp = $_FILES["img"]["tmp_name"];
       $ruta_destino = "images/" . basename($nombre_imagen);

       if (move_uploaded_file($ruta_temp, $ruta_destino)) {
           $sql = "INSERT INTO usuarios (nombre, contra, img) VALUES ('$nombre_usuario', '$contra', '$ruta_destino')";
           if ($conexion->query($sql) === TRUE) {
                header("Location: main.php");
                exit();
           } else {
                 echo "Error al insertar: " . $conexion->error;
           }
        } else {
               echo "Error al subir la imagen.";
        }

       $conexion->close();
?>

<?php
session_start();
if (!isset($_SESSION['nombre']) || ($_SESSION['Conectado'] ?? '') !== '1234567890') {
    header('Location: index.php');
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

<div style="height: 100%; width: 200px; background-color: rgb(82, 216, 33); position: fixed; margin-top: 0%; margin-left: 0%;"></div>
    
<form method="POST" enctype="multipart/form-data" action="insert.php" style="background-color:rgba(125, 125, 125, 1);padding: 50px;border-radius: 10px;width: 500px;box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                               font-family: Arial, sans-serif;margin-top: 100px; margin-bottom: 70px; margin-left: 37%;">
    <p style="font-size: 18px;font-weight: bold;color: #444;margin-bottom: 15px;">INGRESAR USUARIOS:</p>
    <input type="text" name="nombre" placeholder="Nombre de usuario" required style="width: 100%;padding: 10px;margin-bottom: 10px;border: 1px solid #ccc;
                                                                                     border-radius: 5px;font-size: 14px;"><br>
    <input type="password" name="contra" placeholder="Contraseña" required style="width: 100%;padding: 10px;margin-bottom: 15px;
                                                                                  border: 1px solid #ccc;border-radius: 5px;font-size: 14px;"><br>
    <input type="file" name="img" accept="image/*" required><br>                                                                          
    <input type="submit" value="Agregar usuario" style="width: 100%;padding: 10px;background-color: rgb(82, 216, 33); color: white;border: none;
                                                        border-radius: 5px;font-size: 16px;cursor: pointer;transition: background-color 0.3s ease;">
</form>

<form method="POST" enctype="multipart/form-data" action="update.php" style="background-color:rgba(125, 125, 125, 1);padding: 50px;border-radius: 10px;width: 500px;box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                               font-family: Arial, sans-serif;margin-top: 100px; margin-bottom: 70px; margin-left: 37%;">
    <p style="font-size: 18px;font-weight: bold;color: #444;margin-bottom: 15px;">INGRESAR USUARIOS:</p>
    <input type="text" name="nombre" placeholder="Nombre de usuario" required style="width: 100%;padding: 10px;margin-bottom: 10px;border: 1px solid #ccc;
                                                                                     border-radius: 5px;font-size: 14px;"><br>
    <input type="password" name="contra" placeholder="Contraseña" required style="width: 100%;padding: 10px;margin-bottom: 15px;
                                                                                  border: 1px solid #ccc;border-radius: 5px;font-size: 14px;"><br>
    <input type="file" name="img" accept="image/*" required><br>  
    
    <input type="id" name="id" placeholder="ID" required style="width: 100%;padding: 10px;margin-bottom: 15px;
                                                                                  border: 1px solid #ccc;border-radius: 5px;font-size: 14px;"><br>

    <input type="submit" value="Agregar usuario" style="width: 100%;padding: 10px;background-color: rgb(82, 216, 33); color: white;border: none;
                                                        border-radius: 5px;font-size: 16px;cursor: pointer;transition: background-color 0.3s ease;">
</form>

<?php 
include 'select.php'; 
?>

</body>
</html>
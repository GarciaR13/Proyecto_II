<?php

$conexion = new mysqli("btwbnpkxsuqozko5ov9i-mysql.services.clever-cloud.com",
 "ujghn3bst4qvf0gr", "rU8nt5pM9ACe78NoWgvP", "btwbnpkxsuqozko5ov9i");
if ($conexion->connect_error) {
    die("<p style='color:red;'>Error de conexión: " . $conexion->connect_error . "</p>");
}

?>
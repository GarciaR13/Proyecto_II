<?php
session_start();
session_destroy();
header("Location: prototipo_login.php");
exit();
?>
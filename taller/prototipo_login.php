<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Prototipo</title>

  <!-- Enlace a la hoja de estilos externa -->
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<?php if(!empty($error)): ?>
<div class ="alerta_error">
<?php echo $error; ?>
</div>
<?php endif; ?>
<main class="page">

<h1 class="text-center">Inicio de Sesión</h1>

<div class="card shadow p-4" style="width:350px">

<form method="POST" action="index.php">

<div class="mb-3">
<input type="text" name="correo" placeholder="Correo electrónico" required class="form-control">
</div>

<div class="mb-3">
<input type="password" name="contra" placeholder="Contraseña" required class="form-control">
</div>

<button type="submit" class="btn btn-primary w-100">
Iniciar sesión
</button>

</form>

</div>

</main>

</body>

</html>
<?php
session_start();
include 'conexion.php';

// Verificación de sesión
if (!isset($_SESSION['Conectado']) || $_SESSION['Conectado'] !== '1234567890') {
    header('Location: index.php');
    exit();
}

$mensaje = '';

// Post
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Eliminar un usuario
    if ($accion === "eliminar_usuario") {
        $id = intval($_POST["id_usuario"] ?? 0);
        if ($id > 0) {
            $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $mensaje = "Usuario eliminado con éxito.";
            } else {
                $mensaje = "Error al eliminar: " . $conexion->error;
            }
        }
    }

    // Editar usuario
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
    }
}

// Consultas vista
$resUsuarios = $conexion->query("SELECT id, nombre, correo, img FROM usuarios");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Paleta Blanco y Negro Estricta */
        body { 
            background-color: #ffffff; 
            color: #000000;
            font-family: sans-serif;
        }
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            background-color: #000000;
            padding-top: 20px;
            color: #ffffff;
        }
        .sidebar hr { border-top: 1px solid #ffffff; }
        .main-content {
            margin-left: 260px;
            padding: 30px;
        }
        .card-custom {
            background: #ffffff;
            border-radius: 0; /* Bordes rectos para un look más minimalista */
            border: 2px solid #000000;
            margin-bottom: 30px;
            padding: 20px;
        }
        .user-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #000000;
            filter: grayscale(100%); /* Fotos en blanco y negro */
        }
        /* Botones personalizados B&N */
        .btn-black {
            background-color: #000000;
            color: #ffffff;
            border: 1px solid #000000;
            border-radius: 0;
        }
        .btn-black:hover {
            background-color: #ffffff;
            color: #000000;
        }
        .btn-outline-black {
            background-color: #ffffff;
            color: #000000;
            border: 1px solid #000000;
            border-radius: 0;
        }
        .btn-outline-black:hover {
            background-color: #000000;
            color: #ffffff;
        }
        .table-dark { background-color: #000000 !important; }
        .alert-bn {
            border: 2px solid #000000;
            background: #ffffff;
            color: #000000;
            border-radius: 0;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4 class="text-center">ADMIN PANEL</h4>
        <hr>
        <div class="px-3">
            <p>Admin: <br><strong><?php echo htmlspecialchars($_SESSION['correo'] ?? 'ADMIN'); ?></strong></p>
            <a href="main.php" class="btn btn-outline-light w-100 mb-2" style="border-radius:0;">Registrar Usuario</a>
            <hr>
            <a href="logout.php" class="btn btn-light w-100" style="border-radius:0; color:#000;">Cerrar Sesión</a>
        </div>
    </div>

    <div class="main-content">
        <h1 class="fw-bold">GESTIÓN DE USUARIOS</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-bn">
                <?php echo strtoupper($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="card-custom">
            <h3 class="fw-bold">LISTADO DE PERSONAL</h3>
            <table class="table table-hover mt-3">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>FOTO</th>
                        <th>NOMBRE</th>
                        <th>CORREO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $resUsuarios->fetch_assoc()): ?>
                    <tr>
                        <td class="align-middle"><?php echo $user['id']; ?></td>
                        <td class="align-middle">
                            <img src="<?php echo !empty($user['img']) ? $user['img'] : 'https://via.placeholder.com/40/000000/FFFFFF?text=U'; ?>" 
                                 class="user-img" alt="User">
                        </td>
                        <td class="align-middle"><?php echo htmlspecialchars($user['nombre']); ?></td>
                        <td class="align-middle"><?php echo htmlspecialchars($user['correo'] ?? '---'); ?></td>
                        <td class="align-middle">
                            <button class="btn btn-sm btn-outline-black" onclick="abrirEditar(<?php echo $user['id']; ?>, '<?php echo addslashes($user['nombre']); ?>', '<?php echo addslashes($user['correo']); ?>')">EDITAR</button>
                            
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Confirmar eliminación?')">
                                <input type="hidden" name="accion" value="eliminar_usuario">
                                <input type="hidden" name="id_usuario" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-black">ELIMINAR</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card-custom">
            <h4 class="fw-bold">NUEVO REGISTRO</h4>
            <p>Utilice el formulario oficial para dar de alta nuevos empleados.</p>
            <a href="main.php" class="btn btn-black">IR AL FORMULARIO</a>
        </div>
    </div>

    <div id="modalEditar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2000;">
        <div style="background:white; width:400px; margin:100px auto; padding:20px; border: 3px solid #000; box-shadow: 10px 10px 0px #000;">
            <h4 class="fw-bold">EDITAR USUARIO</h4>
            <hr style="border-top: 2px solid #000;">
            <form method="POST">
                <input type="hidden" name="accion" value="editar_usuario_admin">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label fw-bold">NOMBRE COMPLETO</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" style="border: 1px solid #000; border-radius:0;" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">CORREO ELECTRÓNICO</label>
                    <input type="email" name="correo" id="edit_correo" class="form-control" style="border: 1px solid #000; border-radius:0;" required>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-black" onclick="cerrarEditar()">CANCELAR</button>
                    <button type="submit" class="btn btn-black">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirEditar(id, nombre, correo) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_correo').value = correo;
            document.getElementById('modalEditar').style.display = 'block';
        }
        function cerrarEditar() {
            document.getElementById('modalEditar').style.display = 'none';
        }
        window.onclick = function(event) {
            var modal = document.getElementById('modalEditar');
            if (event.target == modal) { cerrarEditar(); }
        }
    </script>
</body>
</html>
<?php $conexion->close(); ?>
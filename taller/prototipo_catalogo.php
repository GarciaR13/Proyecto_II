<?php
/* ============================
   Configuración / Conexión
   ============================ */
mysqli_report(MYSQLI_REPORT_OFF);
error_reporting(E_ALL);
ini_set('display_errors', 1);
//conexión a la base de datos, nombre de la base, usuario, contraseña, 
$conexion = new mysqli("btwbnpkxsuqozko5ov9i-mysql.services.clever-cloud.com",
 "ujghn3bst4qvf0gr", "rU8nt5pM9ACe78NoWgvP", "btwbnpkxsuqozko5ov9i");
if ($conexion->connect_error) {
  die("<p style='color:red;'>Error de conexión: " . $conexion->connect_error . "</p>");
}

$mensaje = '';
$colorMsg = 'black';

//Estas son las sentencias AJAX
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $accion = $_POST["accion"] ?? '';

  /* ---------- INSERTAR PRODUCTO ---------- */
  if ($accion === "insertar_producto") {
    $nombre       = trim($_POST["nombre"] ?? '');
    $descripcion  = trim($_POST["descripcion"] ?? '');

    // No uses floatval antes del filtro; mejor validar directo
    $precio_raw = $_POST["precio"] ?? '';
    $precio     = filter_var($precio_raw, FILTER_VALIDATE_FLOAT);

    // rutas de archivo
    $nombre_imagen = $_FILES["img"]["name"] ?? '';
    $ruta_temp     = $_FILES["img"]["tmp_name"] ?? '';
    $ruta_destino  = "images/" . basename($nombre_imagen);

    if ($nombre === '' || $precio === false || $precio < 0) {
      $mensaje  = "Datos inválidos: revisa nombre y precio.";
      $colorMsg = "red";
    } else {
      // verificar que haya archivo y error OK
      if (!isset($_FILES['img']) || !isset($_FILES['img']['error']) || $_FILES['img']['error'] !== UPLOAD_ERR_OK) {
        $mensaje  = "Error al subir la imagen (no recibida o con error).";
        $colorMsg = "red";
      } else {
        // asegurar carpeta
        $dir_destino = __DIR__ . "/images";
        if (!is_dir($dir_destino)) {
          @mkdir($dir_destino, 0775, true);
        }
        // mover archivo
        $ruta_fs = $dir_destino . "/" . basename($nombre_imagen);
        if (move_uploaded_file($ruta_temp, $ruta_fs)) {
          // INSERT preparado
          $sql = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, imagen) VALUES (?, ?, ?, ?)");
          if (!$sql) {
            $mensaje  = "Error al preparar INSERT: " . $conexion->error;
            $colorMsg = "red";
          } else {
            //este formato tiene que estar correcto o no sirve del todo s= string, d=decimal
            $sql->bind_param("ssds", $nombre, $descripcion, $precio, $ruta_destino);
            if ($sql->execute()) {
              $mensaje  = "Producto insertado (ID: {$sql->insert_id}).";
              $colorMsg = "green";
            } else {
              $mensaje  = "Error al insertar_producto: " . $sql->error;
              $colorMsg = "red";
            }
            $sql->close();
          }
        } else {
          $mensaje  = "Error al mover la imagen al directorio destino.";
          $colorMsg = "red";
        }
      }
    }

  /* ---------- EDITAR PRODUCTO (renombrado) ---------- */
  } elseif ($accion === "editar_producto") {
  $id         = (int)($_POST["id_producto"] ?? 0);
  $nombre     = trim($_POST["nombre"] ?? '');
  $precio_raw = $_POST["precio"] ?? '';
  $precio     = filter_var($precio_raw, FILTER_VALIDATE_FLOAT);

  if ($id <= 0 || $nombre === '' || $precio === false || $precio < 0) {
    $mensaje  = "Datos inválidos: revisa ID, nombre y precio.";
    $colorMsg = "red";
  } else {

    // 1) Consultar la imagen actual (para posible borrado si se sube una nueva)
    $imagenActual = '';
    $res = $conexion->prepare("SELECT imagen FROM productos WHERE id_producto = ?");
    if ($res) {
      $res->bind_param("i", $id);
      $res->execute();
      $res->bind_result($imagenActual);
      $res->fetch();
      $res->close();
    }

    $hayArchivoNuevo = (isset($_FILES['img_edit']) && isset($_FILES['img_edit']['error']) && $_FILES['img_edit']['error'] === UPLOAD_ERR_OK);

    if ($hayArchivoNuevo) {
      // 2) Subir la nueva imagen
      $dir_destino = __DIR__ . "/images";
      if (!is_dir($dir_destino)) {
        @mkdir($dir_destino, 0775, true);
      }

      $nombre_imagen = basename($_FILES["img_edit"]["name"]);
      $nombre_imagen = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre_imagen); // sanitiza

      $ruta_destino_fs  = $dir_destino . "/" . $nombre_imagen;
      $ruta_destino_web = "images/" . $nombre_imagen;

      if (!move_uploaded_file($_FILES["img_edit"]["tmp_name"], $ruta_destino_fs)) {
        $mensaje  = "No se pudo mover la nueva imagen al directorio destino.";
        $colorMsg = "red";
      } else {
        // 3) Actualizar nombre, precio e imagen
        $stmt = $conexion->prepare(
          "UPDATE productos
             SET nombre = ?, precio = ?, imagen = ?
           WHERE id_producto = ?"
        );
        if (!$stmt) {
          $mensaje  = "Error al preparar UPDATE: " . $conexion->error;
          $colorMsg = "red";
        } else {
          $stmt->bind_param("sdsi", $nombre, $precio, $ruta_destino_web, $id);
          if ($stmt->execute()) {
            $mensaje  = "Producto actualizado (con nueva imagen).";
            $colorMsg = "blue";

            // 4) (Opcional) Borrar la imagen anterior del disco si existe y es distinta
            if (!empty($imagenActual) && $imagenActual !== $ruta_destino_web) {
              $fsAnterior = __DIR__ . '/' . ltrim($imagenActual, '/');
              if (is_file($fsAnterior)) { @unlink($fsAnterior); }
            }
          } else {
            $mensaje  = "Error al actualizar: " . $stmt->error;
            $colorMsg = "red";
          }
          $stmt->close();
        }
      }
    } else {
      // Sin archivo nuevo: solo nombre y precio
      $stmt = $conexion->prepare(
        "UPDATE productos
           SET nombre = ?, precio = ?
         WHERE id_producto = ?"
      );
      if (!$stmt) {
        $mensaje  = "Error al preparar UPDATE: " . $conexion->error;
        $colorMsg = "red";
      } else {
        $stmt->bind_param("sdi", $nombre, $precio, $id);
        if ($stmt->execute()) {
          $mensaje  = ($stmt->affected_rows > 0)
            ? "Producto actualizado."
            : "Sin cambios (ID inexistente o datos iguales).";
          $colorMsg = ($stmt->affected_rows > 0) ? "blue" : "orange";
        } else {
          $mensaje  = "Error al actualizar: " . $stmt->error;
          $colorMsg = "red";
        }
        $stmt->close();
      }
    }
  }

  /* ---------- ELIMINAR PRODUCTO (renombrado) ---------- */
  } elseif ($accion === "eliminar_produto") { // ojo: "produto" tal como pediste
    $id = (int)($_POST["id_producto_eliminar"] ?? 0);
    if ($id <= 0) {
      $mensaje  = "ID inválido para eliminar.";
      $colorMsg = "red";
    } else {
      $stmt = $conexion->prepare("DELETE FROM productos WHERE id_producto = ?");
      if (!$stmt) {
        $mensaje  = "Error al preparar DELETE: " . $conexion->error;
        $colorMsg = "red";
      } else {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
          $mensaje  = ($stmt->affected_rows > 0)
            ? "Producto eliminado."
            : "No se encontró el ID indicado.";
          $colorMsg = ($stmt->affected_rows > 0) ? "orange" : "red";
        } else {
          $mensaje  = "Error al eliminar: " . $stmt->error;
          $colorMsg = "red";
        }
        $stmt->close();
      }
    }
  } elseif ($accion === "agregar_usuario"){
    $nombre_usuario = $_POST["nombre"];
    $contra = password_hash($_POST["contraseña"], PASSWORD_DEFAULT);
    $nombre_imagen = $_FILES["img"]["name"];
    $ruta_temp = $_FILES["img"]["tmp_name"];
    $ruta_destino = "images/" . basename($nombre_imagen);
    $corr = $_POST["correo"];

    if (move_uploaded_file($ruta_temp, $ruta_destino)) {
      $sql = "INSERT INTO usuarios (nombre, contra, img, correo) VALUES ('$nombre_usuario', '$contra', '$ruta_destino', '$corr')";
      if ($conexion->query($sql) === TRUE) {
      header("Location: prototipo_catalogo.php");  
      exit();
      } else {
        echo "Error al insertar: " . $conexion->error;
      }
    } else {
      echo "Error al subir la imagen.";
    }
  } elseif ($accion==="editar_usuario"){
    $id_usuario = $_POST["id"];
    $nombre_usuario = $_POST["nombre"];
    $contra = password_hash($_POST["contra"], PASSWORD_DEFAULT);
    $nombre_imagen = $_FILES["img_edit"]["name"];
    $ruta_temp = $_FILES["img_edit"]["tmp_name"];
    $ruta_destino = "images/" . basename($nombre_imagen);
    $corr = $_POST["correo"];

  if (!empty($nombre_imagen)) {
    if (move_uploaded_file($ruta_temp, $ruta_destino)) {
      $sql = "UPDATE usuarios 
      SET nombre = '$nombre_usuario', contra = '$contra', img = '$ruta_destino', correo = '$corr' 
      WHERE id = $id_usuario";
                      
      if ($conexion->query($sql) === TRUE) {
         header("Location: prototipo_catalogo.php");
          exit();
      } else {
        echo "Error al actualizar: " . $conexion->error;
      }    
    } else {
      echo "Error al subir la imagen.";
      exit();
    }
  }
} elseif($accion==="eliminar_usuario"){
    $id_usuario = $_POST["id_usuario_eliminar"];
    // 1. Obtener imagen actual
    $sql_img = "SELECT img FROM usuarios WHERE id = $id_usuario";
    $res_img = $conexion->query($sql_img);

    if ($res_img && $res_img->num_rows > 0) {
        $fila = $res_img->fetch_assoc();
        $img_actual = $fila['img'];

        // 2. Borrar archivo si existe
        if (!empty($img_actual) && file_exists($img_actual)) {
            unlink($img_actual);
        }
    }

    // 3. Eliminar usuario
    $sql = "DELETE FROM usuarios WHERE id = $id_usuario";

    if ($conexion->query($sql) === TRUE) {
        header("Location: prototipo_catalogo.php");
        exit();
    } else {
        echo "Error al eliminar usuario: " . $conexion->error;
    }
}
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Taller 1 - Catálogo (CRUD)</title>
  <style>
  #carrito button{
  width:100%;
  margin-top:10px;
  padding:8px;
  background:#ff4444;
  color:white;
  border:none;
  cursor:pointer;
}
#listaCarrito button{
  background:red;
  color:white;
  border:none;
  cursor:pointer;
  padding:2px 6px;
  margin-left:5px;
}
    #carrito{
  position: fixed;
  right: 0;
  top: 0;
  width: 250px;
  height: 100%;
  background: #f4f4f4;
  border-left: 2px solid #ccc;
  padding: 10px;
  overflow-y: auto;
}

#carrito h3{
  text-align: center;
}

#listaCarrito li{
  margin-bottom: 10px;
}
    td.imagen-col { width: 120px; text-align: center; }
    td.imagen-col img { max-width: 80px; height: auto; display: block; margin: 0 auto; }
    table { border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 6px 8px; }
    th { background: #f2f2f2; }
  </style>
</head>
<script>

//elemento arreglo de productosc arrito
let carrito = [];

function agregarCarrito(nombre, precio){
  //buscar si este exite en la lista
  let producto = carrito.find(p => p.nombre === nombre);
  //si se halla, aumentar cantidad
  if(producto){
    producto.cantidad++;
  }else{
    //si no, lo agregara
    carrito.push({
      nombre: nombre,
      precio: parseFloat(precio),
      cantidad: 1
    });
  }
  //actualizar lista
  actualizarCarrito();
}

//actualizar display de la lista carrito
function actualizarCarrito(){
  //lista del carrito
  let lista = document.getElementById("listaCarrito");
  let total = 0;
  //vaciarla
  lista.innerHTML = "";
  //por cada producto en el carrito
  carrito.forEach((producto, index) => {
    //CREAR un item
    let item = document.createElement("li");
    //sumar el total que es precio por la cantidad
    let subtotal = producto.precio * producto.cantidad;
    //suma al total actual ese resultado
    total += subtotal;
    //codigo del item sera modificado
    item.innerHTML =
      producto.nombre +
      " - $" + producto.precio +
      " | Cantidad: " +
      "<input type='number' min='1' value='"+producto.cantidad+"' onchange='cambiarCantidad("+index+", this.value)'>" +
      " ❌ <button onclick='eliminarProducto("+index+")'>X</button>" +
      " = $" + subtotal.toFixed(2);
    //agregarlo a la lista
    lista.appendChild(item);
  });
  //actualizar el display del total
  document.getElementById("totalCarrito").innerHTML =
    "<b>Total: $" + total.toFixed(2) + "</b>";
}
//eliminar emplea splice apra eliminar del arreglo
function eliminarProducto(index){
  //eliminar en el index solo un elemento
  carrito.splice(index, 1);
  //actualizar display
  actualizarCarrito();
}

//cambiar cantidad del producto
function cambiarCantidad(index, nuevaCantidad){
  //nueva cantidad, pasarlo a INT
  nuevaCantidad = parseInt(nuevaCantidad);
  //si es menor a uno pone por default 1
  if(nuevaCantidad < 1){
    nuevaCantidad = 1;
  }
  //acutlizar en el index la canitdad
  carrito[index].cantidad = nuevaCantidad;
  //actualizar el carrito mostrado
  actualizarCarrito();
}

//vaciado total
function vaciarCarrito(){
  //confirmacion del vaciado
  if(confirm("¿Deseas vaciar todo el carrito?")){
    //simplemente vaciamos la lista redefiniendola
    carrito = [];
    //actualizar vista
    actualizarCarrito();
  }
}


</script>
<body>

<div id="carrito">
  <h3>🛒 Carrito</h3>

  <ul id="listaCarrito"></ul>

  <p id="totalCarrito"><b>Total: $0</b></p>

  <button onclick="vaciarCarrito()">Vaciar carrito</button>
</div>

<?php if ($mensaje): ?>
  <p style="color:<?= htmlspecialchars($colorMsg) ?>;">
    <?= htmlspecialchars($mensaje) ?>
  </p>
<?php endif; ?>

<?php
// ===== Listado de usuarios =====
$sql = "SELECT id, nombre, img, correo FROM usuarios ORDER BY id DESC";
$resultado = $conexion->query($sql);
echo "<h2>Listado de usuarios</h2>";
if ($resultado === false) {
  echo "<p style='color:red;'>Error en la consulta: " . $conexion->error . "</p>";
} else {
  if ($resultado->num_rows > 0) {
    echo "
      <table cellpadding='10'>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Img</th>
          <th>Correo</th>
        </tr>
    ";
    while ($fila = $resultado->fetch_assoc()) {
      $id   = htmlspecialchars($fila['id']);
      $nom  = htmlspecialchars($fila['nombre']);
      $imgU = htmlspecialchars($fila['img']);
      $corr = htmlspecialchars($fila['correo']);

      echo "
        <tr>
          <td>{$id}</td>
          <td>{$nom}</td>
          <td>{$imgU}</td>
          <td>{$corr}</td>
        </tr>
      ";
    }
    echo "</table>";
  } else {
    echo "<p>No hay Usuarios</p>";
  }
}

// ===== Listado de productos =====
$sql = "SELECT id_producto, nombre, descripcion, precio, imagen FROM productos ORDER BY id_producto DESC";
$resultado = $conexion->query($sql);

echo "<h2>Lista de productos</h2>";

if ($resultado === false) {
  echo "<p style='color:red;'>Error en la consulta: " . $conexion->error . "</p>";
} else {
  if ($resultado->num_rows > 0) {
    echo "
      <table cellpadding='10'>
        <tr>
          <th>ID_Producto</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Precio</th>
          <th>Imagen</th>
        </tr>
    ";
    while ($fila = $resultado->fetch_assoc()) {
      $id   = htmlspecialchars($fila['id_producto']);
      $nom  = htmlspecialchars($fila['nombre']);
      $desc = htmlspecialchars($fila['descripcion']);
      $prec = number_format((float)$fila['precio'], 2);
      $img = htmlspecialchars($fila['imagen']);
      echo "
        <tr onclick=\"agregarCarrito('$nom','$prec')\" style='cursor:pointer'>
          <td>{$id}</td>
          <td>{$nom}</td>
          <td>{$desc}</td>
          <td>\${$prec}</td>
          <td><img src='{$fila['imagen']}' style='width: 15%; height: 15%; margin-left: 30%;'></td>
          </td>
        </tr>
      ";
    } echo "</table>";
  } else {
    echo "<p>No hay productos</p>";
  }
}
?>

<hr>

<h2>➕ Insertar producto</h2>
<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="accion" value="insertar_producto">
  <label>Nombre:</label><br>
  <input type="text" name="nombre" required><br>

  <label>Descripción</label><br>
  <input type="text" name="descripcion"><br>

  <label>Precio:</label><br>
  <input type="number" name="precio" min="0" step="0.5" required><br>

  <label>Imagen</label><br>
  <input type="file" name="img" accept="image/*" required><br><br>

  <button type="submit">insertar producto</button>
</form>

  <h2>➕ Agregar Usuario</h2>
<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="accion" value="agregar_usuario">
  <label>Nombre:</label><br>
  <input type="text" name="nombre" required><br>
  
  <label>Correo</label><br>
  <input type="text" name="correo"><br>

  <label>Contraseña:</label><br>
  <input type="text" name="contra"><br>

  <label>Imagen</label><br>
  <input type="file" name="img" accept="image/*" required><br><br>

  <button type="submit">Agregar Usuario</button>
</form>
<br><br>

<h2>✏️ Editar producto</h2>
<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="accion" value="editar_producto">

  <label>ID del producto a editar:</label><br>
  <input type="number" name="id_producto" min="1" required><br>

  <label>Nuevo nombre:</label><br>
  <input type="text" name="nombre" required><br>

  <label>Nuevo precio:</label><br>
  <input type="number" name="precio" min="0" step="0.01" required><br>

  <label>Nueva imagen (opcional):</label><br>
  <input type="file" name="img_edit" accept="image/*"><br><br>

  <button type="submit">Actualizar</button>
</form>

<br><br>

<h2>✏️ Editar Usuario</h2>
<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="accion" value="editar_usuario">

  <label>ID del usuario a editar:</label><br>
  <input type="number" name="id" min="1" required><br>

  <label>Nuevo nombre:</label><br>
  <input type="text" name="nombre" required><br>

  <label>Nueva contraseña:</label><br>
  <input type="text" name="contraseña" required><br>

  <label>Nuevo Correo:</label><br>
  <input type="text" name="correo" required><br>

  <label>Nueva imagen:</label><br>
  <input type="file" name="img_edit" accept="image/*"><br><br>

  <button type="submit">Actualizar</button>
</form>

<br><br>

<h2>🗑️ Eliminar producto</h2>
<form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
  <!-- renombrado: eliminar_produto (con una sola c, como pediste) -->
  <input type="hidden" name="accion" value="eliminar_produto">

  <label>ID del producto a eliminar:</label><br>
  <input type="number" name="id_producto_eliminar" min="1" required><br><br>

  <button type="submit">Eliminar</button>
</form>

<br><br>
<h2>🗑️ Eliminar Usuario</h2>
<form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
  <input type="hidden" name="accion" value="eliminar_usuario">

  <label>ID del usuario a eliminar:</label><br>
  <input type="number" name="id_usuario_eliminar" min="1" required><br><br>

  <button type="submit">Eliminar Usuario</button>
</form>

</body>
</html>

<?php
$conexion->close();

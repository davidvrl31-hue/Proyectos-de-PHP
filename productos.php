<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos</title>
    <!-- Enlace al archivo CSS para estilos -->
    <link rel="stylesheet" href="estilo_productos.css">
</head>
<body>
    
    <h2>Gestión de Productos</h2>

    <?php
session_start();

/**
 * ============================
 * FUNCIÓN DE CONEXIÓN A LA BD
 * ============================
 * Esta función se encarga de conectar PHP con la base de datos MySQL.
 * Devuelve el objeto de conexión ($conn) para usar en las consultas.
 */
function conectarBD() {
    $host = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "usuario_php"; // Nombre de la base de datos

    $conn = new mysqli($host, $dbuser, $dbpass, $dbname);

    // Verifica si hubo error en la conexión
    if ($conn->connect_error) {
        die("<p style='color:red;'>Error al conectar: " . $conn->connect_error . "</p>");
    }
    return $conn;
}

$conn = conectarBD(); // Se conecta a la base de datos



/**
 * ========================
 * REGISTRAR UN PRODUCTO
 * ========================
 * Si el usuario envía el formulario con el botón "Registrar",
 * se toma la información del producto (incluida la imagen),
 * y se inserta en la tabla "productos".
 */
if (isset($_POST['registrar'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];

    // Manejo de la imagen y almacenamiento en carpeta "uploads"
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $imagen = $target_dir . basename($_FILES["imagen"]["name"]);
    move_uploaded_file($_FILES["imagen"]["tmp_name"], $imagen);

    // Inserta en la base de datos
    $sql = "INSERT INTO productos (imagen, nombre, descripcion, precio, cantidad) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdi", $imagen, $nombre, $descripcion, $precio, $cantidad);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>  Producto registrado correctamente</p>";
    } else {
        echo "<p style='color:red;'>  Error: " . $conn->error . "</p>";
    }
    $stmt->close();
}



/**
 * ========================
 * ELIMINAR PRODUCTO
 * ========================
 * Si el usuario presiona "Eliminar" en la tabla,
 * el producto se borra de la base de datos usando su ID.
 */
if (isset($_POST['eliminar_id'])) {
    $id = intval($_POST['eliminar_id']);
    $sql = "DELETE FROM productos WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<p style='color:red;'>🗑 Producto eliminado correctamente</p>";
    } else {
        echo "<p style='color:red;'> Error: " . $conn->error . "</p>";
    }
    $stmt->close();
}



/**
 * ========================
 * CARGAR PRODUCTO A EDITAR
 * ========================
 * Si el usuario presiona "Editar", se busca el producto por ID
 * y se cargan sus datos en el formulario para modificarlos.
 */
$editar = null;
if (isset($_POST['editar_id'])) {
    $id = intval($_POST['editar_id']);
    $result = $conn->query("SELECT * FROM productos WHERE id=$id");
    $editar = $result->fetch_assoc();
}



/**
 * ========================
 * ACTUALIZAR PRODUCTO
 * ========================
 * Si se envía el formulario con el botón "Actualizar",
 * se guardan los cambios (nombre, descripción, precio, cantidad).
 * Si el usuario sube una nueva imagen, también se reemplaza.
 */
if (isset($_POST['actualizar'])) {
    $id = intval($_POST['editar_id']);
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];

    // Validar si hay nueva imagen subida
    if (!empty($_FILES["imagen"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $imagen = $target_dir . basename($_FILES["imagen"]["name"]);
        move_uploaded_file($_FILES["imagen"]["tmp_name"], $imagen);

        $sql = "UPDATE productos SET imagen=?, nombre=?, descripcion=?, precio=?, cantidad=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdii", $imagen, $nombre, $descripcion, $precio, $cantidad, $id);
    } else {
        $sql = "UPDATE productos SET nombre=?, descripcion=?, precio=?, cantidad=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $cantidad, $id);
    }

    if ($stmt->execute()) {
        echo "<p style='color:blue;'>✏ Producto actualizado correctamente</p>";
    } else {
        echo "<p style='color:red;'>  Error: " . $conn->error . "</p>";
    }
    $stmt->close();
}



/**
 * ========================
 * CONSULTAR LISTA DE PRODUCTOS
 * ========================
 * Finalmente se hace una consulta SELECT * FROM productos,
 * para mostrar todos los productos registrados en la tabla HTML.
 */
$result = $conn->query("SELECT * FROM productos");
?>



    <!-- ================= FORMULARIO DE REGISTRO / EDICIÓN ================= -->
    <div class="form-container">
        <form action="productos.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="editar_id" value="<?= $editar['id'] ?? '' ?>">

            <div class="form-group">
                <label>Imagen:</label>
                <input type="file" name="imagen" <?= $editar ? '' : 'required' ?>>
                <?php if ($editar): ?>
                    <p>Imagen actual: <img src="<?= $editar['imagen'] ?>" width="50"></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?= $editar['nombre'] ?? '' ?>" required>
            </div>

            <div class="form-group">
                <label>Descripción:</label>
                <textarea name="descripcion" required><?= $editar['descripcion'] ?? '' ?></textarea>
            </div>

            <div class="form-group">
                <label>Precio:</label>
                <input type="number" name="precio" step="0.01" value="<?= $editar['precio'] ?? '' ?>" required>
            </div>

            <div class="form-group">
                <label>Cantidad:</label>
                <input type="number" name="cantidad" value="<?= $editar['cantidad'] ?? '' ?>" required>
            </div>

            <div class="form-group">
                <?php if ($editar): ?>
                    <input type="submit" name="actualizar" value="Actualizar">
                <?php else: ?>
                    <input type="submit" name="registrar" value="Registrar">
                <?php endif; ?>
            </div>
        </form>
    </div>



    <!-- ================= TABLA DE PRODUCTOS ================= -->
    <h3>Lista de productos</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Creado</th>
                <th>Actualizado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><img src="<?= $row['imagen'] ?>" alt="producto"></td>
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['descripcion']) ?></td>
                    <td><?= $row['precio'] ?></td>
                    <td><?= $row['cantidad'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                    <td><?= $row['updated_at'] ?></td>
                    <td>
                        <!-- Botón Editar -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="editar_id" value="<?= $row['id'] ?>">
                            <input type="submit" value="Editar">
                        </form>
                        <!-- Botón Eliminar -->
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este producto?');">
                            <input type="hidden" name="eliminar_id" value="<?= $row['id'] ?>">
                            <input type="submit" value="Eliminar">
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9">No hay productos registrados</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

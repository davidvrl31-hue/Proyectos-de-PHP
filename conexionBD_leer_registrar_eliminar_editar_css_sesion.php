<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRUD Usuarios</title>
    <link rel="stylesheet" href="estilo_nueva.css">
</head>
<body>
<?php
/**
 * Inicia la sesión y prepara el sistema
 */
session_start();

/**
 * Establece la conexión con la base de datos MySQL.
 */
function conectarBD() {
    $host = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "usuario_php";
    $conn = new mysqli($host, $dbuser, $dbpass, $dbname);
    if ($conn->connect_error) {
        die("<h6 style='color:red;'>Error al conectar a la base de datos: " . $conn->connect_error . "</h6>");
    } 
    return $conn;
}

/**
 * Registrar auditoría de inicio de sesión
 */
if (isset($_SESSION['username']) && isset($_SESSION['user_id'])) {
    $conn = conectarBD();
    $sesion_id = session_id();
    $usuario = $_SESSION['username'];

    $sql = "SELECT id_log FROM log_sistema WHERE sesion_id=? AND fecha_cierre IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sesion_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        $sql_insert = "INSERT INTO log_sistema (sesion_id, usuario, fecha_inicio) VALUES (?, ?, NOW())";
        $stmt2 = $conn->prepare($sql_insert);
        $stmt2->bind_param("ss", $sesion_id, $usuario);
        $stmt2->execute();
        $stmt2->close();
    }
    $stmt->close();
    $conn->close();
}

/**
 * Cierre de sesión con auditoría
 */
if (isset($_GET['logout'])) {
    $conn = conectarBD();
    $sesion_id = session_id();
    $sql_update = "UPDATE log_sistema SET fecha_cierre=NOW() WHERE sesion_id=? AND fecha_cierre IS NULL";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("s", $sesion_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

/**
 * Realiza una consulta SQL para obtener los usuarios registrados.
 */
function consultarBD() {
    $conn = conectarBD();
    $sql = "SELECT id, username AS username, password, created_at, updated_at FROM login_user";
    $result = $conn->query($sql);
    if (!$result) {
        die("Error en la consulta: " . $conn->error);
    }
    return $result;
}
$result = consultarBD();

/**
 * Cargar datos de usuario para editar
 */
$editar_usuario = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_id'])) {
    $conn = conectarBD();
    $id = intval($_POST['editar_id']);
    $sql = "SELECT * FROM login_user WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editar_usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!-- Formulario de registro de usuarios -->
<div class="form-container">
    <h2 class="form-title"><?php echo $editar_usuario ? "Editar Usuario" : "Formulario de Registro"; ?></h2>
        <form action="conexionBD_leer_registrar_eliminar_editar_css_sesion.php" method="post">
            <div style="text-align: right; margin: 10px 5%;">
                <?php
                echo "User Id: " . $_SESSION['user_id'];
                $nombreSession = session_name();
                $idSession = session_id();
                echo " |  Session Name: " . $nombreSession . "  |   Session Id: " . $idSession . "  |";
                ?>
               Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?> | 
                <a href="?logout=1">Cerrar sesión</a> | 
                <a href="?ver_auditoria=1">Ver auditoría</a>
            </div>
            <?php if ($editar_usuario): ?>
                <input type="hidden" name="id" value="<?php echo $editar_usuario['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="user">Usuario</label>
                <input type="text" name="user" placeholder="Usuario" value="<?php echo $editar_usuario ? htmlspecialchars($editar_usuario['username']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" placeholder="Contraseña" value="">
            </div>
            <div class="form-actions">
                <input type="submit" class="btn btn-primary" name="<?php echo $editar_usuario ? 'actualizar' : 'registrar'; ?>" value="<?php echo $editar_usuario ? 'Actualizar' : 'Registrar'; ?>">
                <?php if ($editar_usuario): ?>
                    <a href="conexionBD_leer_registrar_eliminar_editar_css_sesion.php" class="cancel-btn">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
</div>

<?php
$conn = conectarBD();
// Guardar en base de datos.

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar']) && isset($_POST['user']) && isset($_POST['password'])) {
    $user = $_POST["user"];
    $password = $_POST["password"];

    $sql_check = "SELECT id FROM login_user WHERE username = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $user);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo "<h3 style='color:red;'>El nombre de usuario ya existe. Por favor elige otro.</h3>";
    } else {
        $sql = "INSERT INTO login_user (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $user, $password);
        if ($stmt->execute()) {
            echo "<h3 style='color:green;'>Usuario registrado correctamente.</h3>";
        } else {
            echo "Error al registrar usuario: " . $conn->error;
        }
        $stmt->close();
    }
    $stmt_check->close();
    $result = consultarBD();
}

// Actualizar usuario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar']) && isset($_POST['id'])) {
    $conn = conectarBD();
    $id = intval($_POST['id']);
    $user = $_POST["user"];
    $password = $_POST["password"];

    $sql_check = "SELECT id FROM login_user WHERE username = ? AND id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $user, $id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo "<h3 style='color:red;'>El nombre de usuario ya existe en otro registro. Por favor elige otro.</h3>";
    } else {
        if (!empty($password)) {
            $sql = "UPDATE login_user SET username=?, password=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $user, $password, $id);
        } else {
            $sql = "UPDATE login_user SET username=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $user, $id);
        }
        if ($stmt->execute()) {
            echo "<h3 style='color:green;'>Usuario actualizado correctamente.</h3>";
        } else {
            echo "Error al actualizar usuario: " . $conn->error;
        }
        $stmt->close();
    }
    $stmt_check->close();
    $result = consultarBD();
}

/**
 * Eliminar usuario
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_id'])) {
    $conn = conectarBD();
    $id = intval($_POST['eliminar_id']);
    $sql = "DELETE FROM login_user WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<h3 style='color:red;'>Usuario eliminado correctamente.</h3>";
    } else {
        echo "<h3 style='color:red;'>Error al eliminar usuario: " . $conn->error . "</h3>";
    }
    $stmt->close();
}
$result = consultarBD();
?>

<!-- Tabla de usuarios -->
<table border="1" cellpadding="5" cellspacing="0" style="margin-top:30px; width:100%;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Contraseña</th>
            <th>Creado</th>
            <th>Actualizado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['password']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                    <td>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Eliminar Este usuario?');">
                            <input type="hidden" name="eliminar_id" value="<?php echo $row['id']; ?>">
                            <input type="submit" value="Eliminar">
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="editar_id" value="<?php echo $row['id']; ?>">
                            <input type="submit" value="Editar">
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No hay usuarios registrados</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php
/**
 * Mostrar auditoría si se solicita
 */
if (isset($_GET['ver_auditoria'])) {
    $conn = conectarBD();
    $sql = "SELECT usuario, fecha_inicio, fecha_cierre FROM log_sistema ORDER BY fecha_inicio DESC";
    $resultado = $conn->query($sql);

    echo "<h2 style='margin-top:40px;'>Auditoría de Sesiones</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%; margin-top:20px;'>
            <tr>
                <th>Usuario</th>
                <th>Fecha Inicio</th>
                <th>Fecha Cierre</th>
                <th>Estado</th>
            </tr>";
    while ($fila = $resultado->fetch_assoc()) {
        $estado = is_null($fila['fecha_cierre'])
            ? "<span style='color:green;font-weight:bold;'>Activo</span>"
            : "<span style='color:red;'>" . "La sesión ha sido cerrada" . "</span>";
        echo "<tr>
                <td>{$fila['usuario']}</td>
                <td>{$fila['fecha_inicio']}</td>
                <td>" . ($fila['fecha_cierre'] ?? '-') . "</td>
                <td>$estado</td>
              </tr>";
    }
    echo "</table>";
    $conn->close();
}
?>
</body>
</html>

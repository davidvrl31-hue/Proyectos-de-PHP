<?php
session_start();

/**
 * Conexión a BD
 */
function conectarBD()
{
    $host = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "usuario_php";
    $conn = new mysqli($host, $dbuser, $dbpass, $dbname);

    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
    return $conn;
}

// --- LOGIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Por favor ingrese usuario y contraseña";
    } else {
        $conn = conectarBD();
        $sql = "SELECT id, username, password FROM login_user WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // ⚠️ En producción usa password_verify()
            if ($password === $user['password']) {

                // 🔹 Cerrar sesiones previas abiertas del mismo usuario
                $sql_cerrar = "UPDATE log_sistema 
                               SET fecha_cierre = NOW() 
                               WHERE usuario = ? AND fecha_cierre IS NULL";
                $stmt_cerrar = $conn->prepare($sql_cerrar);
                $stmt_cerrar->bind_param("s", $user['username']);
                $stmt_cerrar->execute();
                $stmt_cerrar->close();

                // 🔹 Forzar nuevo ID de sesión
                session_regenerate_id(true);

                // 🔹 Capturar el nuevo session_id
                $sesion_id = session_id();

                // Variables de sesión
                $_SESSION['sesion_id'] = $sesion_id;
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['loggedin']  = true;

                // 🔹 Registrar nueva sesión en log_sistema
                $sql_log = "INSERT INTO log_sistema (sesion_id, usuario, fecha_inicio) VALUES (?, ?, NOW())";
                $stmt_log = $conn->prepare($sql_log);
                $stmt_log->bind_param("ss", $sesion_id, $user['username']);
                $stmt_log->execute();
                $stmt_log->close();

                // Redirigir al sistema principal
                header("Location: conexionBD_leer_registrar_eliminar_editar_css_sesion.php");
                exit;
            } else {
                $error = "Contraseña incorrecta";
            }
        } else {
            $error = "Usuario no encontrado";
        }

        $stmt->close();
        $conn->close();
    }
}

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    $conn = conectarBD();

    if (isset($_SESSION['sesion_id'])) {
        $sql_update = "UPDATE log_sistema 
                       SET fecha_cierre = NOW() 
                       WHERE sesion_id = ? AND fecha_cierre IS NULL";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("s", $_SESSION['sesion_id']);
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();

    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="estilo_login.css">
</head>
<body>
    <h2>Iniciar Sesión</h2>

    <?php if (isset($error)): ?>
        <div style="color:red;"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="login.php" method="post">
        <label for="username">Usuario:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>

        <input type="submit" name="login" value="Iniciar Sesión">
    </form>
</body>
</html>

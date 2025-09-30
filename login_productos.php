
<?php
session_start();

/**
 * Conexión a la base de datos
 */
function conectarBD() {
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

// Procesar el formulario de login
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

            // Validación simple (en texto plano). Recomendado: password_hash + password_verify
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['loggedin'] = true;

                // Redirige al módulo de productos
                header("Location: productos.php");
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

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login_productos.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Productos</title>
    <link rel="stylesheet" href="estilo_login.css">
</head>
<body>
    <h2>Acceso a Gestión de Productos</h2>

    <?php if (isset($error)): ?>
        <div style="color:red;"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="login_productos.php" method="post">
        <label for="username">Usuario:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>

        <input type="submit" name="login" value="Ingresar">
    </form>
    
</body>
</html>
```

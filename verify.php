<?php
session_start();

// Conectar a la base de datos
try {
    require_once __DIR__ . '/config.php';
    // Conexión a la base de datos usando los valores del archivo config.php
    $port = $_ENV['DB_PORT'] ?? '3307';
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    // Establecer el modo de error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los valores del formulario
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Preparar la consulta para obtener el usuario
    $stmt = $pdo->prepare('SELECT id, username, password, rol FROM Usuarios WHERE username = :username');
    $stmt->execute(['username' => $username]);

    // Verificar si el usuario existe
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si la contraseña es correcta
        if (password_verify($password, $user['password'])) {  // Asegúrate de usar password_hash en la base de datos
            // La autenticación fue exitosa

            // Iniciar una sesión y guardar el ID del usuario
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol'] = $user['rol'];  // Almacenar el rol del usuario en la sesión
            $_SESSION['last_activity'] = time(); // tiempo de sesion 

            // Redirigir a la página correspondiente según el rol del usuario
            if ($_SESSION['rol'] == '1') {
                header('Location: PanelSuperadministrador.php'); // Redirigir a la página del superadmin
            } 
            if ($_SESSION['rol'] == '2') {
                header('Location: PanelAdministrador.php'); // Redirigir a la página del admin
            } 
            if ($_SESSION['rol'] == '3') {
                header('Location: PanelGestionHumana.php'); // Redirigir a la página de gestion humana
            }
            if ($_SESSION['rol'] == '4') {
                header('Location: PanelVisitante.php'); // Redirigir a la página del usuario visitante
            }
            //if ($_SESSION['rol'] == '5') {
            //    header('Location: PanelAuditoria.php'); // Redirigir a la página de auditoria
            //}
            exit;
        } else {
            // Contraseña incorrecta
            $_SESSION['error'] = 'Contraseña incorrecta.';
            header('Location: login.php');
            exit;
        }
    } else {
        // Nombre de usuario no encontrado
        $_SESSION['error'] = 'El nombre de usuario no existe.';
        header('Location: login.php');
        exit;
    }
    
}

?>

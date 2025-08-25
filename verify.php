<?php
session_start();

// Conectar a la base de datos
try {
    include('47829374983274.php');
    // Conexión a la base de datos usando los valores del archivo config.php
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
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
                header('Location: 22131325843513551.php'); // Redirigir a la página del superadmin
            } 
            if ($_SESSION['rol'] == '2') {
                header('Location: 0192838712364783.php'); // Redirigir a la página del admin
            } 
            if ($_SESSION['rol'] == '3') {
                header('Location: 78978978978454561.php'); // Redirigir a la página de gestion humana
            }
            if ($_SESSION['rol'] == '4') {
                header('Location: 65410358135135413.php'); // Redirigir a la página del usuario visitante
            }
            //if ($_SESSION['rol'] == '5') {
            //    header('Location: 132155434523463278.php'); // Redirigir a la página de auditoria
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

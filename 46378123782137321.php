<?php
session_start();

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 180)) {
    session_unset();     // Borra todas las variables de sesión
    session_destroy();   // Destruye la sesión
    header("Location: login.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time(); // Actualiza el tiempo de última actividad

include('47829374983274.php');
function registrarAuditoria($pdo, $accion, $usuarioAfectadoId, $datosAntes, $datosDespues) {
    $realizadoPor = $_SESSION['username'] ?? 'desconocido';

    $stmt = $pdo->prepare("
        INSERT INTO auditoria_usuarios (
            accion, usuario_afectado_id, username_afectado, realizado_por, datos_anteriores, datos_nuevos
        ) VALUES (
            :accion, :usuario_afectado_id, :username_afectado, :realizado_por, :datos_anteriores, :datos_nuevos
        )
    ");

    $stmt->execute([
        ':accion' => $accion,
        ':usuario_afectado_id' => $usuarioAfectadoId,
        ':username_afectado' => $datosDespues['username'] ?? '',
        ':realizado_por' => $realizadoPor,
        ':datos_anteriores' => json_encode($datosAntes),
        ':datos_nuevos' => json_encode($datosDespues),
    ]);
}

// Conectar a la base de datos
try {
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME , DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Comprobar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $nombre_completo = $_POST["nombre_completo"];
    $documento = $_POST["documento"];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $rol = $_POST['rol'];

    // Hashear la contraseña
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Preparar la consulta para insertar el nuevo usuario
    try {
        $stmt = $pdo->prepare("INSERT INTO Usuarios (nombre_completo, documento, username, password, rol) 
        VALUES (:nombre_completo, :documento, :username, :password, :rol)");
$stmt->execute([
'nombre_completo' => $nombre_completo,
'documento' => $documento,
'username' => $username,
'password' => $hashedPassword,
'rol' => $rol,
]);

$usuarioId = $pdo->lastInsertId(); // ID del usuario creado

// Guardar en la tabla de auditoría
$datosNuevos = [
'nombre_completo' => $nombre_completo,
'documento' => $documento,
'username' => $username,
'rol' => $rol
];

registrarAuditoria($pdo, 'crear', $usuarioId, null, $datosNuevos);
echo "Usuario registrado correctamente. <a href='login.php'>Iniciar sesión</a>";
    } catch (PDOException $e) {
        echo "Error al registrar el usuario: " . $e->getMessage();
    }
header("Location: " . $_SERVER['PHP_SELF']);
exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link rel="icon" type="image/png" href="img/icon.jpg">
    <style>
        body {
            background-color: #f0f8ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            color: #000080;
        }

        .form-container {
            background-color: #ffffff;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            border-bottom: 2px solid #000080;
            padding-bottom: 10px;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 88%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #000080;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        button {
            background-color: #000080;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #003366;
        }

        a {
            color: #000080;
            text-decoration: none;
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }

        small {
            display: block;
            margin-top: -5px;
            margin-bottom: 8px;
            font-size: 12px;
        }

        ul {
            font-size: 12px;
            padding-left: 20px;
            text-align: left;
            margin: 8px 0;
        }

        ul li {
            margin-bottom: 3px;
        }

        hr {
            margin: 20px 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Registro de Usuario</h2>
    <form id="registroForm" action="46378123782137321.php" method="post" onsubmit="return validarFormulario()">
        <input type="text" name="nombre_completo" placeholder="Nombre Completo" required>

        <input type="text" name="documento" id="documento" placeholder="Número de Documento" required oninput="validarDocumento()">
        <small id="mensajeDocumento" style="color: red; display: none;">Solo se permiten números</small>

        <input type="text" name="username" placeholder="Usuario" required>

        <input type="password" name="password" id="password" placeholder="Contraseña" required 
                oninput="validarPassword()" 
                onfocus="mostrarRequisitos()" 
                onblur="ocultarRequisitosSiVacio()">
        <ul id="requisitos" style="display: none;">
            <li id="longitud" style="color: red;">♦️ Mínimo 8 caracteres</li>
            <li id="mayuscula" style="color: red;">♦️ 1 mayúscula</li>
            <li id="minuscula" style="color: red;">♦️ 1 minúscula</li>
            <li id="numero" style="color: red;">♦️ 1 número</li>
            <li id="especial" style="color: red;">♦️ 1 carácter especial (@$!%*?&)</li>
        </ul>

        <select name="rol">
            <option value="3">Gestión Humana</option>
            <option value="4">Visitante</option>
        </select>

        <button type="submit">Registrar</button>
    </form>

    <a href="logout.php">Cerrar sesión</a>
    <hr>
    <a href="10294732894019238.php">⬅ Volver</a>
</div>

<script>
function mostrarRequisitos() {
    document.getElementById("requisitos").style.display = "block";
}

function ocultarRequisitosSiVacio() {
    const password = document.getElementById("password").value;
    if (password.trim() === "") {
        document.getElementById("requisitos").style.display = "none";
    }
}

function validarPassword() {
    const password = document.getElementById("password").value;

    const requisitos = {
        longitud: [password.length >= 8, "Mínimo 8 caracteres"],
        mayuscula: [/[A-Z]/.test(password), "1 mayúscula"],
        minuscula: [/[a-z]/.test(password), "1 minúscula"],
        numero: [/\d/.test(password), "1 número"],
        especial: [/[!@#$%^&*(),.?":{}|<>]/.test(password), "1 carácter especial (@$!%*?&)"]
    };

    let esValida = true;

    for (const clave in requisitos) {
        const [cumple, texto] = requisitos[clave];
        const item = document.getElementById(clave);
        item.style.color = cumple ? "green" : "red";
        item.textContent = `${cumple ? "✅" : "♦️"} ${texto}`;
        if (!cumple) esValida = false;
    }

    document.getElementById("password").style.borderColor = esValida ? "green" : "red";

    return esValida;
}

function validarDocumento() {
    const input = document.getElementById("documento");
    const mensaje = document.getElementById("mensajeDocumento");
    const soloNumeros = /^[0-9]+$/;

    if (input.value === "" || soloNumeros.test(input.value)) {
        mensaje.style.display = "none";
        input.style.borderColor = "";
        return true;
    } else {
        mensaje.style.display = "block";
        input.style.borderColor = "red";
        return false;
    }
}

function validarFormulario() {
    return validarDocumento() && validarPassword();
}
</script>

</body>
</html>


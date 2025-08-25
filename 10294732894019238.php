<?php
session_start();

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 180)) {
    session_unset();     // Borra todas las variables de sesión
    session_destroy();   // Destruye la sesión
    header("Location: login.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time(); // Actualiza el tiempo de última actividad

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

include('47829374983274.php');
// Verificar si el usuario tiene el rol adecuado
if ($_SESSION['rol'] != '1' && $_SESSION['rol'] != '2') {
    // Si el usuario no tiene el rol 1 (Admin) ni el rol 2, redirigir
    header('Location: login.php');
    exit;
}
// Conectar a la base de datos
try {
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME , DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
// Mostrar usuarios (excepto los administradores)
$sql = "SELECT id, nombre_completo, documento, username, rol FROM Usuarios WHERE rol NOT IN ('1', '2')";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Actualizar usuario
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $rol = $_POST['rol'];

    // Obtener datos antes del cambio
    $stmtOld = $pdo->prepare("SELECT * FROM Usuarios WHERE id = :id");
    $stmtOld->execute([':id' => $id]);
    $datosAntes = $stmtOld->fetch(PDO::FETCH_ASSOC);

    // Actualizar usuario
    $updateSql = "UPDATE Usuarios SET username = :username, password = :password, rol = :rol WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':username' => $username,
        ':password' => $hashedPassword,
        ':rol' => $rol,
        ':id' => $id
    ]);

    // Registrar auditoría (sin mostrar la contraseña)
    $datosDespues = [
        'username' => $username,
        'rol' => $rol
    ];
    // Después de actualizar
registrarAuditoria($pdo, 'actualizar', $id, $datosAntes, $datosDespues);

// ✅ Redirección para evitar reenvío del formulario
header("Location: " . $_SERVER['PHP_SELF']);
exit;

}

// Eliminar usuario
if (isset($_POST['delete'])) {
    $id = $_POST['id'];

    // Obtener datos antes de eliminar
    $stmtOld = $pdo->prepare("SELECT * FROM Usuarios WHERE id = :id");
    $stmtOld->execute([':id' => $id]);
    $datosAntes = $stmtOld->fetch(PDO::FETCH_ASSOC);

    // Eliminar usuario
    $deleteSql = "DELETE FROM Usuarios WHERE id = :id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([':id' => $id]);

    // Registrar auditoría
registrarAuditoria($pdo, 'eliminar', $id, $datosAntes, null);

// ✅ Redirección para evitar reenvío del formulario
header("Location: " . $_SERVER['PHP_SELF']);
exit;


}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="icon" type="image/png" href="img/icon.jpg">
    <style>
        body {
            background-color: #f0f8ff;
            color: #000080;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 26px;
            color: #000080;
            border-bottom: 2px solid #000080;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background-color: #f9f9ff;
        }

        th, td {
            border: 1px solid #000080;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #e0eaff;
        }

        input[type="text"], input[type="password"], select {
            width: 90%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 10px;
            border: 1px solid #000080;
            border-radius: 8px;
            font-size: 14px;
        }

        select {
            background-color: #ffffff;
        }

        button {
            background-color: #000080;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #003366;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-buttons form {
            flex: 1 1 48%;
        }

        .alert {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .links {
            text-align: center;
            margin-top: 30px;
        }

        .links a {
            color: #000080;
            text-decoration: none;
            font-weight: bold;
            margin: 0 10px;
        }

        .refresh-button {
            background: none;
            border: none;
            cursor: pointer;
            margin: 15px auto;
            display: block;
            width: 40px;
            height: 40px;
        }

        .refresh-button img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Gestión de Usuarios</h2>

    <?php
    if (isset($_POST['update']) && $updateStmt) {
        echo "<div class='alert alert-success'>Usuario actualizado correctamente.</div>";
    }
    if (isset($_POST['delete']) && $deleteStmt) {
        echo "<div class='alert alert-danger'>Usuario eliminado correctamente.</div>";
    }
    ?>

    <table>
        <thead>
            <tr>
                <th>Nombre de Usuario</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $roles = [
                3 => 'Gestión Humana',
                4 => 'Invitado',
            ];

            foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['nombre_completo']) ?></td>
                <td><?= $roles[$user['rol']] ?? 'Desconocido' ?></td>
                <td class="action-buttons">
                    <form action="" method="POST" onsubmit="return verificarFormulario(<?= $user['id'] ?>)">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                        <input type="password" name="password" id="password_<?= $user['id'] ?>" placeholder="Nueva Contraseña" required
                                oninput="validarPassword(<?= $user['id'] ?>)" 
                                onfocus="mostrarRequisitos(<?= $user['id'] ?>)" 
                                onblur="ocultarRequisitosSiVacio(<?= $user['id'] ?>)">
                        <ul id="requisitos_<?= $user['id'] ?>" style="display:none; font-size:12px; text-align:left;">
                            <li id="longitud_<?= $user['id'] ?>" style="color:red;">♦️ Mínimo 8 caracteres</li>
                            <li id="mayuscula_<?= $user['id'] ?>" style="color:red;">♦️ 1 mayúscula</li>
                            <li id="minuscula_<?= $user['id'] ?>" style="color:red;">♦️ 1 minúscula</li>
                            <li id="numero_<?= $user['id'] ?>" style="color:red;">♦️ 1 número</li>
                            <li id="especial_<?= $user['id'] ?>" style="color:red;">♦️ 1 carácter especial (@$!%*?&)</li>
                        </ul>
                        <select name="rol">
                            <?php foreach ($roles as $key => $roleName): ?>
                                <option value="<?= $key ?>" <?= $user['rol'] == $key ? 'selected' : '' ?>><?= $roleName ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update">Actualizar</button>
                    </form>

                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                        <button type="submit" name="delete" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="links">
        <a href='46378123782137321.php'>➕ Crear Usuario</a> | 
        <a href='0192838712364783.php'>⬅ Volver</a>
    </div>

    <form method="POST">
        <button type="button" class="refresh-button" onclick="location.reload();">
            <img src="img/refresh.gif" alt="Refrescar">
        </button>
    </form>
</div>

<script>
    function validarPassword(userId) {
        const password = document.getElementById(`password_${userId}`).value;
        const requisitos = {
            longitud: password.length >= 8,
            mayuscula: /[A-Z]/.test(password),
            minuscula: /[a-z]/.test(password),
            numero: /[0-9]/.test(password),
            especial: /[@$!%*?&]/.test(password)
        };
        for (const [clave, valido] of Object.entries(requisitos)) {
            const elem = document.getElementById(`${clave}_${userId}`);
            elem.style.color = valido ? 'green' : 'red';
            elem.innerHTML = (valido ? '✅' : '♦️') + elem.textContent.slice(1);
        }
    }

    function mostrarRequisitos(userId) {
        document.getElementById(`requisitos_${userId}`).style.display = 'block';
    }

    function ocultarRequisitosSiVacio(userId) {
        const val = document.getElementById(`password_${userId}`).value;
        if (!val) document.getElementById(`requisitos_${userId}`).style.display = 'none';
    }

    function esPasswordValida(password) {
        return (
            password.length >= 8 &&
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password) &&
            /[0-9]/.test(password) &&
            /[@$!%*?&]/.test(password)
        );
    }

    function verificarFormulario(userId) {
        const password = document.getElementById(`password_${userId}`).value;
        if (!esPasswordValida(password)) {
            alert("La contraseña no cumple con los requisitos.");
            return false;
        }
        return true;
    }
</script>

</body>
</html>

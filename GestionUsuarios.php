<?php
session_start();

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 180)) {
    session_unset();     // Borra todas las variables de sesión
    session_destroy();   // Destruye la sesión
    header("Location: login.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time(); // Actualiza el tiempo de última actividad

function registrarAuditoria($pdo, $accion, $usuarioAfectadoId, $datosAntes, $datosDespues)
{
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

require_once __DIR__ . '/config.php';
// Verificar si el usuario tiene el rol adecuado
if ($_SESSION['rol'] != '1' && $_SESSION['rol'] != '2') {
    // Si el usuario no tiene el rol 1 (Admin) ni el rol 2, redirigire
    header('Location: login.php');
    exit;
}
$esSuperadministrador = $_SESSION['rol'] == '1';
// Conectar a la base de datos
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Actualizar usuario
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $rol = $_POST['rol'];

    $rolesPermitidos = $esSuperadministrador ? ['2', '3', '4', '5'] : ['3', '4', '5'];
    if (!in_array((string) $rol, $rolesPermitidos, true)) {
        http_response_code(400);
        exit('Rol no permitido.');
    }

    // Obtener datos antes del cambio
    $selectSql = $esSuperadministrador
        ? "SELECT * FROM Usuarios WHERE id = :id AND id != :current_user_id AND rol != '1'"
        : "SELECT * FROM Usuarios WHERE id = :id AND id != :current_user_id AND rol NOT IN ('1', '2')";
    $stmtOld = $pdo->prepare($selectSql);
    $stmtOld->execute([
        ':id' => $id,
        ':current_user_id' => $_SESSION['user_id'],
    ]);
    $datosAntes = $stmtOld->fetch(PDO::FETCH_ASSOC);
    if (!$datosAntes) {
        http_response_code(404);
        exit('Usuario no encontrado o no administrable.');
    }

    // Actualizar usuario
    $updateSql = $esSuperadministrador
        ? "UPDATE Usuarios SET username = :username, password = :password, rol = :rol WHERE id = :id AND id != :current_user_id AND rol != '1'"
        : "UPDATE Usuarios SET username = :username, password = :password, rol = :rol WHERE id = :id AND id != :current_user_id AND rol NOT IN ('1', '2')";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':username' => $username,
        ':password' => $hashedPassword,
        ':rol' => $rol,
        ':id' => $id,
        ':current_user_id' => $_SESSION['user_id'],
    ]);

    // Registrar auditoría (sin mostrar la contraseña)
    $datosDespues = [
        'username' => $username,
        'rol' => $rol
    ];
    // Después de actualizar
    registrarAuditoria($pdo, 'actualizar', $id, $datosAntes, $datosDespues);

    // Redirecciona pasando una variable de éxito por la URL
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=updated");
    exit;
}

// Eliminar usuario
if (isset($_POST['delete'])) {
    $id = $_POST['id'];

    // Obtener datos antes de eliminar
    $selectSql = $esSuperadministrador
        ? "SELECT * FROM Usuarios WHERE id = :id AND id != :current_user_id AND rol != '1'"
        : "SELECT * FROM Usuarios WHERE id = :id AND id != :current_user_id AND rol NOT IN ('1', '2')";
    $stmtOld = $pdo->prepare($selectSql);
    $stmtOld->execute([
        ':id' => $id,
        ':current_user_id' => $_SESSION['user_id'],
    ]);
    $datosAntes = $stmtOld->fetch(PDO::FETCH_ASSOC);
    if (!$datosAntes) {
        http_response_code(404);
        exit('Usuario no encontrado o no administrable.');
    }

    // Eliminar usuario
    $deleteSql = $esSuperadministrador
        ? "DELETE FROM Usuarios WHERE id = :id AND id != :current_user_id AND rol != '1'"
        : "DELETE FROM Usuarios WHERE id = :id AND id != :current_user_id AND rol NOT IN ('1', '2')";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([
        ':id' => $id,
        ':current_user_id' => $_SESSION['user_id'],
    ]);

    // Registrar auditoría
    registrarAuditoria($pdo, 'eliminar', $id, $datosAntes, null);

    // Redirecciona pasando una variable de éxito por la URL
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=deleted");
    exit;
}

// Superadministradores ven todos los demás usuarios; administradores ven roles 3, 4 y 5.
$sql = $esSuperadministrador
    ? "SELECT id, nombre_completo, documento, username, rol FROM Usuarios WHERE id != :current_user_id AND rol != '1'"
    : "SELECT id, nombre_completo, documento, username, rol FROM Usuarios WHERE rol NOT IN ('1', '2')";
$stmt = $pdo->prepare($sql);
$params = $esSuperadministrador ? [':current_user_id' => $_SESSION['user_id']] : [];
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="icon" type="image/svg" href="img/icono.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f8ff;
            color: #000080;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 1050px;
            margin: 40px auto;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(16, 42, 67, 0.08);
            animation: fadeIn 0.4s ease-in-out;
        }

        h2 {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 700;
            color: #00264d;
            border-bottom: 3px solid #00264d;
            padding-bottom: 12px;
            display: inline-block;
        }

        table {
            width: 100%;
            margin-top: 15px;
            border-collapse: separate;
            border-spacing: 0;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 0 1px #e2e8f0;
        }

        th,
        td {
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-top: none;
            border-left: none;
            padding: 16px;
            text-align: center;
            vertical-align: middle;
        }

        th:last-child,
        td:last-child {
            border-right: none;
        }

        tr:last-child td {
            border-bottom: none;
        }

        th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #cbd5e1;
        }

        .table thead th {
            font-size: 15px;
            font-weight: bold;
            padding: 18px 12px;
            background-color: #d0e7ff;
            color: #00264d;
            text-align: center;
            border-bottom: 1px solid #0059b3;
            vertical-align: middle;
            white-space: nowrap;
        }

        .table td {
            text-align: center;
            vertical-align: middle;
            font-size: 14px;
            border: 1px solid #dee2e6;
            padding: 10px 12px;
        }

        .table.table-bordered tbody td {
            padding: 4px 8px;
            line-height: 1.25;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px 14px;
            margin-top: 4px;
            margin-bottom: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f8fafc;
            color: #1e293b;
            transition: all 0.25s ease;
            box-sizing: border-box;
        }

        input::placeholder {
            color: #94a3b8;
            opacity: 1;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        select {
            background-color: #f8fafc;
            cursor: pointer;
        }

        button {
            background-color: #1d4ed8;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            width: 100%;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 2px 4px rgba(29, 78, 216, 0.2);
        }

        button:hover {
            background-color: #1e40af;
        }

        button:active {
            transform: scale(0.98);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: minmax(240px, 1fr) minmax(160px, 0.7fr);
            gap: 15px;
            align-items: start;
        }

        .action-buttons .update-form {
            min-width: 0;
            margin: 0;
        }

        .action-controls {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .action-controls form {
            margin: 0;
        }

        .action-controls .delete-button {
            background-color: #ef4444;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }

        .action-controls .delete-button:hover {
            background-color: #dc2626;
        }

        .alert {
            margin-top: 15px;
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .links {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .links a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin: 0 15px;
            transition: color 0.2s ease;
        }

        .links a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .refresh-button {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 50%;
            cursor: pointer;
            margin: 20px auto 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .refresh-button:hover {
            background-color: #e2e8f0;
            transform: rotate(180deg);
        }

        .refresh-button img {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <link href="fonts.css" rel="stylesheet">
</head>

<body>

    <div class="container">
        <h2>Gestión de Usuarios</h2>

        <?php
        // Ahora leemos los mensajes desde el parámetro GET enviado por la redirección
        if (isset($_GET['success']) && $_GET['success'] == 'updated') {
            echo "<div class='alert alert-success'>Usuario actualizado correctamente.</div>";
        }
        if (isset($_GET['success']) && $_GET['success'] == 'deleted') {
            echo "<div class='alert alert-danger'>Usuario eliminado correctamente.</div>";
        }
        ?>

        <table class="table table-bordered">
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
                    2 => 'Administrador',
                    3 => 'Gestión Humana',
                    4 => 'Invitado',
                    5 => 'Auditoría',
                ];
                if (!$esSuperadministrador) {
                    unset($roles[2]);
                }

                foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['nombre_completo']) ?></td>
                        <td><?= $roles[$user['rol']] ?? 'Desconocido' ?></td>
                        <td class="action-buttons">
                            <form id="update-form-<?= $user['id'] ?>" class="update-form" action="" method="POST"
                                onsubmit="return verificarFormulario(<?= $user['id'] ?>)">
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
                            </form>

                            <div class="action-controls">
                                <button type="submit" form="update-form-<?= $user['id'] ?>" name="update">Actualizar</button>
                                <form action="" method="POST">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                                    <button class="delete-button" type="submit" name="delete"
                                        onclick="return confirm('¿Estás seguro de eliminar este usuario?')">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="links">
            <a href='RegistroUsuario.php'><i class="bi bi-plus-circle"></i> Crear Usuario</a> |
            <a href='PanelAdministrador.php'><i class="bi bi-arrow-left"></i> Volver</a>
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
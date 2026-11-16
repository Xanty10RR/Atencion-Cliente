<?php
session_start();
// Control de inactividad - expira tras 3 minutos (180 segundos)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 180)) {
    session_unset();     // Borra todas las variables de sesión
    session_destroy();   // Destruye la sesión
    header("Location: login.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time(); // Actualiza el tiempo de última actividad

require_once __DIR__ . '/config.php';

// Solo administradores pueden ver la auditoría
if ($_SESSION['rol'] != '1' && $_SESSION['rol'] != '5') {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT * FROM auditoria_usuarios ORDER BY fecha DESC";
    $stmt = $pdo->query($sql);
    $auditorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Auditoría</title>
    <link rel="icon" type="image/svg" href="img/icono.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f8ff;
            color: #000080;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .container-auditoria {
            max-width: 1250px;
            margin: 30px auto;
            background: #ffffff;
            padding: 35px;
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

        .nav-links {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .nav-links a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: color 0.2s ease;
        }

        .nav-links a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-top: 15px;
        }

        table {
            width: 100%;
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
            border-right: none;
            border-top: none;
            border-left: none;
            padding: 14px 12px;
            text-align: center;
            vertical-align: middle;
            font-size: 13px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-bottom: 2px solid #cbd5e1;
        }

        tr:nth-child(even) {
            background-color: #fafbfc;
        }

        /* Estilo limpio para las listas de datos (JSON decodificado) */
        td ul {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
            font-size: 12px;
            color: #334155;
        }

        td ul li {
            padding: 2px 0;
            border-bottom: 1px dashed #f1f5f9;
        }

        td ul li:last-child {
            border-bottom: none;
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
    <div class="container-auditoria">
        <h2>Historial de Auditoría</h2>

        <div class="nav-links">
            <?php if ($_SESSION['rol'] == '1'): ?>
                <a href="PanelSuperadministrador.php"><i class="bi bi-arrow-left me-1"></i> Volver</a> |
            <?php endif; ?>
            <a href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Acción</th>
                        <th>Usuario Afectado</th>
                        <th>Cambio hecho por</th>
                        <th>Informacion</th>
                        <th>Antes</th>
                        <th>Después</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditorias as $aud): ?>
                        <tr>
                            <td><?= $aud['fecha'] ?></td>
                            <td>
                                <?php
                                $accion = strtolower($aud['accion']);
                                $colores = [
                                    'crear' => '#059669',
                                    'actualizar' => '#2563eb',
                                    'eliminar' => '#dc2626'
                                ];
                                $color = $colores[$accion] ?? '#334155';
                                echo "<span style='color: $color; font-weight: 600; text-transform: capitalize;'>$accion</span>";
                                ?>
                            </td>

                            <td><?= htmlspecialchars($aud['username_afectado']) ?></td>
                            <td><?= htmlspecialchars($aud['realizado_por']) ?></td>
                            <td>
                                <?php
                                $anteriores = json_decode($aud['datos_anteriores'], true);
                                $mostrarCampos = ['documento', 'nombre_completo'];
                                $nombresRoles = [
                                    1 => 'SuperAdmin',
                                    2 => 'Administrador',
                                    3 => 'Gestión Humana',
                                    4 => 'Invitado',
                                    5 => 'Auditoría'
                                ];

                                if (is_array($anteriores)) {
                                    echo "<ul>";
                                    foreach ($anteriores as $key => $value) {
                                        if (in_array($key, $mostrarCampos)) {
                                            if ($key == 'rol') {
                                                $value = $nombresRoles[$value] ?? $value;
                                            }
                                            echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>";
                                        }
                                    }
                                    echo "</ul>";
                                } else {
                                    echo "<span class='text-muted'>Sin datos</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $anteriores = json_decode($aud['datos_anteriores'], true);
                                $mostrarCampos = ['rol', 'username'];

                                if (is_array($anteriores)) {
                                    echo "<ul>";
                                    foreach ($anteriores as $key => $value) {
                                        if (in_array($key, $mostrarCampos)) {
                                            if ($key == 'rol') {
                                                $value = $nombresRoles[$value] ?? $value;
                                            }
                                            echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>";
                                        }
                                    }
                                    echo "</ul>";
                                } else {
                                    echo "<span class='text-muted'>Sin datos</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $nuevos = json_decode($aud['datos_nuevos'], true);
                                $mostrarCampos = ['rol', 'username', 'documento', 'nombre_completo'];

                                if (is_array($nuevos)) {
                                    echo "<ul>";
                                    foreach ($nuevos as $key => $value) {
                                        if (in_array($key, $mostrarCampos)) {
                                            if ($key == 'rol') {
                                                $value = $nombresRoles[$value] ?? $value;
                                            }
                                            echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>";
                                        }
                                    }
                                    echo "</ul>";
                                } else {
                                    echo "<span class='text-muted'>Sin datos</span>";
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
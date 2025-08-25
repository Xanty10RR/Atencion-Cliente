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

include('47829374983274.php');

// Solo administradores pueden ver la auditoría
if ($_SESSION['rol'] != '1' && $_SESSION['rol'] != '5') {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
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
    <link rel="icon" type="image/png" href="img/icon.jpg">
    <title>Registro de Auditoría</title>
    <style>
        body {
            background-color: #f0f8ff;
            font-family: Arial, sans-serif;
            color: #000080;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #000080;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th, td {
            border: 1px solid #000080;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #cce5ff;
        }
        tr:nth-child(even) {
            background-color: #e6f2ff;
        }
        a {
            display: block;
            margin-top: 20px;
            color: #000080;
            text-align: center;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<h2>Historial de Auditoría</h2>
<?php if ($_SESSION['rol'] == '1'): ?>
    <a href="22131325843513551.php">← Volver</a>
<?php endif; ?>
<a href="logout.php">Cerrar sesión</a>
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
    $accion = strtolower($aud['accion']); // en minúscula para evitar errores de comparación
    $colores = [
        'crear' => 'green',
        'actualizar' => 'blue',
        'eliminar' => 'red'
    ];
    $color = $colores[$accion] ?? 'black'; // color por defecto si no coincide
    echo "<span style='color: $color; font-weight: bold; text-transform: capitalize;'>$accion</span>";
    ?>
</td>

            <td><?= $aud['username_afectado'] ?></td>
            <td><?= $aud['realizado_por'] ?></td>
            <td><?php
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
        echo "Sin datos";
    }
    ?>
</td></td>
            <td>
    <?php
    $anteriores = json_decode($aud['datos_anteriores'], true);
    $mostrarCampos = ['rol', 'username'];
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
        echo "Sin datos";
    }
    ?>
</td>

<td>
    <?php
    $nuevos = json_decode($aud['datos_nuevos'], true);
    $mostrarCampos = ['rol', 'username', 'documento', 'nombre_completo'];
    $nombresRoles = [
        1 => 'SuperAdmin',
        2 => 'Administrador',
        3 => 'Gestión Humana',
        4 => 'Invitado',
        5 => 'Auditoría'
    ];

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
        echo "Sin datos";
    }
    ?>
</td>

        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>

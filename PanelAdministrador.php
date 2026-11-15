<?php
session_start();

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 180)) {
    session_unset();     // Borra todas las variables de sesión
    session_destroy();   // Destruye la sesión
    header("Location: login.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time(); // Actualiza el tiempo de última actividad

// Verificar si el usuario tiene el rol adecuado
if ($_SESSION['rol'] != '2') {
    // Si no es admin, redirigir a la página de inicio o mostrar un error
    header('Location: login.php');
    exit;
}
try {
    // Conexión a PostgreSQL
    require_once __DIR__ . '/config.php';
    // Conexión a la base de datos usando los valores del archivo config.php
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    // Configurar el manejo de errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Buscar por código de cliente si se proporciona
    $codigoCliente = isset($_GET['id_vendedor']) ? $_GET['id_vendedor'] : '';
    $orden = isset($_GET['orden']) && $_GET['orden'] === 'desc' ? 'DESC' : 'ASC';
    $nuevoOrden = $orden === 'ASC' ? 'desc' : 'asc';

    // Buscar por rango de promedio si se proporciona
    $rangoPromedio = isset($_GET['rangoPromedio']) ? $_GET['rangoPromedio'] : '';
    $rangos = [
        '1  -  1.5' => [1, 1.499],
        '1.5  -  2' => [1.5, 1.999],
        '2  -  2.5' => [2, 2.499],
        '2.5  -  3' => [2.5, 2.999],
        '3  -  3.5' => [3, 3.499],
        '3.5  -  4' => [3.5, 3.999],
        '4  -  4.5' => [4, 4.499],
        '4.5  -  5' => [4.5, 5],
    ];

    $sql = "SELECT *, 
                (pregunta_1 + pregunta_2 + pregunta_3 + pregunta_4) / 4.0 AS promedio,
                (SELECT COUNT(*) FROM EncuestaSatisfaccion c2 WHERE c2.id_vendedor = EncuestaSatisfaccion.id_vendedor) AS frecuencia
            FROM EncuestaSatisfaccion";

    $conditions = [];
    $params = [];

    if (!empty($codigoCliente)) {
        $conditions[] = "id_vendedor = :codigoCliente";
        $params[':codigoCliente'] = $codigoCliente;
    }

    if (!empty($rangoPromedio) && isset($rangos[$rangoPromedio])) {
        list($min, $max) = $rangos[$rangoPromedio];
        $conditions[] = "(pregunta_1 + pregunta_2 + pregunta_3 + pregunta_4) / 4.0 BETWEEN :min AND :max";
        $params[':min'] = $min;
        $params[':max'] = $max;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY promedio $orden";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mejor y peor calificación
    $mejorCalificacion = null;
    $peorCalificacion = null;

    if (!empty($clientes)) {
        $mejorCalificacion = max(array_column($clientes, 'promedio'));
        $peorCalificacion = min(array_column($clientes, 'promedio'));
    }
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg" href="img/icono.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <title>Resumen de Calificaciones</title>
    <style>
        body {
            background-color: #f6f8fb;
            font-family: 'Segoe UI', sans-serif;
        }

        h3 {
            color: #00264d;
            font-weight: 700;
            border-bottom: 3px solid #00264d;
            padding-bottom: 10px;
            display: inline-block;
            margin-bottom: 25px;
        }

        .sidebar {
            min-width: 250px;
            max-width: 250px;
            height: 100vh;
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            color: #333;
        }

        .sidebar .nav-link:hover {
            background-color: #e9ecef;
            border-radius: 5px;
        }

        .submenu .nav-link {
            padding-left: 30px;
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

        td.comentarios {
            width: 230px;
            max-width: 230px;
            white-space: normal !important;
            overflow-wrap: break-word;
            text-align: left;
            vertical-align: top;
            font-size: 13px;
        }

        .logo img {
            height: 50px;
            object-fit: contain;
        }
    </style>
    <link href="fonts.css" rel="stylesheet">
</head>

<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-light bg-white shadow-sm px-4" style="height: 70px;">
        <div class="d-flex align-items-center logo">
            <img src="img/icon-web.webp" alt="Logo Empresa">
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="fw-semibold"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            <a href="logout.php" class="btn btn-danger btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
            </a>
        </div>
    </nav>

    <!-- Main Layout -->
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Buscador por código -->
            <form method="GET" class="px-2 pb-2">
                <small class="text-muted d-block mb-3 text-align-end">
                    <i class="bi bi-info-circle me-1"></i> Búsqueda por CC o código del vendedor
                </small>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="id_vendedor" placeholder="Buscar..." value="<?= htmlspecialchars($codigoCliente) ?>">
                </div>
            </form>

            <div class="accordion" id="menuAccordion">
                <!-- Ordenar -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#adminMenu">
                            <i class="bi bi-sort-down me-2"></i>Ordenar Por
                        </button>
                    </h2>
                    <div id="adminMenu" class="accordion-collapse collapse <?= isset($_GET['orden']) ? 'show' : '' ?>" data-bs-parent="#menuAccordion">
                        <div class="accordion-body p-0">
                            <ul class="nav flex-column submenu">
                                <li class="nav-item"><a class="nav-link" href="?orden=asc"><i class="bi bi-sort-numeric-up me-2 text-success"></i> Ascendente</a></li>
                                <li class="nav-item"><a class="nav-link" href="?orden=desc"><i class="bi bi-sort-numeric-down me-2 text-danger"></i> Descendente</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Búsqueda por Rango -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#conexionMenu">
                            <i class="bi bi-graph-up-arrow me-2"></i>Rango Promedio
                        </button>
                    </h2>
                    <div id="conexionMenu" class="accordion-collapse collapse <?= isset($_GET['rangoPromedio']) ? 'show' : '' ?>" data-bs-parent="#menuAccordion">
                        <div class="accordion-body px-2 py-1">
                            <ul class="nav flex-column">
                                <?php foreach ($rangos as $key => $value): ?>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 <?= $rangoPromedio === $key ? 'fw-bold text-primary' : '' ?>" href="?rangoPromedio=<?= urlencode($key) ?>">
                                            <i class="bi bi-star-fill text-warning me-1" style="font-size: 0.75rem;"></i> <?= $key ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li class="nav-item pt-2 border-top mt-1">
                                    <a class="nav-link py-1 <?= empty($rangoPromedio) ? 'fw-bold text-success' : '' ?>" href="PanelSuperadministrador.php">
                                        <i class="bi bi-eye me-1"></i> Ver todos
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Usuarios -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#usuariosMenu">
                            <i class="bi bi-people me-2"></i>Usuarios
                        </button>
                    </h2>
                    <div id="usuariosMenu" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                        <div class="accordion-body p-0">
                            <ul class="nav flex-column submenu">
                                <li class="nav-item"><a href="GestionUsuarios.php" class="nav-link">Gestión Usuarios</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descargar Excel -->
            <div class="d-grid mt-4">
                <a href="DescargarEncuestasExcel.php" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Descargar Excel
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="table-container">
            <div class="flex-grow-1 p-2">
                <h3 class="mb-4">Resumen de Calificaciones</h3>
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Código del Vendedor</th>
                            <th>Frecuencia</th>
                            <th>Atención al Cliente</th>
                            <th>Interacciones Rápidas</th>
                            <th>Buen Trato</th>
                            <th>Superación de Expectativas</th>
                            <th>Promedio</th>
                            <th>Comentarios</th>
                            <th>Nombre del Cliente</th>
                            <th>Celular</th>
                            <th>Fecha y Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= htmlspecialchars($cliente['id_vendedor']) ?></td>
                                <td><?= htmlspecialchars($cliente['frecuencia']) ?></td>
                                <td><?= htmlspecialchars($cliente['pregunta_1']) ?></td>
                                <td><?= htmlspecialchars($cliente['pregunta_2']) ?></td>
                                <td><?= htmlspecialchars($cliente['pregunta_3']) ?></td>
                                <td><?= htmlspecialchars($cliente['pregunta_4']) ?></td>
                                <td><?= number_format($cliente['promedio'], 2) ?></td>
                                <td class="comentarios"><?= htmlspecialchars($cliente['comentario']) ?></td>
                                <td><?= htmlspecialchars($cliente['nombre_cliente']) ?></td>
                                <td><?= htmlspecialchars($cliente['celular_cliente']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($cliente['fecha_votacion'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
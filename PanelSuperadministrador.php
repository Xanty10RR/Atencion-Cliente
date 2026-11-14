<?php
session_start();

// Control de inactividad (180 segundos = 3 minutos)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 180)) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Validación de rol de Administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != '1') {
    header('Location: login.php');
    exit;
}

try {
    // Conexión a la base de datos (Archivo renombrado a config.php)
    include('config.php');
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Parámetros de búsqueda y ordenamiento
    $codigoCliente = isset($_GET['id_vendedor']) ? trim($_GET['id_vendedor']) : '';
    $orden = isset($_GET['orden']) && $_GET['orden'] === 'desc' ? 'DESC' : 'ASC';
    $rangoPromedio = isset($_GET['rangoPromedio']) ? $_GET['rangoPromedio'] : '';

    $rangos = [
        '1 - 1.5' => [1, 1.499],
        '1.5 - 2' => [1.5, 1.999],
        '2 - 2.5' => [2, 2.499],
        '2.5 - 3' => [2.5, 2.999],
        '3 - 3.5' => [3, 3.499],
        '3.5 - 4' => [3.5, 3.999],
        '4 - 4.5' => [4, 4.499],
        '4.5 - 5' => [4.5, 5],
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
} catch (PDOException $e) {
    die("Error de conexión: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg" href="img/icono.svg">
    <title>Panel de Administración | Resumen de Calificaciones</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f6f8fb;
            font-family: 'Segoe UI', sans-serif;
        }

        .sidebar {
            min-width: 250px;
            max-width: 250px;
            height: calc(100vh - 70px);
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
            border-bottom: 2px solid #0059b3;
            vertical-align: middle;
            white-space: nowrap;
        }

        .table td {
            text-align: center;
            vertical-align: middle;
            font-size: 14px;
            border: 1px solid #dee2e6;
            padding: 10px 12px;
            /* Espaciado más cómodo y limpio */
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
                <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión
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
                    <i class="bi bi-info-circle me-1"></i> Búsqueda por código
                </small>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="id_vendedor" placeholder="Código..." value="<?= htmlspecialchars($codigoCliente) ?>">
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
                                            <i class="bi bi-dot"></i> <?= $key ?>
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
                                <li class="nav-item"><a href="GestionUsuarios.php" class="nav-link">Gestión usuarios</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descargar Excel -->
            <div class="d-grid mt-4">
                <a href="DescargarEncuestas.php" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Descargar Excel
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4" style="overflow-x: auto; height: calc(100vh - 70px);">
            <h3 class="mb-4 text-primary fw-bold">Resumen de Calificaciones</h3>

            <?php if (empty($clientes)): ?>
                <div class="alert alert-warning text-center">No se encontraron registros con los filtros seleccionados.</div>
            <?php else: ?>
                <div class="table-responsive bg-white rounded shadow-sm p-3">
                    <table class="table table-bordered table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código Vendedor</th>
                                <th>Frecuencia</th>
                                <th>Atención al Cliente</th>
                                <th>Interacciones Rápidas</th>
                                <th>Buen Trato</th>
                                <th>Superación Expectativas</th>
                                <th>Promedio</th>
                                <th>Comentarios</th>
                                <th>Nombre Cliente</th>
                                <th>Celular</th>
                                <th>Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($cliente['id_vendedor']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($cliente['frecuencia']) ?></span></td>
                                    <td><?= htmlspecialchars($cliente['pregunta_1']) ?></td>
                                    <td><?= htmlspecialchars($cliente['pregunta_2']) ?></td>
                                    <td><?= htmlspecialchars($cliente['pregunta_3']) ?></td>
                                    <td><?= htmlspecialchars($cliente['pregunta_4']) ?></td>
                                    <td class="fw-bold text-success"><?= number_format($cliente['promedio'], 2) ?></td>
                                    <td class="comentarios"><?= htmlspecialchars($cliente['comentario']) ?></td>
                                    <td><?= htmlspecialchars($cliente['nombre_cliente']) ?></td>
                                    <td><?= htmlspecialchars($cliente['celular_cliente']) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($cliente['fecha_votacion'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
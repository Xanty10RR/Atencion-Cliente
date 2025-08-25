<?php
session_start();

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 180)) {
    session_unset();     // Borra todas las variables de sesión
    session_destroy();   // Destruye la sesión
    header("Location: login.php?expired=1");
    exit;
}
$_SESSION['last_activity'] = time(); // Actualiza el tiempo de última actividad


if ($_SESSION['rol'] != '1') {
    // Si no es admin, redirigir a la página de inicio o mostrar un error
    header('Location: login.php');
    exit;
}
try {
    // Conexión a PostgreSQL
    include('47829374983274.php');
    // Conexión a la base de datos usando los valores del archivo config.php
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
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
    <link rel="icon" type="image/png" href="img/icon.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <title>Tabla de Clientes</title>
    <style>
        body {
            background-color: #f6f8fb;
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

        .table-container {
    padding: 10px 30px;
    max-width: 100%;
    height: 1000px;
}

.table th {
    background-color: #e0f0ff;
    color: #003366;
}


.table thead th {
    font-size: 16px;
    font-weight: bold;
    padding: 14px;
    background-color: #d0e7ff;
    color: #00264d;
    text-align: center;
    border-bottom: 2px solid #0059b3;
    vertical-align: middle;
}


    .table td {
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
        font-size: 14px;
        border: 1px solid #dee2e6;
    }
        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 60px;
            width: 250px;
        }
        td {
    word-wrap: break-word;
    word-break: break-word;
    white-space: normal;
    max-width: 180px;
}

.comentarios {
    max-width: 180px;
    word-wrap: break-word; /* Permite cortar palabras largas */
    word-break: break-word;
    white-space: normal;   /* Permite saltos de línea */
}

.table td, .table th {
    padding: 12px;
    font-size: 15px;
}

.table td:not(.comentarios) {
    white-space: nowrap; /* Aplica nowrap solo a las demás celdas */
}
td.comentarios {
    width: 230px;
    max-width: 230px; /* Límite máximo opcional */
    white-space: normal !important;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    text-align: left;
    vertical-align: top;
    font-size: 13px;
}

    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-light bg-white shadow-sm px-4">
        <div class="d-flex align-items-center gap-3">
            <div class="logo">
                <img src="img/logo1.jpg" alt="Logo">
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="btn btn-danger btn-sm">
                    <i></i>Cerrar sesión
                </a>
        </div>
    </nav>
    <!-- Main Layout -->
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Buscador en el menú lateral -->
<form method="GET" class="px-2 pb-2">
    <div class="input-group">
    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
    <input type="text" class="form-control" name="id_vendedor" placeholder="-" value="<?= htmlspecialchars($codigoCliente) ?>">
    </div>
</form>
<small class="text-muted d-block ms-3">
    <i class="bi bi-search me-1"></i> Búsqueda por código
</small>
            <br>
            <div class="accordion" id="menuAccordion">
<!-- Ordenar -->
<div class="accordion-item">
    <h2 class="accordion-header">
        <button class="accordion-button collapsed" data-bs-toggle="collapse"
            data-bs-target="#adminMenu">
            <i class="bi bi-gear me-2"></i>Ordenar Por
        </button>
    </h2>
    <div id="adminMenu" class="accordion-collapse collapse <?= isset($_GET['orden']) ? 'show' : '' ?>" data-bs-parent="#menuAccordion">
        <div class="accordion-body p-0">
            <ul class="nav flex-column submenu">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center" href="?orden=asc">
                        <i class="bi bi-sort-down me-2 text-success"></i> Ascendente
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center" href="?orden=desc">
                        <i class="bi bi-sort-up me-2 text-danger"></i> Descendente
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Busqueda por Rango -->
<div class="accordion-item">
    <h2 class="accordion-header">
    <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#conexionMenu">
    <i class="bi bi-plug me-2"></i>Seleccionar por rango
    </button>
    </h2>
    <div id="conexionMenu" class="accordion-collapse collapse <?= isset($_GET['rangoPromedio']) ? 'show' : '' ?>" data-bs-parent="#menuAccordion">
    <div class="accordion-body px-2 py-1">

    <?php
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
    ?>

    <ul class="nav flex-column">
        <?php foreach ($rangos as $key => $value): ?>
        <li class="nav-item">
            <a class="nav-link py-1 <?= $rangoPromedio === $key ? 'fw-bold text-primary' : '' ?>"
            href="?rangoPromedio=<?= urlencode($key) ?>">
            <i class="bi bi-graph-up me-1"></i> <?= $key ?>
            </a>
        </li>
        <?php endforeach; ?>

        <!-- "Ver todos" al final de la lista -->
        <li class="nav-item pt-2 border-top">
        <a class="nav-link py-1 <?= empty($rangoPromedio) ? 'fw-bold text-success' : '' ?>" href="?">
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
                        <button class="accordion-button collapsed" data-bs-toggle="collapse"
                            data-bs-target="#usuariosMenu">
                            <i class="bi bi-people me-2"></i>Usuarios
                        </button>
                    </h2>
                    <div id="usuariosMenu" class="accordion-collapse collapse" data-bs-parent="#menuAccordion">
                        <div class="accordion-body p-0">
                            <ul class="nav flex-column submenu">
                                <li class="nav-item"><a href="987541612115113.php" class="nav-link" href="#">Gestion usuarios</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Descargar Excel -->
            <div class="d-grid mt-3">
                <a href="723648764783264783.php" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Descargar Excel
                </a>
            </div>
        </div>

<!-- Main Content -->
<div class="table-container" style="overflow-x: auto; width: 100%;">
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
<?php
session_start();
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $_SESSION['error'] = 'La sesión ha expirado por inactividad. Inicia sesión nuevamente.';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inicio de sesión</title>
    <link rel="icon" href="img/icono.svg" type="image/svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f0f8ff;
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 1s ease;
        }

        .login-card h2 {
            color: #003366;
            margin-bottom: 25px;
        }

        .form-control {
            border-color: #d1d9e6;
        }

        .input-group-text {
            background-color: #fff;
            border-color: #d1d9e6;
            color: #000000;
        }

        .btn-primary {
            background-color: #003D7A;
            border-color: #003D7A;
            --bs-btn-active-bg: #003D7A;
            --bs-btn-active-border-color: #003D7A;
            --bs-btn-active-shadow: none;
            --bs-btn-focus-shadow-rgb: 0, 51, 102;
            -webkit-tap-highlight-color: transparent;
        }

        .btn-primary:hover {
            background-color: #002B57;
            border-color: #002B57;
        }

        .btn-primary:active,
        .btn-primary.active {
            background-color: #003D7A !important;
            border-color: #003D7A !important;
            box-shadow: none !important;
        }

        .btn-primary:focus,
        .btn-primary:focus-visible {
            box-shadow: none !important;
        }

        .error {
            color: red;
            font-size: 14px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
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

    <div class="login-card shadow-sm">
        <div class="text-center mb-4">
            <img src="img/icono.svg" alt="Logo" width="70" class="mb-2 rounded-circle">
            <h2></i>Iniciar Sesión</h2>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger text-center p-2">
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="verify.php" method="post">
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Ingresa tu usuario" required>
            </div>
            <div class="input-group mb-4">
                <span class="input-group-text"><i class="bi bi-lock-fill" aria-hidden="true"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Ingresa tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-door-open-fill me-1"></i>Ingresar
            </button>
        </form>
    </div>

    <!-- Bootstrap JS (opcional para funcionalidad extra) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
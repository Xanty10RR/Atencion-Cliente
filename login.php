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
    <link rel="icon" href="img/icon.jpg" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(to right, #cce5ff, #e6f0ff);
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
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 1s ease;
        }
        .login-card h2 {
            color: #003366;
            margin-bottom: 25px;
        }
        .form-control {
            border-color: #003366;
        }
        .btn-primary {
            background-color: #003366;
            border-color: #003366;
        }
        .btn-primary:hover {
            background-color: #001f4d;
            border-color: #001f4d;
        }
        .error {
            color: red;
            font-size: 14px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="login-card shadow-sm">
    <div class="text-center mb-4">
        <img src="img/icon.jpg" alt="Logo" width="70" class="mb-2 rounded-circle">
        <h2><i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión</h2>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger text-center p-2">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="verify.php" method="post">
        <div class="mb-3">
            <label class="form-label"><i class="bi bi-person-fill me-1"></i>Usuario</label>
            <input type="text" name="username" class="form-control" placeholder="Usuario" required>
        </div>
        <div class="mb-4">
            <label class="form-label"><i class="bi bi-lock-fill me-1"></i>Contraseña</label>
            <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
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

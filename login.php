<?php
    require_once __DIR__ . '/api/auth.php';
    startSecureSession();
    $message = $_SESSION['login_error'] ?? '';
    unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="resources/css/dashboard.css">
    <link rel="stylesheet" href="resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="resources/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesion</title>
</head>
<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">
        <?php if ($message): ?>
            <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        <form action="api/checkLogin.php" method="post" class="form-control mt-5 p-4" style="height:auto; width:380px;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="row">  
                    <h5 class="text-center p-4" style="font-weight:700;">Iniciar sesion</h5>
                </div>
                <div class="col-mb-3">
                    <label for="username">Nombre de usuario</label>
                    <input type="text" name="username" id="username" class="form-control" autocomplete="username" required>
                </div>
                <div class="col mb-3 mt-3">
                    <label for="password">Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" autocomplete="current-password" required>
                </div>
                <div class="align-items-center mb-3 mt-3">
                    <button type="submit" class="btn btn-outline-dark">Iniciar Sesion</button>
                </div>
        </form>
    </div>
    <script>document.querySelectorAll('.toast').forEach(el => new bootstrap.Toast(el,{delay:3000}).show());</script>
</body>
</html>
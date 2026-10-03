<?php
    require_once __DIR__ . '/api/auth.php';
    requireLogin(true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="./resources/css/custom.css">
    <link rel="stylesheet" href="./resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="./resources/css/bootstrap.min.css">
    <script src="./resources/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    <div class="container p-5 d-flex">
        <h2 class="p-4 mt-5">Bienvenido/a <?php echo htmlspecialchars($_SESSION['first_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>
</body>
</html>
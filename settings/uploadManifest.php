<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/css/font-awesome.css">
    <link rel="stylesheet" href="../resources/css/uploadManifest.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Manifiestos</title>
</head>

<body>
    <?php include '../header.php' ?>

    <div class="container">
        <div class="header">
            <h1>Carga de Manifiestos</h1>
        </div>
        
        <div class="content">
            <div class="drop-zone" id="dropZone">
                <div class="drop-zone-icon">📁</div>
                <div class="drop-zone-text">Arrastra y suelta tu archivo aquí</div>
                <div class="drop-zone-subtext">o haz clic para seleccionar</div>
                <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display: none;">
            </div>
            
            <div id="progress-container">
                <div class="progress-bar-wrapper">
                    <div class="progress-bar" id="progressBar">0%</div>
                </div>
            </div>
            
            <div id="result"></div>
            
            <div class="file-info" id="fileInfo">
                <strong>📄 Archivo seleccionado:</strong> <span id="fileName"></span>
                <br>
                <strong>📊 Tamaño:</strong> <span id="fileSize"></span>
                <br>
                <strong>📅 Fecha:</strong> <span id="fileDate"></span>
            </div>
            
            <button class="btn" id="manualUploadBtn" style="display: none;">📤 Subir Archivo</button>
            
            <div class="stats" id="stats" style="display: none;">
                <div class="stat">
                    <div class="stat-number" id="statsShipments">0</div>
                    <div class="stat-label">Shipments</div>
                </div>
                <div class="stat">
                    <div class="stat-number" id="statsClients">0</div>
                    <div class="stat-label">Clientes Nuevos</div>
                </div>
                <div class="stat">
                    <div class="stat-number" id="statsTotal">0</div>
                    <div class="stat-label">Total Registros</div>
                </div>
            </div>
        </div>
    </div>

    <script src="../resources/js/uploadManifest.js"></script>
</body>
</html>
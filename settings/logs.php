<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/auth.php';
require_once __DIR__ . '/../api/logView.php';
requireLogin(true);

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../resources/css/logView.css">
    <title>Administración de logs | APT Warehouse</title>
</head>
<body>
<main>
    <h1>Administración de logs</h1>
    <p class="muted">Consulta de registros del sistema y errores de importación de manifiestos. Acceso restringido al personal autorizado.</p>
    <div class="warning">La limpieza vacía el archivo seleccionado de forma permanente. Descarga o copia cualquier información necesaria antes de confirmar.</div>
    <div class="layout">
        <section class="panel" aria-labelledby="logs-heading">
            <div class="toolbar">
                <h2 id="logs-heading">Archivos disponibles</h2>
                <button class="action secondary" id="refresh" type="button">Actualizar</button>
            </div>
            <div id="status" class="notice" role="status" aria-live="polite">Cargando archivos…</div>
            <ul class="log-list" id="log-list"></ul>
        </section>
        <section class="panel" aria-labelledby="viewer-heading">
            <div class="toolbar">
                <div>
                    <h2 id="viewer-heading">Contenido</h2>
                    <div id="selected-meta" class="muted">Selecciona un archivo de la lista.</div>
                </div>
                <div>
                    <button class="action secondary" id="reload" type="button" disabled>Recargar</button>
                    <button class="action danger" id="clear" type="button" disabled>Limpiar archivo</button>
                </div>
            </div>
            <pre id="content" tabindex="0" aria-label="Contenido del archivo log">No hay ningún archivo seleccionado.</pre>
        </section>
    </div>
</main>
<script src="../resources/js/logsView.js"></script>
</body>
</html>

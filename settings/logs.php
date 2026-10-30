<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/auth.php';
requireLogin(true);

$logDir = realpath(__DIR__ . '/../logs');
if ($logDir === false || !is_dir($logDir)) {
    $logDir = __DIR__ . '/../logs';
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function jsonResponse(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/** Return only known log files; never accept arbitrary paths from the request. */
function allowedLogPath(string $name, string $directory): ?string {
    $allowed = in_array($name, ['webservices_access.log', 'webservices_error.log'], true)
        || preg_match('/^import_errors_\d{4}-\d{2}-\d{2}\.log$/D', $name) === 1;

    if (!$allowed || basename($name) !== $name) {
        return null;
    }

    $path = $directory . DIRECTORY_SEPARATOR . $name;
    if (is_link($path) || !is_file($path)) {
        return null;
    }

    $realPath = realpath($path);
    $realDirectory = realpath($directory);
    if ($realPath === false || $realDirectory === false
        || dirname($realPath) !== $realDirectory) {
        return null;
    }

    return $realPath;
}

/** Read a bounded tail instead of loading a potentially huge log into memory. */
function readLogTail(string $path, int $maxLines = 500, int $maxBytes = 1048576): string {
    $handle = @fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('No se pudo abrir el archivo de log.');
    }

    try {
        if (!flock($handle, LOCK_SH)) {
            throw new RuntimeException('No se pudo bloquear el archivo para lectura.');
        }
        $size = fstat($handle)['size'] ?? 0;
        $start = max(0, $size - $maxBytes);
        if ($start > 0) {
            fseek($handle, $start);
        }
        $content = stream_get_contents($handle, $maxBytes);
        flock($handle, LOCK_UN);
        if (!is_string($content)) {
            throw new RuntimeException('No se pudo leer el archivo de log.');
        }
    } finally {
        fclose($handle);
    }

    if ($start > 0) {
        $newline = strpos($content, "\n");
        $content = $newline === false ? '' : substr($content, $newline + 1);
    }

    $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
    if (count($lines) > $maxLines) {
        $lines = array_slice($lines, -$maxLines);
    }

    $prefix = ($start > 0) ? "[Vista parcial: se omitieron datos antiguos por el límite de lectura]\n" : '';
    return $prefix . implode("\n", $lines);
}

function listLogs(string $directory): array {
    $names = ['webservices_access.log', 'webservices_error.log'];
    foreach (glob($directory . DIRECTORY_SEPARATOR . 'import_errors_*.log') ?: [] as $file) {
        $name = basename($file);
        if (preg_match('/^import_errors_\d{4}-\d{2}-\d{2}\.log$/D', $name) === 1) {
            $names[] = $name;
        }
    }

    $result = [];
    foreach (array_unique($names) as $name) {
        $path = allowedLogPath($name, $directory);
        if ($path === null) {
            $result[] = ['name' => $name, 'exists' => false, 'size' => 0, 'modified' => null];
            continue;
        }
        $stat = @stat($path);
        $result[] = [
            'name' => $name,
            'exists' => true,
            'size' => (int) ($stat['size'] ?? 0),
            'modified' => isset($stat['mtime']) ? date('Y-m-d H:i:s', (int) $stat['mtime']) : null,
        ];
    }

    usort($result, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));
    return $result;
}

if (!is_dir($logDir) || !is_readable($logDir)) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
        && isset($_GET['action'])) {
        jsonResponse(['success' => false, 'error' => 'El directorio de logs no está disponible.'], 500);
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET' && isset($_GET['action'])) {
    if (!is_dir($logDir) || !is_readable($logDir)) {
        jsonResponse(['success' => false, 'error' => 'El directorio de logs no está disponible.'], 500);
    }

    $action = (string) $_GET['action'];
    if ($action === 'list') {
        jsonResponse(['success' => true, 'logs' => listLogs($logDir)]);
    }

    if ($action === 'read') {
        $name = isset($_GET['file']) && is_string($_GET['file']) ? $_GET['file'] : '';
        $path = allowedLogPath($name, $logDir);
        if ($path === null) {
            jsonResponse(['success' => false, 'error' => 'Archivo no permitido o inexistente.'], 404);
        }
        try {
            jsonResponse(['success' => true, 'file' => $name, 'content' => readLogTail($path)]);
        } catch (RuntimeException $exception) {
            jsonResponse(['success' => false, 'error' => $exception->getMessage()], 500);
        }
    }

    jsonResponse(['success' => false, 'error' => 'Acción no válida.'], 400);
}

if ($method === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';
    $name = isset($_POST['file']) && is_string($_POST['file']) ? $_POST['file'] : '';
    $path = allowedLogPath($name, $logDir);

    if ($action !== 'clear' || $path === null) {
        jsonResponse(['success' => false, 'error' => 'Acción o archivo no válido.'], 400);
    }

    $handle = @fopen($path, 'c');
    if ($handle === false) {
        jsonResponse(['success' => false, 'error' => 'No se pudo abrir el archivo para limpiarlo.'], 500);
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            jsonResponse(['success' => false, 'error' => 'No se pudo bloquear el archivo.'], 500);
        }
        if (!ftruncate($handle, 0) || !fflush($handle)) {
            jsonResponse(['success' => false, 'error' => 'No se pudo vaciar el archivo.'], 500);
        }
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }

    jsonResponse(['success' => true, 'message' => 'El archivo se ha vaciado correctamente.', 'file' => $name]);
}

if ($method !== 'GET') {
    header('Allow: GET, POST');
    jsonResponse(['success' => false, 'error' => 'Método no permitido.'], 405);
}

$csrf = csrfToken();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración de logs | APT Warehouse</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        body { margin: 0; background: #f3f5f8; color: #1f2937; }
        main { max-width: 1200px; margin: 28px auto; padding: 0 18px; }
        h1 { margin-bottom: 6px; }
        .muted { color: #64748b; }
        .layout { display: grid; grid-template-columns: minmax(260px, 350px) minmax(0, 1fr); gap: 18px; margin-top: 22px; }
        .panel { background: white; border: 1px solid #dbe2ea; border-radius: 10px; padding: 16px; min-width: 0; box-shadow: 0 2px 8px #0f172a0a; }
        .log-list { list-style: none; margin: 12px 0 0; padding: 0; }
        .log-item { display: block; width: 100%; text-align: left; margin: 7px 0; padding: 11px; border: 1px solid #dbe2ea; border-radius: 7px; background: #fff; color: inherit; cursor: pointer; overflow-wrap: anywhere; }
        .log-item[aria-pressed="true"] { border-color: #2563eb; background: #eff6ff; }
        .log-item:disabled { opacity: .55; cursor: not-allowed; }
        .meta { display: block; margin-top: 5px; font-size: .82rem; color: #64748b; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
        button { font: inherit; }
        .action { border: 0; border-radius: 7px; padding: 9px 13px; cursor: pointer; }
        .secondary { background: #e2e8f0; color: #1e293b; }
        .danger { background: #b91c1c; color: #fff; }
        .action:disabled { opacity: .55; cursor: not-allowed; }
        pre { white-space: pre-wrap; overflow-wrap: anywhere; background: #0b1220; color: #e2e8f0; border-radius: 8px; padding: 16px; min-height: 340px; max-height: 65vh; overflow: auto; font: 13px/1.5 ui-monospace, SFMono-Regular, Consolas, monospace; }
        .notice { padding: 10px 12px; border-radius: 7px; margin-top: 14px; background: #eff6ff; color: #1d4ed8; }
        .notice.error { background: #fef2f2; color: #991b1b; }
        .warning { font-size: .9rem; color: #92400e; background: #fffbeb; padding: 10px; border-radius: 7px; margin-top: 14px; }
        @media (max-width: 760px) { .layout { grid-template-columns: 1fr; } pre { min-height: 260px; } }
    </style>
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
<script>
'use strict';
const csrfToken = <?= json_encode($csrf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const listElement = document.getElementById('log-list');
const contentElement = document.getElementById('content');
const statusElement = document.getElementById('status');
const metaElement = document.getElementById('selected-meta');
const clearButton = document.getElementById('clear');
const reloadButton = document.getElementById('reload');
let selectedFile = null;

function setStatus(message, isError = false) {
    statusElement.textContent = message;
    statusElement.classList.toggle('error', isError);
}
function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}
async function requestJson(url, options = {}) {
    const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.error || 'La solicitud no pudo completarse.');
    return data;
}
async function loadLogs() {
    setStatus('Cargando archivos…');
    try {
        const data = await requestJson('?action=list');
        listElement.replaceChildren();
        data.logs.forEach(log => {
            const item = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'log-item';
            button.disabled = !log.exists;
            button.setAttribute('aria-pressed', String(log.name === selectedFile));
            const name = document.createElement('span');
            name.textContent = log.name;
            const details = document.createElement('span');
            details.className = 'meta';
            details.textContent = log.exists ? formatSize(log.size) + ' · Modificado: ' + (log.modified || 'desconocido') : 'Todavía no existe';
            button.append(name, details);
            button.addEventListener('click', () => loadLog(log.name));
            item.append(button);
            listElement.append(item);
        });
        setStatus(data.logs.length + ' archivo(s) encontrado(s).');
        if (selectedFile && data.logs.some(log => log.name === selectedFile && log.exists)) {
            await loadLog(selectedFile);
        }
    } catch (error) {
        setStatus(error.message, true);
    }
}
async function loadLog(name) {
    selectedFile = name;
    clearButton.disabled = false;
    reloadButton.disabled = false;
    listElement.querySelectorAll('button').forEach(button => {
        button.setAttribute('aria-pressed', String(button.querySelector('span')?.textContent === name));
    });
    metaElement.textContent = name;
    contentElement.textContent = 'Cargando contenido…';
    try {
        const data = await requestJson('?action=read&file=' + encodeURIComponent(name));
        contentElement.textContent = data.content || '(El archivo está vacío.)';
        setStatus('Archivo cargado: ' + name);
    } catch (error) {
        contentElement.textContent = '';
        setStatus(error.message, true);
    }
}
async function clearLog() {
    if (!selectedFile) return;
    const name = selectedFile;
    const confirmed = window.confirm('¿Vaciar permanentemente "' + name + '"? Esta acción no se puede deshacer.');
    if (!confirmed) return;
    clearButton.disabled = true;
    try {
        const body = new URLSearchParams({ action: 'clear', file: name, csrf_token: csrfToken });
        const data = await requestJson(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
            body
        });
        setStatus(data.message);
        await loadLogs();
        await loadLog(name);
    } catch (error) {
        setStatus(error.message, true);
    } finally {
        clearButton.disabled = false;
    }
}
document.getElementById('refresh').addEventListener('click', loadLogs);
reloadButton.addEventListener('click', () => selectedFile && loadLog(selectedFile));
clearButton.addEventListener('click', clearLog);
loadLogs();
</script>
</body>
</html>

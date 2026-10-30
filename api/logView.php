<?php
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
<?php
require_once __DIR__ . '/../auth.php';
requireApiLogin(true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../db_connect.php';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare('SELECT id, name, is_active FROM warehouse_locations ORDER BY name');
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['ok' => true, 'locations' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); header('Allow: GET, POST');
        echo json_encode(['error' => 'Método no permitido']); exit;
    }
    requireCsrf(); requireApiAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = (string)($data['action'] ?? '');
    if ($action === 'save') {
        $id = (int)($data['id'] ?? 0); $name = trim((string)($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            http_response_code(400); echo json_encode(['error' => 'Nombre de almacén inválido.']); exit;
        }
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE warehouse_locations SET name = ? WHERE id = ?');
            $stmt->bind_param('si', $name, $id);
        } else {
            $stmt = $conn->prepare('INSERT INTO warehouse_locations (name) VALUES (?)');
            $stmt->bind_param('s', $name);
        }
        $stmt->execute(); $stmt->close(); echo json_encode(['ok' => true]); exit;
    }
    if ($action === 'toggle') {
        $id = (int)($data['id'] ?? 0); $active = (int)($data['is_active'] ?? 0) === 1 ? 1 : 0;
        if ($id < 1) { http_response_code(400); echo json_encode(['error' => 'Almacén inválido.']); exit; }
        $stmt = $conn->prepare('UPDATE warehouse_locations SET is_active = ? WHERE id = ?');
        $stmt->bind_param('ii', $active, $id); $stmt->execute(); $stmt->close();
        echo json_encode(['ok' => true]); exit;
    }
    http_response_code(400); echo json_encode(['error' => 'Acción no válida.']);
} catch (mysqli_sql_exception $error) {
    error_log('Warehouse locations error: ' . $error->getMessage());
    http_response_code($error->getCode() === 1062 ? 409 : 500);
    echo json_encode(['error' => $error->getCode() === 1062 ? 'Ya existe un almacén con ese nombre.' : 'No se pudo guardar el almacén.']);
} catch (Throwable $error) {
    error_log('Warehouse locations error: ' . $error->getMessage());
    http_response_code(500); echo json_encode(['error' => 'No se pudo completar la operación.']);
}

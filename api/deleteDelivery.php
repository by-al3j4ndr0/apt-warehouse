<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';

requireLogin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

requireCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error_message'] = 'Invalid delivery ID.';
    header('Location: ../delivery/deliveries.php');
    exit();
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT status FROM delivery WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $route = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$route) {
        throw new Exception('Route not found.');
    }
    if ($route['status'] !== 'draft') {
        throw new Exception('Only draft routes can be deleted.');
    }

    $stmt = $conn->prepare("UPDATE shipments SET status = 'warehouse', route_id = 0 WHERE route_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM delivery WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION['success_message'] = 'Delivery route removed successfully!';
} catch (Throwable $e) {
    $conn->rollback();
    error_log('Delivery deletion error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Failed to remove delivery.';
}

header('Location: ../delivery/deliveries.php');
exit();
?>
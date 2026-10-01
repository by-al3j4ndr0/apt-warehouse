<?php
    require_once __DIR__ . '/auth.php';
    requireLogin(true);

    $type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
    $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';

    if (!in_array($type, ['ci', 'hbl', 'route_id'], true) || $id === '') {
        http_response_code(400);
        exit('Invalid redirect parameters');
    }

    if ($type === 'route_id') {
        if (!ctype_digit($id) || (int) $id < 1) {
            http_response_code(400);
            exit('Invalid redirect parameters');
        }

        header('Location: ../pdf/exportPdf.php?id=' . rawurlencode($id));
        exit;
    }

    if (mb_strlen($id) > 64 || !preg_match('/^[A-Za-z0-9-]+$/', $id)) {
        http_response_code(400);
        exit('Invalid redirect parameters');
    }

    $target = $type === 'ci'
        ? '../search/details.php?ci='
        : '../search/shipmentsDetails.php?hbl=';

    header('Location: ' . $target . rawurlencode($id));
    exit;
?>

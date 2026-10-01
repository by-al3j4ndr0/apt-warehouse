<?php
    require_once __DIR__ . '/auth.php';
    requireLogin(true);

    // Validar y obtener el tipo
    $typo = isset($_GET['type']) ? (string) $_GET['type'] : '';
    $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';

    if (!in_array($typo, ['ci', 'hbl', 'route_id'], true) || $id === '' || mb_strlen($id) > 64) {
        http_response_code(400);
        exit('Invalid redirect parameters');
    }

    if ($typo === 'ci') {
        header("Location: ../search/details.php?ci=" . $id);
    } else if ($typo === 'hbl') {
        header("Location: ../search/shipmentsDetails.php?hbl=" . $id);
    } else if ($typo === 'route_id') {
        header("Location: ../pdf/exportPdf.php?id=" . $id);
    }

?>
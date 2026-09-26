<?php

    session_start();

    // Validar y obtener el tipo
    $typo = isset($_GET['type']) ? $_GET['type'] : '';
    $id = isset($_GET['id']) ? $_GET['id'] : '';

    if ($typo == 'ci') {
        header("Location: ../search/details.php?ci=" . $id);
    } else if ($typo == 'hbl') {
        header("Location: ../search/shipmentsDetails.php?hbl=" . $id);
    } else if ($typo == 'route_id') {
        header("Location: ../pdf/exportPdf.php?id=" . $id);
    }

?>
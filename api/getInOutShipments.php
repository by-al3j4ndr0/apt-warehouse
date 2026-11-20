<?php
require_once __DIR__ . '/auth.php';
requireApiLogin(true);
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = (int) ($_GET['per_page'] ?? 25);
    $perPage = min(100, max(10, $perPage));
    $search = trim((string) ($_GET['search'] ?? ''));
    if (mb_strlen($search) > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'El término de búsqueda es demasiado largo.']);
        exit;
    }

    $where = "s.status = 'at_customs'";
    $searchTerm = '%' . $search . '%';
    if ($search !== '') {
        $where .= " AND (s.hbl LIKE ? OR s.manifest LIKE ? OR c.name LIKE ? OR c.city LIKE ? OR c.state LIKE ?)";
    }

    if ($search !== '') {
        $countStmt = $conn->prepare("SELECT COUNT(*) AS total
            FROM shipments s LEFT JOIN clients c ON c.ci = s.ci WHERE $where");
        $countStmt->bind_param('sssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    } else {
        $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM shipments s WHERE $where");
    }
    $countStmt->execute();
    $total = (int) $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT s.hbl, s.manifest, s.origen, c.name AS recipient,
                   c.city AS municipality, c.state AS province
            FROM shipments s
            LEFT JOIN clients c ON c.ci = s.ci
            WHERE $where
            ORDER BY s.manifest, s.hbl
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if ($search !== '') {
        $stmt->bind_param('sssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $perPage, $offset);
    } else {
        $stmt->bind_param('ii', $perPage, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $shipments = [];
    while ($row = $result->fetch_assoc()) {
        $shipments[] = [
            'code' => (string) $row['hbl'],
            'manifest' => (string) $row['manifest'],
            'recipient' => (string) ($row['recipient'] ?? ''),
            'municipality' => (string) ($row['municipality'] ?? ''),
            'province' => (string) ($row['province'] ?? '')
        ];
    }
    $stmt->close();

    $summaryStmt = $conn->prepare("SELECT
        (SELECT COUNT(*) FROM shipments WHERE status = 'at_customs') AS pending,
        (SELECT COUNT(*) FROM scanner_events
          WHERE result = 'FOUND' AND scanned_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)) AS scanned_last_week");
    $summaryStmt->execute();
    $summary = $summaryStmt->get_result()->fetch_assoc();
    $summaryStmt->close();

    echo json_encode([
        'ok' => true,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'summary' => [
            'pending' => (int) $summary['pending'],
            'scanned_last_week' => (int) $summary['scanned_last_week']
        ],
        'shipments' => $shipments
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('In/out shipments list error: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar la mercancía pendiente de entrada.']);
}
?>
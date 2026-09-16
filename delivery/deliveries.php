<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../resources/css/deliveries.css">
    <link rel="stylesheet" href="../resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <script src="../resources/js/delivery.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutas</title>
</head>

<body>
    <?php include '../header.php'; ?>

    <div class="container p-3 d-flex flex-column align-items-center">
        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="toast align-items-center text-white bg-danger border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php
                            echo htmlspecialchars($_SESSION['error_message']);
                            unset($_SESSION['error_message']);
                        ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="toast align-items-center text-white bg-success border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php
                            echo htmlspecialchars($_SESSION['success_message']);
                            unset($_SESSION['success_message']);
                        ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php
        include '../api/db_connect.php';
        include '../api/getInfoById.php';

        // === Caché de origen y driver (evita N+1) ===
        $origen_cache = [];
        $driver_cache = [];

        $deliveries_stmt = $conn->query(
            "SELECT * FROM `delivery` WHERE `id` > '760' ORDER BY `id` DESC"
        );

        $deliveries = [];
        while ($delivery = $deliveries_stmt->fetch_assoc()) {
            $deliveries[] = $delivery;
        }

        foreach ($deliveries as $d) {
            $oid = $d['origen'] ?? null;
            $did = $d['driver'] ?? null;

            if ($oid && !isset($origen_cache[$oid])) {
                $origen_cache[$oid] = getInfoById($oid, 'origen')['name'] ?? '—';
            }
            if ($did && !isset($driver_cache[$did])) {
                $driver_cache[$did] = getInfoById($did, 'driver')['name'] ?? '—';
            }
        }

        function statusBadge($status) {
            switch ($status) {
                case 'finished':
                    return '<span class="status-finished">TERMINADA</span>';
                case 'delivering':
                    return '<span class="status-delivering">ENTREGANDO</span>';
                case 'draft':
                    return '<span class="status-draft">BORRADOR</span>';
                default:
                    return '<span>' . htmlspecialchars($status) . '</span>';
            }
        }
    ?>

    <div class="p-3 routes-app">
        <div class="search-bar">
            <div class="search-group">
                <label for="routes-search-input">Buscar ruta</label>
                <input type="text"
                    id="routes-search-input"
                    placeholder="Buscar por ID, nombre, estado..."
                    autocomplete="off" />
            </div>

            <button type="button" class="btn-clear" id="routes-clear">Limpiar</button>

            <form method="get" action="./manageDelivery.php" style="margin:0;">
                <input type="hidden" name="model" value="new_delivery">
                <button type="submit" class="btn-add-route">
                    <i class="fa fa-plus"></i> Adicionar Ruta
                </button>
            </form>
        </div>

        <div class="toolbar">
            <div class="count" id="routes-count"></div>
            <div></div>
        </div>

        <div class="table-wrap">
            <table id="routes-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Origen</th>
                        <th>Chofer</th>
                        <th>Arancel</th>
                        <th>Paquetes</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="routes-table-body">
                    <?php if (empty($deliveries)): ?>
                        <tr class="no-rows">
                            <td colspan="8">No hay rutas registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deliveries as $delivery): ?>
                            <?php
                                $status    = $delivery['status'] ?? '';
                                $canEdit   = in_array($status, ['delivering', 'draft'], true);
                                $canDelete = $status === 'draft';

                                $origenName = $origen_cache[$delivery['origen']] ?? '—';
                                $driverName = $driver_cache[$delivery['driver']] ?? '—';
                            ?>
                            <tr
                                data-id="<?php echo (int) $delivery['id'] ?>"
                                data-name="<?php echo htmlspecialchars($delivery['name']) ?>"
                                data-status="<?php echo htmlspecialchars($status) ?>"
                                data-origen="<?php echo htmlspecialchars($origenName) ?>"
                                data-driver="<?php echo htmlspecialchars($driverName) ?>"
                            >
                                <td><?php echo (int) $delivery['id'] ?></td>
                                <td><?php echo htmlspecialchars($delivery['name']) ?></td>
                                <td><?php echo statusBadge($status) ?></td>
                                <td><?php echo htmlspecialchars($origenName) ?></td>
                                <td><?php echo htmlspecialchars($driverName) ?></td>
                                <td>$<?php echo htmlspecialchars($delivery['total_tariff']) ?></td>
                                <td><?php echo (int) $delivery['total_shipments'] ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="./manageDelivery.php?model=update_delivery&id=<?php echo (int) $delivery['id'] ?>"
                                           class="<?php echo $canEdit ? '' : 'disabled' ?>"
                                           title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <a target="_blank"
                                           href="../pdf/exportPdf.php?id=<?php echo (int) $delivery['id'] ?>"
                                           title="Imprimir">
                                            <i class="fa fa-print"></i>
                                        </a>
                                        <a href="../api/deleteDelivery.php?id=<?php echo (int) $delivery['id'] ?>"
                                           class="<?php echo $canDelete ? '' : 'disabled' ?>"
                                           title="Eliminar"
                                           onclick="return confirm('¿Eliminar esta ruta?');">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                        <a href="../api/exportRouteInfo.php?id=<?php echo (int) $delivery['id'] ?>"
                                           title="Exportar">
                                            <i class="fa fa-external-link"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="routes-pagination"></div>
    </div>
</body>
</html>
<?php
    require_once __DIR__ . '/../api/auth.php';
    requireLogin(true);

    include '../api/getDeliveryInfo.php';

    global $drivers_stmt, $vehicule_stmt;

    getGlobalInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../resources/css/custom.css">
    <link rel="stylesheet" href="../resources/css/manageDriversVehicules.css">
    <link rel="stylesheet" href="../resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choferes y Vehiculos</title>
</head>
<body>
    <?php include '../header.php' ?>

    <!-- ============ CHOFERES ============ -->
    <div class="container p-3 d-flex">
        <h2 class="p-4 mt-5">Choferes</h2>
    </div>
    <div class="container">
        <div class="row">
            <table id="drivers-table">
                <tbody id="drivers-table-body">
                    <tr>
                        <td>
                            <select id="driver" class="form-control" name="driver">
                                <option value="">Seleccione...</option>
                                <?php if (isset($drivers_stmt) && $drivers_stmt): ?>
                                    <?php while ($drivers = $drivers_stmt->fetch_assoc()): ?>
                                        <option
                                            value="<?php echo htmlspecialchars($drivers['id']); ?>"
                                            data-name="<?php echo htmlspecialchars($drivers['name']); ?>"
                                            data-ci="<?php echo htmlspecialchars($drivers['ci']); ?>">
                                            <?php echo htmlspecialchars($drivers['name']) . ' - CI: ' . htmlspecialchars($drivers['ci']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-primary" id="addDriver" title="Agregar chofer"><i class="fa fa-add"></i></button>
                                <button class="btn btn-primary" id="editDriver" title="Editar chofer"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-primary" id="removeDriver" title="Eliminar chofer"><i class="fa fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============ VEHICULOS ============ -->
    <div class="container p-3 d-flex">
        <h2 class="p-4 mt-5">Vehiculos</h2>
    </div>
    <div class="container">
        <div class="row">
            <table id="vehicules-table">
                <tbody id="vehicules-table-body">
                    <tr>
                        <td>
                            <select id="vehicule" class="form-control" name="vehicule">
                                <option value="">Seleccione...</option>
                                <?php if (isset($vehicule_stmt) && $vehicule_stmt): ?>
                                    <?php while ($vehicule = $vehicule_stmt->fetch_assoc()): ?>
                                        <option
                                            value="<?php echo htmlspecialchars($vehicule['id']); ?>"
                                            data-matriculate="<?php echo htmlspecialchars($vehicule['matriculate']); ?>">
                                            <?php echo htmlspecialchars($vehicule['matriculate']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-primary" id="addVehicule" title="Agregar vehículo"><i class="fa fa-add"></i></button>
                                <button class="btn btn-primary" id="editVehicule" title="Editar vehículo"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-primary" id="removeVehicule" title="Eliminar vehículo"><i class="fa fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============ MODAL ============ -->
    <div id="modal" class="modal-overlay">
        <div class="modal-box" id="modal-box">
            <!-- Aquí se genera el contenido del modal dinámicamente -->
        </div>
    </div>

    <script src="../resources/js/manageDriversVehicules.js"></script>
</body>
</html>
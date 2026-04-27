<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Validar y obtener el modelo
$model = isset($_GET['model']) ? $_GET['model'] : '';

// Inicializar variables globales
$origen_stmt = null;
$drivers_stmt = null;
$vehicule_stmt = null;

// Ejecutar según el modelo
switch ($model) {
    case 'new_delivery':
        newDelivery();
        break;
    case 'update_delivery':
        if(isset($_GET['id'])) {
            updateDelivery($_GET['id']);
        }
        break;
    default:
        // Si no hay modelo válido, mostrar error o redirigir
        $_SESSION['error_message'] = "Modelo no especificado o inválido";
        break;
}

// Funciones
function get_global_info() {
    include '../api/db_connect.php';
    
    global $origen_stmt, $drivers_stmt, $vehicule_stmt;
    
    try {
        $origen_stmt = $conn->query("SELECT * FROM `origen`");
        $drivers_stmt = $conn->query("SELECT * FROM `drivers`");
        $vehicule_stmt = $conn->query("SELECT * FROM `vehicules`");
        
        if (!$origen_stmt || !$drivers_stmt || !$vehicule_stmt) {
            throw new Exception("Error al cargar los datos");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        error_log("Error en get_global_info: " . $e->getMessage());
    }
}

function newDelivery() {

    global $formActionHref;
    global $deliveryId, $deliveryName, $origenId, $driverId, $vehiculeId;
    global $page_tittle, $draft_state, $finished_state, $origen_state, $input_delivering_state;

    get_global_info();
    $page_tittle = "Nueva Ruta";
    $formActionHref = "../api/createDelivery.php";
    $deliveryId = "0";
    $draft_state = "checked";
    $finished_state = "disabled";
    $deliveryName = "";
    $origenId = 0;
    $driverId = 0;
    $vehiculeId = 0;
    $origen_state = "";
    $input_delivering_state = '';
}

function updateDelivery(int $delivery_id) {

    include '../api/getDeliveryInfo.php';
    include '../api/getInfoById.php';

    global $page_tittle;
    global $formActionHref;
    global $draft_state, $finished_state, $origen_state, $delivering_state, $input_delivering_state;
    global $deliveryId, $deliveryName, $origenId, $driverId, $vehiculeId;

    $page_tittle = "Modificar Ruta";
    $formActionHref = "../api/updateDelivery.php";
    $delivery_data = getDeliveryInfo($delivery_id);
    $deliveryId = $delivery_id;  

    if($delivery_data['status'] == 'draft') {
        get_global_info();
        $draft_state = "checked";
        $finished_state = "disabled";
        $origen_state = 'readonly="true"';
        $input_delivering_state = '';
        $deliveryName = $delivery_data['name'];
        $origenId = $delivery_data['origen'];
        $driverId = $delivery_data['driver'];
        $vehiculeId = $delivery_data['vehicule'];
    } else if($delivery_data['status'] == 'delivering') {
        get_global_info();
        $draft_state = "disabled";
        $delivering_state = "checked";
        $origen_state = 'readonly="true"';
        $input_delivering_state = 'readonly="true"';
        $deliveryName = $delivery_data['name'];
        $origenId = $delivery_data['origen'];
        $driverId = $delivery_data['driver'];
        $vehiculeId = $delivery_data['vehicule'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="stylesheet" href="../resources/css/custom.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_tittle ?></title>
    <style>
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
        .form-control:focus {
            box-shadow: none;
        }
        .btn-group .btn-check:checked + .btn {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include '../header.php'; ?>

    <div class="p-5 flex-column align-items-center">
        <!-- Mensajes de error/success -->
        <?php if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): ?>
            <div class="toast align-items-center text-white bg-danger border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php 
                            echo htmlspecialchars($_SESSION['error_message']);
                            unset($_SESSION['error_message']); // Limpiar después de mostrar
                        ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
            <div class="toast align-items-center text-white bg-success border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php 
                            echo htmlspecialchars($_SESSION['success_message']);
                            unset($_SESSION['success_message']);
                        ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo $formActionHref ?>" method="post">
            <input type="hidden" id="deliveryId" name="deliveryId" value="<?php echo $deliveryId ?>">
            <div class="form-control">
                <div class="row p-2">
                    <div class="btn-group" role="group">
                        <!-- Corregido: IDs únicos y names correctos -->
                        <input type="radio" class="btn-check" name="status" id="status_draft" value="draft" autocomplete="off" <?php echo $draft_state ?>>
                        <label class="btn btn-outline-warning" for="status_draft">Borrador</label>

                        <input type="radio" class="btn-check" name="status" id="status_delivering" value="delivering" autocomplete="off" <?php echo $delivering_state ?>>
                        <label class="btn btn-outline-primary" for="status_delivering">Entregando</label>

                        <input type="radio" class="btn-check" name="status" id="status_finished" value="finished" autocomplete="off" <?php echo $finished_state ?>>
                        <label class="btn btn-outline-success" for="status_finished">Terminada</label>
                    </div>
                </div>
                
                <div class="row p-2">
                    <div class="col">
                        <label for="name" class="form-label">Nombre</label>  
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $deliveryName ?>" placeholder="(XXX-00) 00/00" autocomplete="off" required <?php echo $input_delivering_state ?>>
                    </div>
                    <div class="col">
                        <label for="origen" class="form-label">Origen</label>
                        <select id="origen" class="form-control" name="origen" <?php echo $origen_state ?> required>
                            <option name="default_origen" value="">Seleccione...</option>
                            <?php if (isset($origen_stmt) && $origen_stmt): ?>
                                <?php while($origen = $origen_stmt->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($origen['id']); ?>"
                                    <?php if($origen['id'] == $origenId) {
                                            echo "selected";
                                        }
                                    ?>>
                                        <?php echo htmlspecialchars($origen['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row p-2">
                    <div class="col">
                        <label for="driver" class="form-label">Chofer</label> 
                        <select id="driver" class="form-control" name="driver" required <?php echo $input_delivering_state ?>>
                            <option name="default_driver" value="">Seleccione...</option>
                            <?php if (isset($drivers_stmt) && $drivers_stmt): ?>
                                <?php while($drivers = $drivers_stmt->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($drivers['id']); ?>"
                                    <?php if($drivers['id'] == $driverId) {
                                            echo "selected";
                                        }
                                    ?>>
                                        <?php echo htmlspecialchars($drivers['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label for="vehicule" class="form-label">Vehículo</label> 
                        <select id="vehicule" class="form-control" name="vehicule" required <?php echo $input_delivering_state ?>>
                            <option name="default_vehicule" value="">Seleccione...</option>
                            <?php if (isset($vehicule_stmt) && $vehicule_stmt): ?>
                                <?php while($vehicule = $vehicule_stmt->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($vehicule['id']); ?>"
                                    <?php if($vehicule['id'] == $vehiculeId) {
                                            echo "selected";
                                        }
                                    ?>>
                                        <?php echo htmlspecialchars($vehicule['matriculate']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-flex flex-column align-items-left">
                <div id="tableDiv">
                    <!-- Aquí se cargará dinámicamente la tabla de productos/entregas -->
                </div>
                <div class="container p-5 d-flex flex-column align-items-right">
                    <button class="btn btn-primary mb-2" type="submit" style="font-weight:bolder;color:white;">
                        Guardar Ruta
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <script src="../resources/js/custom.js"></script>
    <script src="../resources/js/delivery.js"></script>
    
    <script>
        // Auto-cerrar toasts después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 5000);
            });
            
            // Validación del formulario antes de enviar
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const name = document.getElementById('name').value;
                    const origen = document.getElementById('origen').value;
                    const driver = document.getElementById('driver').value;
                    const vehicule = document.getElementById('vehicule').value;
                    
                    if (!name || !origen || !driver || !vehicule) {
                        e.preventDefault();
                        alert('Por favor, complete todos los campos obligatorios');
                        return false;
                    }
                    
                    if (name && !/^\([A-Z]{3}-\d{2}\) \d{2}\/\d{2}$/.test(name)) {
                        e.preventDefault();
                        alert('El formato del nombre debe ser (XXX-00) 00/00');
                        return false;
                    }
                });
            }
        });
    </script>
</body>

</html>
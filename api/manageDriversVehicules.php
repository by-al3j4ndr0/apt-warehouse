<?php
require_once __DIR__ . '/auth.php';
requireLogin(true);
header('Content-Type: application/json');

// Ajusta según tu conexión existente (mysqli)
require_once __DIR__ . '/db_connect.php'; // o tu archivo de conexión

$action = $_POST['action'] ?? '';

function respond($success, $message = '') {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

try {
    switch ($action) {

        // ---------- CHOFERES ----------
        case 'addDriver':
            $name = trim($_POST['name'] ?? '');
            $ci   = trim($_POST['ci']   ?? '');
            if ($name === '' || $ci === '') respond(false, 'Nombre y CI son obligatorios');
            $stmt = $conn->prepare("INSERT INTO `drivers` (`name`, `ci`) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $ci);
            $stmt->execute();
            respond(true);

        case 'editDriver':
            $id   = intval($_POST['id']   ?? 0);
            $name = trim($_POST['name'] ?? '');
            $ci   = trim($_POST['ci']   ?? '');
            if (!$id || $name === '' || $ci === '') respond(false, 'Datos inválidos');
            $stmt = $conn->prepare("UPDATE `drivers` SET `name` = ?, `ci` = ? WHERE `id` = ?");
            $stmt->bind_param('ssi', $name, $ci, $id);
            $stmt->execute();
            respond(true);

        case 'removeDriver':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) respond(false, 'ID inválido');
            $stmt = $conn->prepare("DELETE FROM `drivers` WHERE `id` = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            respond(true);

        // ---------- VEHICULOS ----------
        case 'addVehicule':
            $mat = trim($_POST['matriculate'] ?? '');
            if ($mat === '') respond(false, 'La matrícula es obligatoria');
            $stmt = $conn->prepare("INSERT INTO `vehicules` (`matriculate`) VALUES (?)");
            $stmt->bind_param('s', $mat);
            $stmt->execute();
            respond(true);

        case 'editVehicule':
            $id  = intval($_POST['id'] ?? 0);
            $mat = trim($_POST['matriculate'] ?? '');
            if (!$id || $mat === '') respond(false, 'Datos inválidos');
            $stmt = $conn->prepare("UPDATE `vehicules` SET `matriculate` = ? WHERE `id` = ?");
            $stmt->bind_param('si', $mat, $id);
            $stmt->execute();
            respond(true);

        case 'removeVehicule':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) respond(false, 'ID inválido');
            $stmt = $conn->prepare("DELETE FROM `vehicules` WHERE `id` = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            respond(true);

        default:
            respond(false, 'Acción no reconocida');
    }
} catch (Throwable $e) {
    respond(false, $e->getMessage());
}
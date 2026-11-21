<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireApiAdmin();
requireCsrf();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

function userResponse(bool $success, string $message, int $status = 200): void {
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    userResponse(false, 'Método no permitido.', 405);
}

$action = (string) ($_POST['action'] ?? '');
$actorUsername = (string) ($_SESSION['username'] ?? '');

function readUserRole(string $role): array {
    $roles = [
        'visitor' => [0, 0],
        'operator' => [1, 0],
        'admin' => [1, 1],
    ];
    if (!isset($roles[$role])) {
        userResponse(false, 'Perfil de usuario no válido.', 422);
    }
    return $roles[$role];
}

function validateOrigin(mysqli $conn, int $originId): bool {
    $stmt = $conn->prepare("SELECT 1 FROM `origen` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param('i', $originId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    return $exists;
}

/** Lock active administrator rows so concurrent requests cannot remove the last one. */
function activeAdminCount(mysqli $conn): int {
    $result = $conn->query(
        "SELECT `id` FROM `auth_user`
         WHERE `is_active` = 1 AND `is_staff` = 1 AND `is_superuser` = 1
         FOR UPDATE"
    );
    return $result->num_rows;
}

try {
    if ($action === 'create') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $originId = filter_var($_POST['origen'] ?? null, FILTER_VALIDATE_INT);
        [$staff, $superuser] = readUserRole((string) ($_POST['role'] ?? ''));

        if ($username === '' || mb_strlen($username) > 150 ||
            $firstName === '' || mb_strlen($firstName) > 150 ||
            $lastName === '' || mb_strlen($lastName) > 150 ||
            mb_strlen($email) > 254 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) ||
            strlen($password) < 8 || $originId === false || (int) $originId < 1) {
            userResponse(false, 'Revisa los campos obligatorios. La contraseña debe tener al menos 8 caracteres.', 422);
        }
        if (!validateOrigin($conn, (int) $originId)) {
            userResponse(false, 'El origen seleccionado no existe.', 422);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO `auth_user`
             (`password`, `is_superuser`, `username`, `first_name`, `last_name`,
              `email`, `is_staff`, `is_active`, `date_joined`, `origen`)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(6), ?)"
        );
        $stmt->bind_param('sissssii', $hash, $superuser, $username, $firstName, $lastName, $email, $staff, $originId);
        $stmt->execute();
        $stmt->close();
        userResponse(true, 'Usuario creado correctamente.');
    }

    if ($action === 'update') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || (int) $id < 1) {
            userResponse(false, 'Identificador de usuario no válido.', 422);
        }

        $conn->begin_transaction();
        $stmt = $conn->prepare(
            "SELECT `username`, `is_active`, `is_staff`, `is_superuser`
             FROM `auth_user` WHERE `id` = ? FOR UPDATE"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$target) {
            $conn->rollback();
            userResponse(false, 'El usuario no existe.', 404);
        }
        if (hash_equals($actorUsername, (string) $target['username'])) {
            $conn->rollback();
            userResponse(false, 'No puedes modificar tu propia cuenta desde este módulo.', 403);
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $originId = filter_var($_POST['origen'] ?? null, FILTER_VALIDATE_INT);
        [$staff, $superuser] = readUserRole((string) ($_POST['role'] ?? ''));

        if ($firstName === '' || mb_strlen($firstName) > 150 ||
            $lastName === '' || mb_strlen($lastName) > 150 ||
            mb_strlen($email) > 254 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) ||
            $originId === false || (int) $originId < 1 ||
            ($password !== '' && strlen($password) < 8)) {
            userResponse(false, 'Revisa los campos. Si cambias la contraseña, debe tener al menos 8 caracteres.', 422);
        }
        if (!validateOrigin($conn, (int) $originId)) {
            userResponse(false, 'El origen seleccionado no existe.', 422);
        }

        $wasActiveAdmin = (int) $target['is_active'] === 1
            && (int) $target['is_staff'] === 1
            && (int) $target['is_superuser'] === 1;
        $willRemainActiveAdmin = (int) $target['is_active'] === 1 && $staff === 1 && $superuser === 1;

        if ($wasActiveAdmin && !$willRemainActiveAdmin && activeAdminCount($conn) <= 1) {
            $conn->rollback();
            userResponse(false, 'No se puede retirar el perfil de administrador al último administrador activo.', 409);
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare(
                "UPDATE `auth_user` SET `first_name` = ?, `last_name` = ?, `email` = ?,
                 `origen` = ?, `is_staff` = ?, `is_superuser` = ?, `password` = ? WHERE `id` = ?"
            );
            $update->bind_param('sssiiisi', $firstName, $lastName, $email, $originId, $staff, $superuser, $hash, $id);
        } else {
            $update = $conn->prepare(
                "UPDATE `auth_user` SET `first_name` = ?, `last_name` = ?, `email` = ?,
                 `origen` = ?, `is_staff` = ?, `is_superuser` = ? WHERE `id` = ?"
            );
            $update->bind_param('sssiiii', $firstName, $lastName, $email, $originId, $staff, $superuser, $id);
        }
        $update->execute();
        $update->close();
        $conn->commit();
        userResponse(true, 'Usuario actualizado correctamente.');
    }

    if ($action === 'deactivate') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || (int) $id < 1) {
            userResponse(false, 'Identificador de usuario no válido.', 422);
        }

        $conn->begin_transaction();
        $stmt = $conn->prepare(
            "SELECT `username`, `is_active`, `is_staff`, `is_superuser`
             FROM `auth_user` WHERE `id` = ? FOR UPDATE"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$target) {
            $conn->rollback();
            userResponse(false, 'El usuario no existe.', 404);
        }
        if (hash_equals($actorUsername, (string) $target['username'])) {
            $conn->rollback();
            userResponse(false, 'No puedes desactivar tu propia cuenta.', 403);
        }

        $isActiveAdmin = (int) $target['is_active'] === 1
            && (int) $target['is_staff'] === 1
            && (int) $target['is_superuser'] === 1;
        if ($isActiveAdmin && activeAdminCount($conn) <= 1) {
            $conn->rollback();
            userResponse(false, 'No se puede desactivar al último administrador activo.', 409);
        }

        $update = $conn->prepare("UPDATE `auth_user` SET `is_active` = 0 WHERE `id` = ?");
        $update->bind_param('i', $id);
        $update->execute();
        $update->close();
        $conn->commit();
        userResponse(true, 'Usuario desactivado. El registro se conservó en la base de datos.');
    }

    userResponse(false, 'Acción no reconocida.', 400);
} catch (mysqli_sql_exception $e) {
    if ($conn->errno === 0) {
        // No-op: the connection remains available; rollback is safe below.
    }
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('User management database error: ' . $e->getMessage());
    if ((int) $e->getCode() === 1062) {
        userResponse(false, 'Ya existe una cuenta con ese nombre de usuario.', 409);
    }
    userResponse(false, 'No se pudo completar la operación en la base de datos.', 500);
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('User management error: ' . $e->getMessage());
    userResponse(false, 'Ocurrió un error inesperado.', 500);
}
?>
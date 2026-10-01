<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

requireCsrf();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Usuario y contraseña son obligatorios.';
    header('Location: ../login.php');
    exit();
}

$stmt = $conn->prepare("SELECT password, first_name, last_name, is_staff, origen FROM auth_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

$valid = false;
if ($stmt->num_rows === 1) {
    $stmt->bind_result($db_password, $firstname, $lastname, $staff, $user_origen);
    $stmt->fetch();

    // Prefer PHP's password hashing API. Keep legacy PBKDF2 verification only
    // to support existing accounts during a one-time migration.
    if (password_get_info($db_password)['algo'] !== 0) {
        $valid = password_verify($password, $db_password);
    } else {
        $pieces = explode("$", $db_password);
        if (count($pieces) === 4 && ctype_digit($pieces[1])) {
            $iterations = (int) $pieces[1];
            $salt = $pieces[2];
            $old_hash = $pieces[3];
            $hash = base64_encode(hash_pbkdf2("SHA256", $password, $salt, $iterations, 0, true));
            $valid = hash_equals($old_hash, $hash);
        }
    }
}

$stmt->close();

if ($valid && password_get_info($db_password)['algo'] === 0) {
    // Upgrade legacy credentials after a successful login.
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $upgradeStmt = $conn->prepare("UPDATE auth_user SET password = ? WHERE username = ?");
    $upgradeStmt->bind_param("ss", $newHash, $username);
    $upgradeStmt->execute();
    $upgradeStmt->close();
}

if (!$valid) {
    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
    header('Location: ../login.php');
    exit();
}

session_regenerate_id(true);
$_SESSION['username'] = $username;
$_SESSION['first_name'] = $firstname;
$_SESSION['last_name'] = $lastname;
$_SESSION['is_staff'] = (int) $staff;
$_SESSION['login_time_stamp'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ((int) $staff === 1) {
    header('Location: ../index.php');
} else {
    $_SESSION['user_origen'] = $user_origen;
    header('Location: ../visitors/visitors.php');
}
exit();
?>
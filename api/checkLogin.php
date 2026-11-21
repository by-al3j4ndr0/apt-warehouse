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

$stmt = $conn->prepare("SELECT `password`, `first_name`, `last_name`, `is_active`, `is_staff`, `is_superuser`, `origen` FROM `auth_user` WHERE `username` = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

$valid = false;
$db_password = null;

if ($stmt->num_rows === 1) {
    $stmt->bind_result($db_password, $firstname, $lastname, $active, $staff, $superuser, $user_origen);
    $stmt->fetch();

    // First try PHP password hashes (bcrypt/Argon). If this is a legacy
    // PBKDF2 value, fall back to the format already used by this application.
    $valid = password_verify($password, $db_password);

    if (!$valid) {
        $pieces = explode("$", $db_password);
        if (count($pieces) === 4 && ctype_digit($pieces[1])) {
            $iterations = (int) $pieces[1];
            $salt = $pieces[2];
            $oldHash = $pieces[3];
            $candidate = base64_encode(hash_pbkdf2("SHA256", $password, $salt, $iterations, 0, true));
            $valid = hash_equals($oldHash, $candidate);
        }
    }
}

$stmt->close();

if ($valid && ((int) $active !== 1 || !in_array([(int) $staff, (int) $superuser], [[0, 0], [1, 0], [1, 1]], true))) {
    $valid = false;
}

if (!$valid) {
    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
    header('Location: ../login.php');
    exit();
}

// Upgrade legacy hashes after a successful login. password_needs_rehash()
// also keeps PHP-generated hashes aligned with the current PASSWORD_DEFAULT.
if (password_needs_rehash($db_password, PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $upgradeStmt = $conn->prepare("UPDATE `auth_user` SET `password` = ? WHERE `username` = ?");
    $upgradeStmt->bind_param("ss", $newHash, $username);
    $upgradeStmt->execute();
    $upgradeStmt->close();
}

session_regenerate_id(true);
$_SESSION['username'] = $username;
$_SESSION['first_name'] = $firstname;
$_SESSION['last_name'] = $lastname;
$_SESSION['is_active'] = (int) $active;
$_SESSION['is_staff'] = (int) $staff;
$_SESSION['is_superuser'] = (int) $superuser;
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
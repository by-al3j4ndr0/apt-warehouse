<?php
require_once __DIR__ . '/api/auth.php';
startSecureSession();
destroySession();
header('Location: login.php');
exit();
?>
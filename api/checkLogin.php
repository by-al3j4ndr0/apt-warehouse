<?php

include '../api/db_connect.php';

$message = "";
$toastClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute
    $stmt = $conn->prepare("SELECT `password`, `first_name`, `last_name` FROM auth_user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_password, $firstname, $lastname);
        $stmt->fetch();

        $pieces = explode("$", $db_password);

        $iterations = $pieces[1];
        $salt = $pieces[2];
        $old_hash = $pieces[3];

        $hash = hash_pbkdf2("SHA256", $password, $salt, $iterations, 0, true);
        $hash = base64_encode($hash);

        if ($hash == $old_hash) {
            $message = "Login successful";
            $toastClass = "bg-success";
            // Start the session and redirect to the dashboard or home page
            session_start();
            $_SESSION['username'] = $username;
            $_SESSION['first_name'] = $firstname;
            $_SESSION['last_name'] = $lastname;
            header("Location: ../index.php");
            exit();
        }
        else {
            header("Location: ../login.php");
            $message = "Incorrect password";
            $toastClass = "bg-danger";
        }
    } else {
        header("Location: ../login.php");
        $message = "Username not found";
        $toastClass = "bg-warning";
    }

    $stmt->close();
    $conn->close();
}
?>
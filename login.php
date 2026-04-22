<?php
include './api/db_connect.php';

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
            header("Location: index.php");
            exit();
        }
        else {
            $message = "Incorrect password";
            $toastClass = "bg-danger";
        }
    } else {
        $message = "Username not found";
        $toastClass = "bg-warning";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="resources/css/dashboard.css">
    <link rel="stylesheet" href="resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="resources/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesion</title>
</head>

<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">
        <?php if ($message): ?>
            <div class="toast align-items-center text-white 
            <?php echo $toastClass; ?> border-0" role="alert"
                aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $message; ?>
                    </div>
                    <button type="button" class="btn-close
                    btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        <form action="" method="post" class="form-control mt-5 p-4"
            style="height:auto; width:380px; box-shadow: rgba(60, 64, 67, 0.3) 
            0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
            <div class="row">
                <i class="fa fa-user-circle-o fa-3x mt-1 mb-2"
          style="text-align: center; color: green;"></i>
                <h5 class="text-center p-4" 
          style="font-weight: 700;">Iniciar sesion</h5>
            </div>
            <div class="col-mb-3">
                <label for="username"><i 
                  class="fa fa-user-lock"></i> Nombre de usuario</label>
                <input type="text" name="username" id="username"
                  class="form-control" autocomple="off" required>
            </div>
            <div class="col mb-3 mt-3">
                <label for="password"><i
                  class="fa fa-lock"></i> Contrase&ntilde;a</label>
                <input type="password" name="password" id="password" 
                  class="form-control" required>
            </div>
            <div class="col mb-3 mt-3">
                <button type="submit" 
                  class="btn btn-success bg-success" style="font-weight: 600;">Iniciar Sesion</button>
            </div>
        </form>
    </div>
    <script>
        var toastElList = [].slice.call(document.querySelectorAll('.toast'))
        var toastList = toastElList.map(function (toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 3000 });
        });
        toastList.forEach(toast => toast.show());
    </script>
</body>

</html>
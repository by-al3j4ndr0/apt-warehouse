<?php global $message, $toastClass; ?>
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
        <form action="../api/checkLogin.php" method="post" class="form-control mt-5 p-4"
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
            <div class="align-items-center mb-3 mt-3">
                <button type="submit" 
                  class="btn btn-outline-dark" style="font-weight: 600;">Iniciar Sesion</button>
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
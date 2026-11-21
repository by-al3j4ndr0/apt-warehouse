<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/auth.php';
requireAdmin();
require_once __DIR__ . '/../api/db_connect.php';

$search = trim((string) ($_GET['q'] ?? ''));
$origins = $conn->query("SELECT `id`, `name` FROM `origen` ORDER BY `name`");
if (!$origins) {
    http_response_code(500);
    exit('No se pudieron cargar los orígenes.');
}

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare(
        "SELECT u.`id`, u.`username`, u.`first_name`, u.`last_name`, u.`email`,
                u.`is_staff`, u.`is_superuser`, u.`is_active`, u.`origen`, o.`name` AS origin_name
         FROM `auth_user` u
         LEFT JOIN `origen` o ON o.`id` = u.`origen`
         WHERE u.`username` LIKE ? OR u.`first_name` LIKE ? OR u.`last_name` LIKE ? OR u.`email` LIKE ?
         ORDER BY u.`username`"
    );
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query(
        "SELECT u.`id`, u.`username`, u.`first_name`, u.`last_name`, u.`email`,
                u.`is_staff`, u.`is_superuser`, u.`is_active`, u.`origen`, o.`name` AS origin_name
         FROM `auth_user` u
         LEFT JOIN `origen` o ON o.`id` = u.`origen`
         ORDER BY u.`username`"
    );
}
if (!$users) {
    http_response_code(500);
    exit('No se pudieron cargar los usuarios.');
}

function userRoleLabel(array $user): string {
    if ((int) $user['is_staff'] === 1 && (int) $user['is_superuser'] === 1) return 'Administrador';
    if ((int) $user['is_staff'] === 1) return 'Operador / consulta';
    return 'Visitante';
}
function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../resources/css/custom.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <title>Gestión de usuarios</title>
    <style>
        .users-wrap{padding:2rem 0}.users-toolbar{display:flex;gap:1rem;align-items:center;justify-content:space-between;flex-wrap:wrap;margin:1.5rem 0}
        .users-table-wrap{overflow-x:auto}.users-table{width:100%;border-collapse:collapse;background:#fff}
        .users-table th,.users-table td{padding:.7rem;border-bottom:1px solid #dee2e6;text-align:left;vertical-align:middle}
        .users-table th{background:#f5f6f7}.status-active{color:#146c43;font-weight:600}.status-inactive{color:#b02a37;font-weight:600}
        dialog{border:0;border-radius:10px;padding:0;width:min(640px,calc(100% - 2rem));box-shadow:0 1rem 3rem #0003}
        dialog::backdrop{background:#0007}.user-dialog{padding:1.5rem}.user-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
        .user-form-grid .full{grid-column:1/-1}.user-actions{display:flex;gap:.5rem;justify-content:flex-end;margin-top:1.25rem}
        #feedback{min-height:1.5rem}.muted{color:#6c757d}
        @media(max-width:600px){.user-form-grid{grid-template-columns:1fr}.user-form-grid .full{grid-column:auto}}
    </style>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>
<main class="container users-wrap">
    <h1>Gestión de usuarios</h1>
    <p class="muted">Crea y administra visitantes, operadores de consulta y administradores. Todos los perfiles deben tener un origen asignado.</p>

    <div class="users-toolbar">
        <form method="get" class="d-flex gap-2" role="search">
            <input class="form-control" type="search" name="q" value="<?= h($search) ?>" placeholder="Buscar por usuario, nombre o correo" aria-label="Buscar usuarios">
            <button class="btn btn-outline-primary" type="submit">Buscar</button>
            <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="manageUsers.php">Limpiar</a><?php endif; ?>
        </form>
        <button class="btn btn-primary" id="createUser" type="button">Crear usuario</button>
    </div>

    <div id="feedback" role="status" aria-live="polite"></div>
    <div class="users-table-wrap">
        <table class="users-table">
            <thead><tr><th>Usuario</th><th>Nombre</th><th>Correo</th><th>Perfil</th><th>Origen</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php if ($users->num_rows === 0): ?>
                <tr><td colspan="7">No se encontraron usuarios.</td></tr>
            <?php else: ?>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <?php
                    $userData = [
                        'id' => (int) $user['id'],
                        'username' => $user['username'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'origen' => (int) $user['origen'],
                        'role' => ((int) $user['is_staff'] === 1 && (int) $user['is_superuser'] === 1)
                            ? 'admin' : (((int) $user['is_staff'] === 1) ? 'operator' : 'visitor'),
                    ];
                    ?>
                    <tr>
                        <td><?= h($user['username']) ?></td>
                        <td><?= h($user['first_name'] . ' ' . $user['last_name']) ?></td>
                        <td><?= h($user['email']) ?></td>
                        <td><?= h(userRoleLabel($user)) ?></td>
                        <td><?= h($user['origin_name'] ?? 'Origen no encontrado') ?></td>
                        <td class="<?= (int) $user['is_active'] === 1 ? 'status-active' : 'status-inactive' ?>">
                            <?= (int) $user['is_active'] === 1 ? 'Activo' : 'Inactivo' ?>
                        </td>
                        <td>
                            <?php if ($user['username'] !== ($_SESSION['username'] ?? '')): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-user"
                                    data-user="<?= h(json_encode($userData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">Editar</button>
                                <?php if ((int) $user['is_active'] === 1): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger deactivate-user" data-id="<?= (int) $user['id'] ?>" data-username="<?= h($user['username']) ?>">Desactivar</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="muted">Cuenta actual</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<dialog id="userDialog">
    <form id="userForm" class="user-dialog">
        <h2 id="dialogTitle">Crear usuario</h2>
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="action" id="formAction" value="create">
        <input type="hidden" name="id" id="userId">
        <div class="user-form-grid">
            <div class="full">
                <label class="form-label" for="username">Nombre de usuario</label>
                <input class="form-control" id="username" name="username" maxlength="150" required>
                <small class="muted" id="usernameHelp">No se podrá cambiar después de crear la cuenta.</small>
            </div>
            <div><label class="form-label" for="first_name">Nombre</label><input class="form-control" id="first_name" name="first_name" maxlength="150" required></div>
            <div><label class="form-label" for="last_name">Apellidos</label><input class="form-control" id="last_name" name="last_name" maxlength="150" required></div>
            <div class="full"><label class="form-label" for="email">Correo electrónico</label><input class="form-control" type="email" id="email" name="email" maxlength="254"></div>
            <div class="full"><label class="form-label" for="password">Contraseña</label><input class="form-control" type="password" id="password" name="password" minlength="8" autocomplete="new-password"><small class="muted" id="passwordHelp">Mínimo 8 caracteres.</small></div>
            <div><label class="form-label" for="role">Perfil</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="visitor">Visitante</option><option value="operator">Operador / consulta</option><option value="admin">Administrador</option>
                </select>
            </div>
            <div><label class="form-label" for="origen">Origen</label>
                <select class="form-select" id="origen" name="origen" required>
                    <option value="">Seleccione...</option>
                    <?php while ($origin = $origins->fetch_assoc()): ?>
                        <option value="<?= (int) $origin['id'] ?>"><?= h($origin['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="user-actions">
            <button type="button" class="btn btn-outline-secondary" id="cancelDialog">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="saveUser">Guardar</button>
        </div>
    </form>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('userDialog');
    const form = document.getElementById('userForm');
    const feedback = document.getElementById('feedback');
    const setFeedback = (message, isError = false) => {
        feedback.textContent = message;
        feedback.className = isError ? 'text-danger' : 'text-success';
    };
    const resetForm = () => {
        form.reset();
        document.getElementById('userId').value = '';
        document.getElementById('formAction').value = 'create';
        document.getElementById('dialogTitle').textContent = 'Crear usuario';
        document.getElementById('username').readOnly = false;
        document.getElementById('usernameHelp').textContent = 'No se podrá cambiar después de crear la cuenta.';
        document.getElementById('password').required = true;
        document.getElementById('passwordHelp').textContent = 'Mínimo 8 caracteres.';
    };

    document.getElementById('createUser').addEventListener('click', () => {
        resetForm();
        dialog.showModal();
    });
    document.getElementById('cancelDialog').addEventListener('click', () => dialog.close());

    document.querySelectorAll('.edit-user').forEach(button => {
        button.addEventListener('click', () => {
            resetForm();
            const user = JSON.parse(button.dataset.user);
            document.getElementById('formAction').value = 'update';
            document.getElementById('userId').value = user.id;
            document.getElementById('dialogTitle').textContent = 'Editar usuario: ' + user.username;
            document.getElementById('username').value = user.username;
            document.getElementById('username').readOnly = true;
            document.getElementById('usernameHelp').textContent = 'El nombre de usuario no se puede modificar.';
            document.getElementById('first_name').value = user.first_name;
            document.getElementById('last_name').value = user.last_name;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('origen').value = user.origen;
            document.getElementById('password').required = false;
            document.getElementById('passwordHelp').textContent = 'Déjala en blanco para conservar la contraseña actual.';
            dialog.showModal();
        });
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const saveButton = document.getElementById('saveUser');
        saveButton.disabled = true;
        try {
            const response = await fetch('../api/manageUsers.php', {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'No se pudo guardar el usuario.');
            dialog.close();
            setFeedback(result.message);
            window.location.reload();
        } catch (error) {
            setFeedback(error.message, true);
        } finally {
            saveButton.disabled = false;
        }
    });

    document.querySelectorAll('.deactivate-user').forEach(button => {
        button.addEventListener('click', async () => {
            if (!confirm('¿Desactivar la cuenta "' + button.dataset.username + '"? El registro se conservará.')) return;
            const payload = new FormData();
            payload.append('action', 'deactivate');
            payload.append('id', button.dataset.id);
            payload.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
            button.disabled = true;
            try {
                const response = await fetch('../api/manageUsers.php', {
                    method: 'POST', body: payload, headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'No se pudo desactivar la cuenta.');
                window.location.reload();
            } catch (error) {
                setFeedback(error.message, true);
                button.disabled = false;
            }
        });
    });
})();
</script>
</body>
</html>

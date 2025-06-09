<?php
session_start();
include_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$registro_exitoso = false;
$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $existe = $stmt->fetchColumn();

    if ($existe) {
        $mensaje_error = "Este correo ya está registrado.";
    } else {
        $sql = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $email, $password, $rol]);
        $registro_exitoso = true;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if ($registro_exitoso): ?>
    <meta http-equiv="refresh" content="4;url=dashboard.php">
<?php endif; ?>


</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header text-center">
                <h4>Registrar usuario</h4>
            </div>
            <div class="card-body">
                <?php if ($registro_exitoso): ?>
                    <div class="alert alert-success text-center">
   						Usuario registrado con éxito. Redirigiendo al dashboard en <span id="contador">4</span> segundos...
					</div>
					<script>
					    let segundos = 4;
					    const contador = document.getElementById('contador');

					    const intervalo = setInterval(() => {
					        segundos--;
					        contador.textContent = segundos;
					        if (segundos <= 0) {
					            clearInterval(intervalo);
					        }
					    }, 1000);
					</script>
                <?php else: ?>
                    <?php if (!empty($mensaje_error)): ?>
                        <div class="alert alert-danger text-center"><?php echo $mensaje_error; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre completo" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Correo electrónico" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                        </div>
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="usuario">Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">Registrar</button>
                            <a href="dashboard.php" class="btn btn-secondary">Volver</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Realizado por Xulomali_HD (Antonio.R) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>
</html>

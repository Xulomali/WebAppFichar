<?php
session_start();
include_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$registros_por_pagina = 10;
$pagina_actual        = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset               = ($pagina_actual - 1) * $registros_por_pagina;

if ($user_role == 'admin') {
    $sql = "SELECT fichajes.*, usuarios.nombre FROM fichajes
            JOIN usuarios ON fichajes.usuario_id = usuarios.id
            ORDER BY fecha DESC, hora DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit',  $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset,                PDO::PARAM_INT);
    $stmt->execute();
} else {
    $sql = "SELECT * FROM fichajes
            WHERE usuario_id = :user_id
            ORDER BY fecha DESC, hora DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id,               PDO::PARAM_INT);
    $stmt->bindParam(':limit',   $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset',  $offset,               PDO::PARAM_INT);
    $stmt->execute();
}

$fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql_total = "SELECT COUNT(*) FROM fichajes" . ($user_role == 'admin' ? '' : " WHERE usuario_id = :user_id");
$stmt_total = $pdo->prepare($sql_total);
if ($user_role != 'admin') {
    $stmt_total->bindParam(':user_id', $user_id, PDO::PARAM_INT);
}
$stmt_total->execute();
$total_fichajes = $stmt_total->fetchColumn();
$total_paginas  = ceil($total_fichajes / $registros_por_pagina);

$hora_actual            = new DateTime("now", new DateTimeZone('Europe/Madrid'));
$hora_inicio            = new DateTime('07:00', new DateTimeZone('Europe/Madrid'));
$hora_fin               = new DateTime('15:00', new DateTimeZone('Europe/Madrid'));
$hora_actual_formateada = $hora_actual->format('H:i');
$fuera_horario          = false;

if ($hora_actual < $hora_inicio || $hora_actual > $hora_fin) {
    $fuera_horario  = true;
    $mensaje_error = "No puedes fichar fuera del horario permitido (07:00 - 15:00). Hora actual: $hora_actual_formateada";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$fuera_horario) {
    $tipo_fichaje = $_POST['tipo'] ?? null;

    if (!in_array($tipo_fichaje, ['entrada', 'salida'])) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Tipo de fichaje no válido.'
        ]);
        exit();
    }

    $hora_completa = $hora_actual->format('Y-m-d H:i:s');
    $fecha_actual  = $hora_actual->format('Y-m-d');

    // Validar si ya existe un fichaje de entrada o salida para el mismo día
    $sql_validacion = "SELECT COUNT(*) FROM fichajes WHERE usuario_id = :user_id AND fecha = :fecha AND tipo = :tipo";
    $stmt_validacion = $pdo->prepare($sql_validacion);
    $stmt_validacion->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_validacion->bindParam(':fecha', $fecha_actual, PDO::PARAM_STR);
    $stmt_validacion->bindParam(':tipo', $tipo_fichaje, PDO::PARAM_STR);
    $stmt_validacion->execute();
    $fichajes_existentes = $stmt_validacion->fetchColumn();

    if ($fichajes_existentes > 0) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Ya has registrado un fichaje de ' . $tipo_fichaje . ' para hoy.'
        ]);
        exit();
    }

    $sql = "INSERT INTO fichajes (usuario_id, tipo, hora, fecha) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $tipo_fichaje, $hora_completa, $fecha_actual]);

    echo json_encode([
        'success' => true,
        'mensaje' => 'Fichaje realizado correctamente a las ' . $hora_actual->format('H:i') . ' del ' . $fecha_actual
    ]);
    exit();
}
?>

<!-- Realizado por Xulomali_HD (Antonio.R) -->

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
  <h2 class="text-center mb-4">Fichajes</h2>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Fichajes de <?php echo explode(' ', $_SESSION['nombre'])[0]; ?></h3>
    <div class="d-flex flex-row gap-2">
      <?php if ($user_role != 'admin'): ?>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ficharModal">
        Fichar Entrada/Salida
      </button>
      <?php endif; ?>
      <?php if ($user_role == 'admin'): ?>
      <a href="register.php" class="btn btn-success">Registrar Nuevo Usuario</a>
      <?php endif; ?>
      <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
    </div>
  </div>

  <table id="tabla-fichajes" class="table table-striped">
    <thead>
      <tr>
        <?php if ($user_role != 'admin'): ?>
        <th>#</th>
        <?php endif; ?>
        <?php if ($user_role == 'admin'): ?>
        <th>Usuario</th>
        <?php endif; ?>
        <th>Fecha</th>
        <th>Hora</th>
        <th>Tipo</th>
      </tr>
    </thead>
    <tbody id="fichajes-table-body">
      <?php foreach ($fichajes as $f): ?>
      <tr>
        <?php if ($user_role != 'admin'): ?>
        <td><?= $f['id'] ?></td>
        <?php endif; ?>
        <?php if ($user_role == 'admin'): ?>
        <td><?= htmlspecialchars($f['nombre']) ?></td>
        <?php endif; ?>
        <td><?= $f['fecha'] ?></td>
        <td><?= date('H:i', strtotime($f['hora'])) ?></td>
        <td><?= ucfirst($f['tipo']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <nav>
    <ul class="pagination justify-content-center mt-3 mb-5">
      <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
      <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
        <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
      </li>
      <?php endfor; ?>
    </ul>
  </nav>

  <div id="mensaje-resultado" class="alert d-none"></div>
</div>

<!-- Modal -->
<div class="modal fade" id="ficharModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Fichar Entrada/Salida</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <?php if (isset($mensaje_error)): ?>
      <div class="alert alert-danger"><?= $mensaje_error ?></div>
      <?php endif; ?>
      <form id="fichaje-form" method="POST">
        <div class="text-center mb-3">
          <button type="button" name="tipo" value="entrada" class="btn btn-success btn-lg m-2">Fichar Entrada</button>
          <button type="button" name="tipo" value="salida"  class="btn btn-warning btn-lg m-2">Fichar Salida</button>
        </div>
      </form>
    </div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(function(){
  let tabla = $('#tabla-fichajes').DataTable({
    order: [
      <?php if ($user_role == 'admin'): ?>
      [1,'desc'], [2,'desc']
      <?php else: ?>
      [0,'desc'], [2,'desc']
      <?php endif; ?>
    ],
    language: {
      sProcessing:   "Procesando...",
      sLengthMenu:   "Mostrar _MENU_ entradas",
      sZeroRecords:  "No se encontraron resultados",
      sEmptyTable:   "No hay datos disponibles en la tabla",
      sInfo:         "Mostrando _START_ de _END_ (_TOTAL_ entradas)",
      sInfoEmpty:    "Mostrando 0 a 0 de 0 entradas",
      sInfoFiltered: "(filtrado de _MAX_ entradas totales)",
      sSearch:       "Buscar:",
      oPaginate: {
        sFirst:    "Primera",
        sPrevious: "Anterior",
        sNext:     "Siguiente",
        sLast:     "Última"
      }
    }
  });

  $('#fichaje-form button[name="tipo"]').on('click', function(e){
    e.preventDefault();
    let tipo = $(this).val();

    $.post('dashboard.php', { tipo: tipo }, function(resp){
      if(resp.success){
        $('#mensaje-resultado').removeClass('d-none alert-danger')
                             .addClass('alert-success')
                             .text(resp.mensaje).show();
        recargarTabla();
      } else {
        $('#mensaje-resultado').removeClass('d-none alert-success')
                             .addClass('alert-danger')
                             .text(resp.mensaje).show();
      }
      $('#ficharModal').modal('hide');
    }, 'json').fail(function(){
      $('#mensaje-resultado').removeClass('d-none alert-success')
                             .addClass('alert-danger')
                             .text('Hubo un error al registrar el fichaje.').show();
      $('#ficharModal').modal('hide');
    });
  });

  function recargarTabla(){
    $.get('dashboard.php', function(html){
      let tmp = $('<div>').html(html);
      let rows = tmp.find('#fichajes-table-body').html();
      tabla.clear().destroy();
      $('#fichajes-table-body').html(rows);
      tabla = $('#tabla-fichajes').DataTable({
        order: [
          <?php if ($user_role == 'admin'): ?>
          [1,'desc'], [2,'desc']
          <?php else: ?>
          [0,'desc'], [2,'desc']
          <?php endif; ?>
        ],
        language: tabla.settings()[0].init().language
      });
    });
  }
});
</script>
<!-- Realizado por Xulomali_HD (Antonio.R) -->
</body>
</html>

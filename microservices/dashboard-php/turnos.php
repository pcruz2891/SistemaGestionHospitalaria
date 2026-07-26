<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config.php';

$mensaje = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPaciente = $_POST['id_paciente'] ?? '';
    $idMedico = $_POST['id_medico'] ?? '';
    $fechaHora = $_POST['fecha_hora'] ?? '';

    if ($idPaciente === '' || $idMedico === '' || $fechaHora === '') {
        $errorMsg = 'Todos los campos son obligatorios.';
    } else {
        $resp = llamarApi(MS_TURNOS_URL . '/api/v1/turnos', 'POST', [
            'id_paciente' => $idPaciente,
            'id_medico' => $idMedico,
            'fecha_hora' => str_replace('T', 'T', $fechaHora) . ':00',
        ]);

        if ($resp['ok']) {
            $mensaje = 'Turno creado correctamente (ID: ' . htmlspecialchars($resp['data']['id_turno'] ?? '') . ').';
        } else {
            $errorMsg = 'No se pudo crear el turno: ' . ($resp['error'] ?? json_encode($resp['data']));
        }
    }
}

// Listado de turnos vía la API de MS Turnos
$turnosResp = llamarApi(MS_TURNOS_URL . '/api/v1/turnos');
$turnos = $turnosResp['ok'] ? ($turnosResp['data'] ?? []) : [];

// Pacientes y médicos para el formulario (consulta directa a la BD)
$pdo = getPDO();
$pacientes = $pdo->query("SELECT id_paciente, nombre, apellido_paterno FROM pacientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$medicos = $pdo->query("SELECT id_medico, nombre, especialidad FROM medicos WHERE activo = TRUE ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Mapas id -> nombre legible, para mostrar nombres en vez de UUIDs en la tabla
$nombresPacientes = [];
foreach ($pacientes as $p) {
    $nombresPacientes[$p['id_paciente']] = $p['nombre'] . ' ' . $p['apellido_paterno'];
}
$nombresMedicos = [];
foreach ($medicos as $m) {
    $nombresMedicos[$m['id_medico']] = $m['nombre'] . ' — ' . $m['especialidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SIGH — Turnos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="topbar">
        <h1>Sistema de Gestión Hospitalaria</h1>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="turnos.php" class="active">Turnos</a>
            <a href="consultas.php">Consultas</a>
            <a href="logout.php">Cerrar sesión (<?= htmlspecialchars($_SESSION['usuario']['nombre_completo']) ?>)</a>
        </nav>
    </div>

    <div class="container">
        <?php if (!$turnosResp['ok']): ?>
            <div class="api-warning">No se pudo conectar con MS Turnos (<?= MS_TURNOS_URL ?>). Verifica que el servicio esté corriendo en el puerto 8081.</div>
        <?php endif; ?>
        <?php if ($mensaje): ?><div class="success-msg"><?= $mensaje ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="error-msg"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Nuevo turno</h2>
            <form method="POST" action="turnos.php">
                <label for="id_paciente">Paciente</label>
                <select name="id_paciente" id="id_paciente" required>
                    <option value="">Selecciona un paciente</option>
                    <?php foreach ($pacientes as $p): ?>
                        <option value="<?= htmlspecialchars($p['id_paciente']) ?>">
                            <?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido_paterno']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="id_medico">Médico</label>
                <select name="id_medico" id="id_medico" required>
                    <option value="">Selecciona un médico</option>
                    <?php foreach ($medicos as $m): ?>
                        <option value="<?= htmlspecialchars($m['id_medico']) ?>">
                            <?= htmlspecialchars($m['nombre'] . ' — ' . $m['especialidad']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="fecha_hora">Fecha y hora</label>
                <input type="datetime-local" id="fecha_hora" name="fecha_hora" required>

                <button type="submit">Crear turno</button>
            </form>
        </div>

        <div class="card">
            <h2>Turnos registrados</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Turno</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Fecha y hora</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($turnos)): ?>
                        <tr><td colspan="5">No hay turnos registrados todavía.</td></tr>
                    <?php else: ?>
                        <?php foreach ($turnos as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars(substr($t['id_turno'] ?? '', 0, 8)) ?>…</td>
                                <td><?= htmlspecialchars($nombresPacientes[$t['id_paciente'] ?? ''] ?? (substr($t['id_paciente'] ?? '', 0, 8) . '…')) ?></td>
                                <td><?= htmlspecialchars($nombresMedicos[$t['id_medico'] ?? ''] ?? (substr($t['id_medico'] ?? '', 0, 8) . '…')) ?></td>
                                <td><?= htmlspecialchars($t['fecha_hora'] ?? '') ?></td>
                                <td><span class="estado-badge estado-<?= htmlspecialchars($t['estado'] ?? '') ?>"><?= htmlspecialchars($t['estado'] ?? '') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
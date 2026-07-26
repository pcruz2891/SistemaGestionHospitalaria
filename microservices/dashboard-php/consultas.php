<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config.php';

$mensaje = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idTurno = $_POST['id_turno'] ?? '';
    $idPaciente = $_POST['id_paciente'] ?? '';
    $idMedico = $_POST['id_medico'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');

    if ($idTurno === '' || $idPaciente === '' || $idMedico === '' || $motivo === '') {
        $errorMsg = 'Todos los campos son obligatorios.';
    } else {
        $resp = llamarApi(MS_CONSULTA_URL . '/api/v1/consultas', 'POST', [
            'idTurno' => $idTurno,
            'idPaciente' => $idPaciente,
            'idMedico' => $idMedico,
            'motivo' => $motivo,
        ]);

        if ($resp['ok']) {
            $mensaje = 'Consulta registrada correctamente (ID: ' . htmlspecialchars($resp['data']['idConsulta'] ?? '') . '). '
                     . 'MS Turnos fue notificado por HTTP y por el evento ConsultaCompletadaEvent en Kafka.';
        } else {
            $errorMsg = 'No se pudo registrar la consulta: ' . ($resp['error'] ?? json_encode($resp['data']));
        }
    }
}

// Listado de consultas vía la API de MS Consulta
$consultasResp = llamarApi(MS_CONSULTA_URL . '/api/v1/consultas');
$consultas = $consultasResp['ok'] ? ($consultasResp['data'] ?? []) : [];

// Turnos, pacientes y médicos para el formulario
$turnosResp = llamarApi(MS_TURNOS_URL . '/api/v1/turnos');
$turnos = $turnosResp['ok'] ? ($turnosResp['data'] ?? []) : [];

$pdo = getPDO();
$pacientes = $pdo->query("SELECT id_paciente, nombre, apellido_paterno FROM pacientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$medicos = $pdo->query("SELECT id_medico, nombre, especialidad FROM medicos WHERE activo = TRUE ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SIGH — Consultas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="topbar">
        <h1>Sistema de Gestión Hospitalaria</h1>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="turnos.php">Turnos</a>
            <a href="consultas.php" class="active">Consultas</a>
            <a href="logout.php">Cerrar sesión (<?= htmlspecialchars($_SESSION['usuario']['nombre_completo']) ?>)</a>
        </nav>
    </div>

    <div class="container">
        <?php if (!$consultasResp['ok']): ?>
            <div class="api-warning">No se pudo conectar con MS Consulta (<?= MS_CONSULTA_URL ?>). Verifica que el servicio esté corriendo en el puerto 8082.</div>
        <?php endif; ?>
        <?php if ($mensaje): ?><div class="success-msg"><?= $mensaje ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="error-msg"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

        <div class="card">
            <h2>Nueva consulta</h2>
            <form method="POST" action="consultas.php">
                <label for="id_turno">Turno relacionado</label>
                <select name="id_turno" id="id_turno" required>
                    <option value="">Selecciona un turno</option>
                    <?php foreach ($turnos as $t): ?>
                        <option value="<?= htmlspecialchars($t['id_turno']) ?>">
                            <?= htmlspecialchars(substr($t['id_turno'], 0, 8)) ?>… — <?= htmlspecialchars($t['fecha_hora'] ?? '') ?> (<?= htmlspecialchars($t['estado'] ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

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

                <label for="motivo">Motivo de la consulta</label>
                <textarea name="motivo" id="motivo" rows="3" required></textarea>

                <button type="submit">Registrar consulta</button>
            </form>
        </div>

        <div class="card">
            <h2>Consultas registradas</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Consulta</th>
                        <th>Turno</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th>Fecha atención</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($consultas)): ?>
                        <tr><td colspan="5">No hay consultas registradas todavía.</td></tr>
                    <?php else: ?>
                        <?php foreach ($consultas as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars(substr($c['idConsulta'] ?? '', 0, 8)) ?>…</td>
                                <td><?= htmlspecialchars(substr($c['idTurno'] ?? '', 0, 8)) ?>…</td>
                                <td><?= htmlspecialchars($c['motivo'] ?? '') ?></td>
                                <td><span class="estado-badge estado-<?= htmlspecialchars($c['estado'] ?? '') ?>"><?= htmlspecialchars($c['estado'] ?? '') ?></span></td>
                                <td><?= htmlspecialchars($c['fechaAtencion'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

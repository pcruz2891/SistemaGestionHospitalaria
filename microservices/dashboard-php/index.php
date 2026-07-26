<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config.php';

$turnosResp = llamarApi(MS_TURNOS_URL . '/api/v1/turnos');
$consultasResp = llamarApi(MS_CONSULTA_URL . '/api/v1/consultas');

$totalTurnos = $turnosResp['ok'] ? count($turnosResp['data'] ?? []) : null;
$totalConsultas = $consultasResp['ok'] ? count($consultasResp['data'] ?? []) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SIGH — Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="topbar">
        <h1>Sistema de Gestión Hospitalaria</h1>
        <nav>
            <a href="index.php" class="active">Inicio</a>
            <a href="turnos.php">Turnos</a>
            <a href="consultas.php">Consultas</a>
            <a href="logout.php">Cerrar sesión (<?= htmlspecialchars($_SESSION['usuario']['nombre_completo']) ?>)</a>
        </nav>
    </div>

    <div class="container">
        <?php if (!$turnosResp['ok']): ?>
            <div class="api-warning">No se pudo conectar con MS Turnos (<?= MS_TURNOS_URL ?>). Verifica que el servicio esté corriendo.</div>
        <?php endif; ?>
        <?php if (!$consultasResp['ok']): ?>
            <div class="api-warning">No se pudo conectar con MS Consulta (<?= MS_CONSULTA_URL ?>). Verifica que el servicio esté corriendo.</div>
        <?php endif; ?>

        <div class="grid-2">
            <div class="card">
                <h2>Turnos registrados</h2>
                <p style="font-size:32px; font-weight:bold; color:#1a5f9e;">
                    <?= $totalTurnos !== null ? $totalTurnos : '—' ?>
                </p>
                <a href="turnos.php" class="btn">Ver turnos</a>
            </div>
            <div class="card">
                <h2>Consultas registradas</h2>
                <p style="font-size:32px; font-weight:bold; color:#1a5f9e;">
                    <?= $totalConsultas !== null ? $totalConsultas : '—' ?>
                </p>
                <a href="consultas.php" class="btn">Ver consultas</a>
            </div>
        </div>

        <div class="card">
            <h2>Acerca de este panel</h2>
            <p style="font-size:13px; color:#555; line-height:1.6;">
                Este dashboard se comunica directamente con los microservicios MS Turnos
                (<?= MS_TURNOS_URL ?>) y MS Consulta (<?= MS_CONSULTA_URL ?>) mediante llamadas
                HTTP/REST, demostrando la comunicación entre componentes distribuidos del sistema SIGH.
            </p>
        </div>
    </div>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Ingresa usuario y contraseña.';
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT id_usuario, username, password_hash, nombre_completo, rol FROM usuarios WHERE username = :u");
            $stmt->execute(['u' => $username]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                $_SESSION['usuario'] = [
                    'id' => $usuario['id_usuario'],
                    'username' => $usuario['username'],
                    'nombre_completo' => $usuario['nombre_completo'],
                    'rol' => $usuario['rol'],
                ];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'No se pudo conectar a la base de datos. Verifica que PostgreSQL esté corriendo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SIGH — Iniciar sesión</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <h1>Sistema de Gestión Hospitalaria</h1>
            <p class="subtitle">Inicia sesión para continuar</p>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" autocomplete="username" required>

                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>

                <button type="submit" style="width:100%;">Iniciar sesión</button>
            </form>
        </div>
    </div>
</body>
</html>

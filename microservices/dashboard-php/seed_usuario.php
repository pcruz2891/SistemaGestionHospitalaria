<?php
// Script de una sola ejecución para crear la tabla de usuarios (si no existe)
// y sembrar un usuario administrador de prueba.
// El usuario y contraseña se leen desde .env (SEED_ADMIN_USER, SEED_ADMIN_PASSWORD)
// para no exponer credenciales en el código fuente.
// Uso:  php seed_usuario.php
require_once __DIR__ . '/config.php';
try {
    $pdo = getPDO();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario      UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
            username        VARCHAR(50) UNIQUE NOT NULL,
            password_hash   VARCHAR(255) NOT NULL,
            nombre_completo VARCHAR(120) NOT NULL,
            rol             VARCHAR(20) NOT NULL DEFAULT 'medico',
            creado_en       TIMESTAMP NOT NULL DEFAULT now()
        )
    ");
    echo "Tabla 'usuarios' verificada/creada correctamente.\n";
    $username = env('SEED_ADMIN_USER', 'admin');
    $passwordPlano = env('SEED_ADMIN_PASSWORD', 'change_me');
    $hash = password_hash($passwordPlano, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE username = :u");
    $stmt->execute(['u' => $username]);
    if ($stmt->fetch()) {
        echo "El usuario '$username' ya existe, no se duplica.\n";
    } else {
        $insert = $pdo->prepare("
            INSERT INTO usuarios (username, password_hash, nombre_completo, rol)
            VALUES (:u, :p, :n, :r)
        ");
        $insert->execute([
            'u' => $username,
            'p' => $hash,
            'n' => 'Administrador SIGH',
            'r' => 'admin',
        ]);
        echo "Usuario creado -> username: $username / password: $passwordPlano\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
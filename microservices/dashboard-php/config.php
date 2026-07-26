<?php
// Configuración central del dashboard SIGH.
// Conexión a PostgreSQL (para el login) y URLs base de los microservicios.
// Las credenciales y URLs se cargan desde el archivo .env (no versionado en Git)
// para evitar exponer datos sensibles en el repositorio público.

/**
 * Carga variables de entorno desde un archivo .env sencillo (formato CLAVE=VALOR).
 * No sobrescribe variables que ya existan en el entorno del sistema.
 */
function cargarEnv(string $rutaEnv): void {
    if (!file_exists($rutaEnv)) {
        return;
    }
    $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        if (!str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);
        if (getenv($clave) === false) {
            putenv("$clave=$valor");
            $_ENV[$clave] = $valor;
        }
    }
}

cargarEnv(__DIR__ . '/.env');

/**
 * Obtiene una variable de entorno con un valor por defecto opcional.
 */
function env(string $clave, ?string $default = null): ?string {
    $valor = getenv($clave);
    return $valor !== false ? $valor : $default;
}

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '5432'));
define('DB_NAME', env('DB_NAME', 'hospitaldb'));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));
define('MS_TURNOS_URL', env('MS_TURNOS_URL', 'http://localhost:8081'));
define('MS_CONSULTA_URL', env('MS_CONSULTA_URL', 'http://localhost:8082'));

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    return $pdo;
}

/**
 * Llama a una API REST de un microservicio (GET o POST) usando cURL.
 * Devuelve un arreglo con ['ok' => bool, 'status' => int, 'data' => mixed, 'error' => string|null]
 */
function llamarApi(string $url, string $metodo = 'GET', ?array $body = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    if ($metodo === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $respuesta = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    if ($respuesta === false) {
        return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $error];
    }
    $data = json_decode($respuesta, true);
    return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'data' => $data, 'error' => null];
}
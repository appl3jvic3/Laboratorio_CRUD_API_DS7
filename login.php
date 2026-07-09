<?php
// login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Limpiar salida previa
ob_clean();
header('Content-Type: application/json');

// Incluir archivos
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Modelo/Usuario.php';

// Verificar autoload de JWT
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    http_response_code(500);
    echo json_encode(['error' => 'vendor/autoload.php no encontrado. Ejecuta: composer require firebase/php-jwt']);
    exit;
}

use Firebase\JWT\JWT;

// Obtener datos del POST (espera 'username' y 'password')
$input = json_decode(file_get_contents('php://input'), true);

// Verificar que llegaron datos
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibieron datos JSON']);
    exit;
}

// Ahora esperamos 'username' en lugar de 'email'
if (!isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan credenciales (username y password)']);
    exit;
}

$username = $input['username'];
$password = $input['password'];

// Buscar usuario
try {
    $usuarioModel = new Usuario();
    $usuario = $usuarioModel->buscarPorUsername($username);

    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }

    // Verificar contraseña (usa password_hash)
    if (!password_verify($password, $usuario['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Contraseña incorrecta']);
        exit;
    }

    // Generar JWT
    $payload = [
        'sub'   => $usuario['id'],
        'username' => $username,
        'iat'   => time(),
        'exp'   => time() + 3600 // 1 hora
    ];

    $jwt = JWT::encode($payload, JWT_SECRET_KEY, 'HS256');

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'token'   => $jwt,
        'message' => 'Login exitoso',
        'usuario' => [
            'id' => $usuario['id'],
            'username' => $username
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

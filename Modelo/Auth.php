<?php
// Modelo/Auth.php
require_once __DIR__ . '/../config.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    http_response_code(500);
    echo json_encode(['error' => 'vendor/autoload.php no encontrado']);
    exit;
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class Auth
{
    /**
     * Verifica el header Authorization: Bearer <token>
     * Si es válido, devuelve el payload decodificado.
     * Si falla, responde 401 y termina la ejecución (exit).
     */
    public static function verificarToken()
    {
        $headers = getallheaders();

        // getallheaders() puede variar la capitalización según el servidor
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader) || stripos($authHeader, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
            exit;
        }

        $token = trim(substr($authHeader, 7)); // quita "Bearer "

        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
            return $decoded; // contiene sub, username, iat, exp
        } catch (ExpiredException $e) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token expirado, inicia sesión nuevamente']);
            exit;
        } catch (SignatureInvalidException $e) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token inválido']);
            exit;
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token inválido: ' . $e->getMessage()]);
            exit;
        }
    }
}
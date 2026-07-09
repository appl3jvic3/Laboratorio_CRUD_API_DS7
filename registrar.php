<?php
// registrar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_clean();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Modelo/Auth.php';
require_once __DIR__ . '/Modelo/Productos.php';
require_once __DIR__ . '/Modelo/Sanitizador.php';

// ============================================
// 1. VERIFICAR TOKEN (obligatorio para todo este archivo)
// ============================================
$usuarioActual = Auth::verificarToken(); // corta la ejecución si es inválido

// ============================================
// 2. DETECTAR MÉTODO HTTP REAL
// ============================================
$metodo = $_SERVER['REQUEST_METHOD'];

$producto = new Producto();
$response = ['success' => false, 'message' => ''];

try {
    switch ($metodo) {

        // -----------------------------------
        // GET → Listar o buscar por id/termino
        // -----------------------------------
        case 'GET':
            if (!empty($_GET['id'])) {
                $id = Sanitizador::cantidad($_GET['id']);
                $data = $producto->buscar($id);
                if ($data) {
                    http_response_code(200);
                    $response = ['success' => true, 'data' => $data, 'message' => 'Producto encontrado'];
                } else {
                    http_response_code(404);
                    $response = ['success' => false, 'message' => 'Producto no encontrado'];
                }
            } elseif (!empty($_GET['buscar'])) {
                $termino = Sanitizador::busqueda($_GET['buscar']);
                $data = $producto->buscarProductos($termino);
                http_response_code(200);
                $response = ['success' => true, 'data' => $data, 'message' => count($data) . ' resultado(s)'];
            } else {
                $data = $producto->listar();
                http_response_code(200);
                $response = ['success' => true, 'data' => $data, 'message' => 'Productos listados correctamente'];
            }
            break;

        // -----------------------------------
        // POST → Crear producto
        // -----------------------------------
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $campos_requeridos = ['codigo', 'producto', 'precio', 'cantidad'];
            foreach ($campos_requeridos as $campo) {
                if (empty($input[$campo])) {
                    http_response_code(400);
                    $response = ['success' => false, 'message' => "El campo '{$campo}' es obligatorio"];
                    echo json_encode($response);
                    exit;
                }
            }

            if (!Sanitizador::esSeguro($input['producto']) || !Sanitizador::esSeguro($input['codigo'])) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'El producto contiene caracteres no permitidos'];
                echo json_encode($response);
                exit;
            }

            $result = $producto->guardar($input);
            http_response_code($result ? 201 : 500);
            $response = [
                'success' => $result,
                'message' => $result ? 'Producto guardado correctamente' : 'Error al guardar',
                'accion'  => 'Guardar'
            ];
            break;

        // -----------------------------------
        // PUT → Actualizar producto existente
        // -----------------------------------
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['id'])) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'ID de producto no proporcionado'];
                echo json_encode($response);
                exit;
            }

            $campos_requeridos = ['codigo', 'producto', 'precio', 'cantidad'];
            foreach ($campos_requeridos as $campo) {
                if (empty($input[$campo])) {
                    http_response_code(400);
                    $response = ['success' => false, 'message' => "El campo '{$campo}' es obligatorio"];
                    echo json_encode($response);
                    exit;
                }
            }

            if (!Sanitizador::esSeguro($input['producto']) || !Sanitizador::esSeguro($input['codigo'])) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'El producto contiene caracteres no permitidos'];
                echo json_encode($response);
                exit;
            }

            $existe = $producto->buscar(Sanitizador::cantidad($input['id']));
            if (!$existe) {
                http_response_code(404);
                $response = ['success' => false, 'message' => 'Producto no encontrado'];
                echo json_encode($response);
                exit;
            }

            $result = $producto->editar($input['id'], $input);
            http_response_code($result ? 200 : 500);
            $response = [
                'success' => $result,
                'message' => $result ? 'Producto actualizado correctamente' : 'Error al actualizar',
                'accion'  => 'Modificar'
            ];
            break;

        // -----------------------------------
        // DELETE → Eliminar producto
        // -----------------------------------
        case 'DELETE':
            parse_str(file_get_contents('php://input'), $deleteParams);
            $id = $_GET['id'] ?? $deleteParams['id'] ?? null;

            if (empty($id)) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'ID de producto no proporcionado'];
                echo json_encode($response);
                exit;
            }

            $id = Sanitizador::id($id);
            $existe = $producto->buscar($id);
            if (!$existe) {
                http_response_code(404);
                $response = ['success' => false, 'message' => 'Producto no encontrado'];
                echo json_encode($response);
                exit;
            }

            $result = $producto->eliminar($id);
            http_response_code($result ? 200 : 500);
            $response = ['success' => $result, 'message' => $result ? 'Producto eliminado correctamente' : 'Error al eliminar'];
            break;

        default:
            http_response_code(405);
            $response = ['success' => false, 'message' => 'Método no soportado'];
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = ['success' => false, 'message' => $e->getMessage()];
}

ob_clean();
echo json_encode($response);
exit;
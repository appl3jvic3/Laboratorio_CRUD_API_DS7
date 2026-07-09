<?php
// registrar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Limpiar salida previa
ob_clean();
header('Content-Type: application/json');

// Incluir archivos
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Modelo/Productos.php';
require_once __DIR__ . '/Modelo/Sanitizador.php';

// ... (código de verificación de token)

// ============================================
// PROCESAR ACCIÓN
// ============================================
$accion = $_POST['accion'] ?? '';

if (empty($accion)) {
    echo json_encode(['success' => false, 'message' => 'No se especificó ninguna acción']);
    exit;
}

$producto = new Producto();
$response = ['success' => false, 'message' => '', 'accion' => $accion];

try {
    switch ($accion) {
        case 'Guardar':
            // Validar campos obligatorios
            $campos_requeridos = ['codigo', 'producto', 'precio', 'cantidad'];
            foreach ($campos_requeridos as $campo) {
                if (empty($_POST[$campo])) {
                    throw new Exception("El campo '{$campo}' es obligatorio");
                }
            }

            // Verificar que los datos sean seguros
            if (
                !Sanitizador::esSeguro($_POST['producto']) ||
                !Sanitizador::esSeguro($_POST['codigo'])
            ) {
                throw new Exception('El producto contiene caracteres no permitidos');
            }

            $result = $producto->guardar($_POST);
            $response['success'] = $result;
            $response['message'] = $result ? 'Producto guardado correctamente' : 'Error al guardar';
            break;

        case 'Modificar':
            if (empty($_POST['id'])) {
                throw new Exception('ID de producto no proporcionado');
            }

            $campos_requeridos = ['codigo', 'producto', 'precio', 'cantidad'];
            foreach ($campos_requeridos as $campo) {
                if (empty($_POST[$campo])) {
                    throw new Exception("El campo '{$campo}' es obligatorio");
                }
            }

            // Verificar que los datos sean seguros
            if (
                !Sanitizador::esSeguro($_POST['producto']) ||
                !Sanitizador::esSeguro($_POST['codigo'])
            ) {
                throw new Exception('El producto contiene caracteres no permitidos');
            }

            $result = $producto->editar($_POST['id'], $_POST);
            $response['success'] = $result;
            $response['message'] = $result ? 'Producto actualizado correctamente' : 'Error al actualizar';
            break;

        case 'Buscar':
            if (empty($_POST['id'])) {
                throw new Exception('ID de producto no proporcionado');
            }

            $id = Sanitizador::cantidad($_POST['id']);
            $data = $producto->buscar($id);
            if ($data) {
                $response['success'] = true;
                $response['data'] = $data;
                $response['message'] = 'Producto encontrado';
            } else {
                $response['success'] = false;
                $response['message'] = 'Producto no encontrado';
            }
            break;

        case 'Listar':
            $data = $producto->listar();
            $response['success'] = true;
            $response['data'] = $data;
            $response['message'] = 'Productos listados correctamente';
            break;

        // registrar.php - Dentro del switch

        case 'BuscarProductos':
            // Obtener término de búsqueda (puede venir como 'termino' o 'id')
            $termino = $_POST['termino'] ?? $_POST['id'] ?? '';
            $termino = trim($termino);

            // Si no hay término, devolver todos los productos
            if (empty($termino)) {
                $data = $producto->listar();
                $response['success'] = true;
                $response['data'] = $data;
                $response['message'] = 'Mostrando todos los productos (' . count($data) . ')';
                break;
            }

            // Sanitizar término
            $termino = Sanitizador::busqueda($termino);

            // Detectar si es un ID numérico
            if (is_numeric($termino) && strlen($termino) < 10) {
                // Búsqueda por ID exacto
                $id = intval($termino);
                $data = $producto->buscar($id);
                if ($data) {
                    $response['success'] = true;
                    $response['data'] = [$data]; // Devolver como array para consistencia
                    $response['message'] = 'Producto encontrado por ID';
                } else {
                    $response['success'] = false;
                    $response['data'] = [];
                    $response['message'] = 'No se encontró producto con ID ' . $id;
                }
            } else {
                // Búsqueda por nombre o código (LIKE)
                $data = $producto->buscarProductos($termino);
                $response['success'] = true;
                $response['data'] = $data;
                $response['message'] = count($data) > 0 ?
                    'Productos encontrados: ' . count($data) :
                    'No se encontraron productos con "' . $termino . '"';
            }
            break;

        case 'Eliminar':
            if (empty($_POST['id'])) {
                throw new Exception('ID de producto no proporcionado');
            }

            $id = Sanitizador::cantidad($_POST['id']);
            $result = $producto->eliminar($id);
            $response['success'] = $result;
            $response['message'] = $result ? 'Producto eliminado correctamente' : 'Error al eliminar';
            break;

        default:
            throw new Exception('Acción no válida: ' . $accion);
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

// Limpiar y enviar JSON
ob_clean();
echo json_encode($response);
exit;

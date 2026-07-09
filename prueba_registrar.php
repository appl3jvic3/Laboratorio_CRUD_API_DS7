<?php
// prueba_registrar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Prueba de registrar.php</h2>";

// 1. Verificar archivos
echo "<b>1. Archivos necesarios:</b><br>";
echo "config.php: " . (file_exists(__DIR__ . '/config.php') ? '✅' : '❌') . "<br>";
echo "Modelo/Productos.php: " . (file_exists(__DIR__ . '/Modelo/Productos.php') ? '✅' : '❌') . "<br>";
echo "vendor/autoload.php: " . (file_exists(__DIR__ . '/vendor/autoload.php') ? '✅' : '❌') . "<br><br>";

// 2. Verificar que no hay errores de sintaxis
echo "<b>2. Verificando sintaxis de registrar.php:</b><br>";

// Incluir registrar.php y capturar la salida
ob_start();
try {
    // Simular una petición POST con acción Listar
    $_POST['accion'] = 'Listar';

    // Incluir el archivo
    include __DIR__ . '/registrar.php';
    $output = ob_get_clean();

    echo "Respuesta recibida:<br>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc; max-height: 300px; overflow: auto;'>";
    echo htmlspecialchars($output);
    echo "</pre>";

    // Verificar si es JSON válido
    $json = json_decode($output);
    if ($json !== null) {
        echo "✅ La respuesta es JSON válido<br>";
        echo "<pre>";
        print_r($json);
        echo "</pre>";
    } else {
        echo "❌ La respuesta NO es JSON válido<br>";
        echo "Posibles causas:<br>";
        echo "- Error de PHP (revisa el log de errores)<br>";
        echo "- Espacios en blanco antes de &lt;?php<br>";
        echo "- echo o print accidental<br>";
        echo "- error_reporting mostrando warnings<br>";
    }
} catch (Exception $e) {
    ob_clean();
    echo "❌ Error al incluir registrar.php: " . $e->getMessage();
}

<?php
// config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar variables del .env manualmente
$env_file = __DIR__ . '/.env';
$config = [];

if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, '"\'');
            $config[$key] = $value;
        }
    }
}

// Definir constantes
define('DB_HOST', $config['DB_HOST'] ?? 'localhost');
define('DB_NAME', $config['DB_NAME'] ?? 'productosdb');
define('DB_USER', $config['DB_USER'] ?? 'root');
define('DB_PASS', $config['DB_PASS'] ?? '');
define('JWT_SECRET_KEY', $config['JWT_SECRET_KEY'] ?? '2f876bd72c03e4214b0feb6b26ce4d647c8c71b4baf9eda6af71f00528e50d46');
define('JWT_EXPIRATION', $config['JWT_EXPIRATION'] ?? 3600);
define('APP_NAME', $config['APP_NAME'] ?? 'CRUD Productos');
define('APP_ENV', $config['APP_ENV'] ?? 'development');
define('APP_DEBUG', $config['APP_DEBUG'] ?? 'true');

function debug($data)
{
    if (APP_DEBUG) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}

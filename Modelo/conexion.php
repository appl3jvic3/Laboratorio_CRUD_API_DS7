<?php
// Modelo/conexion.php
require_once __DIR__ . '/../config.php';

class DB
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $host = DB_HOST;
            $dbname = DB_NAME;
            $user = DB_USER;
            $pass = DB_PASS;

            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}

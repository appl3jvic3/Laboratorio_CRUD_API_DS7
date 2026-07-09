<?php
// Modelo/Usuario.php
require_once __DIR__ . '/conexion.php';

class Usuario
{
    private $db;

    public function __construct()
    {
        $this->db = DB::getInstance()->getConnection();
    }

    // Buscar usuario por username
    public function buscarPorUsername($username)
    {
        $sql = "SELECT * FROM usuarios WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    // Método para mantener compatibilidad con código existente
    public function buscarPorEmail($email)
    {
        // Redirigir a buscarPorUsername
        return $this->buscarPorUsername($email);
    }
}

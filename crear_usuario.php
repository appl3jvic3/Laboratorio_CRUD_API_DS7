<?php
// crear_usuario.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Modelo/conexion.php';

echo "<h2>🔧 Creando usuario de prueba</h2>";

try {
    $db = DB::getInstance()->getConnection();

    // Datos del usuario
    $username = 'admin';
    $password_hash = password_hash('admin123', PASSWORD_BCRYPT);

    // Verificar si el usuario ya existe
    $sql_check = "SELECT * FROM usuarios WHERE username = ?";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->execute([$username]);

    if ($stmt_check->fetch()) {
        echo "⚠️ El usuario '{$username}' ya existe.<br>";
        echo "📧 Username: {$username}<br>";
        echo "🔑 Contraseña: admin123<br>";
        exit;
    }

    // Insertar usuario
    $sql_insert = "INSERT INTO usuarios (username, password_hash) VALUES (?, ?)";
    $stmt_insert = $db->prepare($sql_insert);
    $stmt_insert->execute([$username, $password_hash]);

    echo "✅ Usuario creado correctamente:<br>";
    echo "📧 Username: {$username}<br>";
    echo "🔑 Contraseña: admin123<br>";
    echo "🔒 Hash: {$password_hash}<br>";
} catch (PDOException $e) {
    echo "❌ Error de base de datos: " . $e->getMessage();
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

<?php
// Modelo/Productos.php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/Sanitizador.php';

class Producto
{
    private $db;

    public function __construct()
    {
        $this->db = DB::getInstance()->getConnection();
    }

    /**
     * Guardar producto usando Sanitizador
     */
    public function guardar($datos)
    {
        // Sanitizar con la clase Sanitizador
        $sanitizados = Sanitizador::sanitizarArray($datos, [
            'codigo' => 'codigo',
            'producto' => 'nombreProducto',
            'precio' => 'precio',
            'cantidad' => 'cantidad'
        ]);

        // Verificar que los datos sean seguros
        if (
            !Sanitizador::esSeguro($sanitizados['producto']) ||
            !Sanitizador::esSeguro($sanitizados['codigo'])
        ) {
            throw new Exception('El producto contiene caracteres no permitidos');
        }

        $sql = "INSERT INTO productos (codigo, producto, precio, cantidad) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $sanitizados['codigo'],
            $sanitizados['producto'],
            $sanitizados['precio'],
            $sanitizados['cantidad']
        ]);
    }

    /**
     * Editar producto usando Sanitizador
     */
    public function editar($id, $datos)
    {
        // Sanitizar con la clase Sanitizador
        $sanitizados = Sanitizador::sanitizarArray($datos, [
            'codigo' => 'codigo',
            'producto' => 'nombreProducto',
            'precio' => 'precio',
            'cantidad' => 'cantidad'
        ]);

        // Verificar que los datos sean seguros
        if (
            !Sanitizador::esSeguro($sanitizados['producto']) ||
            !Sanitizador::esSeguro($sanitizados['codigo'])
        ) {
            throw new Exception('El producto contiene caracteres no permitidos');
        }

        $sql = "UPDATE productos SET codigo=?, producto=?, precio=?, cantidad=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $sanitizados['codigo'],
            $sanitizados['producto'],
            $sanitizados['precio'],
            $sanitizados['cantidad'],
            $id
        ]);
    }

    /**
     * Buscar un producto por ID
     */
    public function buscar($id)
    {
        $sql = "SELECT * FROM productos WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Listar todos los productos
     */
    public function listar()
    {
        $sql = "SELECT * FROM productos ORDER BY id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Buscar productos por código o nombre
     */
    public function buscarProductos($termino)
    {
        // Sanitizar término de búsqueda
        $termino = Sanitizador::busqueda($termino);
        $termino = '%' . $termino . '%';

        $sql = "SELECT * FROM productos 
                WHERE codigo LIKE ? 
                OR producto LIKE ? 
                ORDER BY producto ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$termino, $termino]);
        return $stmt->fetchAll();
    }

    /**
     * Eliminar un producto
     */
    public function eliminar($id)
    {
        $sql = "DELETE FROM productos WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}

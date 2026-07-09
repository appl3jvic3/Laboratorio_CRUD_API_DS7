<?php
// Modelo/Sanitizador.php

/**
 * Clase Sanitizador
 * Proporciona métodos estáticos para sanitizar y validar datos
 * 
 * @author Tu Nombre
 * @version 1.0
 */
class Sanitizador
{
    
    // ============================================
    // CONSTANTES DE PATRONES
    // ============================================

    /**
     * Patrones de caracteres peligrosos para validación
     */
    private const PATRONES_PELIGROSOS = [
        '/<script/i',           // Scripts
        '/<[^>]*>/',            // Etiquetas HTML
        '/javascript:/i',       // Protocolo javascript:
        '/on\w+=/i',            // Event handlers
        '/alert\s*\(/i',        // alert()
        '/eval\s*\(/i',         // eval()
        '/document\./i',        // document.
        '/window\./i',          // window.
        '/\bSELECT\b/i',        // SQL Injection
        '/\bINSERT\b/i',
        '/\bUPDATE\b/i',
        '/\bDELETE\b/i',
        '/\bDROP\b/i',
        '/\bUNION\b/i',
        '/\bEXEC\b/i',
        '/\bXP_/i',
    ];
    
    // ============================================
    // MÉTODOS DE SANITIZACIÓN
    // ============================================

    /**
     * Sanitiza texto general (nombres, descripciones, etc.)
     * Elimina HTML, scripts y caracteres especiales no permitidos
     * 
     * @param string $texto Texto a sanitizar
     * @param bool $capitalizar Si debe capitalizar las palabras (true por defecto)
     * @param string $default Valor por defecto si queda vacío
     * @return string Texto sanitizado
     */
    public static function texto($texto, $capitalizar = true, $default = 'Sin nombre')
    {
        // Convertir a string por si acaso
        $texto = (string) $texto;

        // 1. Eliminar espacios al inicio y final
        $texto = trim($texto);

        // 2. Eliminar etiquetas HTML
        $texto = strip_tags($texto);

        // 3. Convertir entidades HTML
        $texto = htmlspecialchars($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 4. Eliminar caracteres de control invisibles
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);

        // 5. Eliminar caracteres no permitidos
        // Solo permite: letras (acentos y ñ), números, espacios, guiones, puntos, ampersand y signos de exclamación/interrogación
        $texto = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\.\-&!¿?¡]/', '', $texto);

        // 6. Eliminar espacios múltiples
        $texto = preg_replace('/\s+/', ' ', $texto);

        // 7. Capitalizar palabras
        if ($capitalizar) {
            $texto = ucwords(strtolower($texto));
        }

        // 8. Si quedó vacío, usar valor por defecto
        if (empty($texto) || trim($texto) === '') {
            $texto = $default;
        }

        return trim($texto);
    }

    /**
     * Sanitiza códigos de productos
     * Solo permite mayúsculas, números y guiones
     * 
     * @param string $codigo Código a sanitizar
     * @param string $default Valor por defecto si queda vacío
     * @return string Código sanitizado
     */
    public static function codigo($codigo, $default = null)
    {
        $codigo = (string) $codigo;
        $codigo = trim($codigo);
        $codigo = strip_tags($codigo);
        $codigo = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
        $codigo = strtoupper($codigo);
        $codigo = preg_replace('/[^A-Z0-9\-]/', '', $codigo);

        if (empty($codigo)) {
            if ($default === null) {
                $codigo = 'P' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            } else {
                $codigo = $default;
            }
        }

        return $codigo;
    }

    /**
     * Sanitiza precios
     * Asegura que sea un número positivo con 2 decimales
     * 
     * @param mixed $precio Precio a sanitizar
     * @param float $minimo Precio mínimo permitido
     * @return float Precio sanitizado
     */
    public static function precio($precio, $minimo = 0.01)
    {
        $precio = floatval($precio);
        $precio = max(0, $precio);
        $precio = round($precio, 2);

        if ($precio < $minimo) {
            $precio = $minimo;
        }

        return $precio;
    }

    /**
     * Sanitiza cantidades
     * Asegura que sea un entero positivo
     * 
     * @param mixed $cantidad Cantidad a sanitizar
     * @param int $minimo Cantidad mínima permitida
     * @return int Cantidad sanitizada
     */
    public static function cantidad($cantidad, $minimo = 1)
    {
        $cantidad = intval($cantidad);
        $cantidad = max(0, $cantidad);

        if ($cantidad < $minimo) {
            $cantidad = $minimo;
        }

        return $cantidad;
    }

    /**
     * Sanitiza emails
     * 
     * @param string $email Email a sanitizar
     * @return string Email sanitizado o cadena vacía si es inválido
     */
    public static function email($email)
    {
        $email = trim($email);
        $email = strip_tags($email);
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        return $email;
    }

    /**
     * Sanitiza URLs
     * 
     * @param string $url URL a sanitizar
     * @return string URL sanitizada o cadena vacía si es inválida
     */
    public static function url($url)
    {
        $url = trim($url);
        $url = strip_tags($url);
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $url = filter_var($url, FILTER_SANITIZE_URL);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        return $url;
    }

    /**
     * Sanitiza texto para búsquedas
     * Elimina caracteres especiales pero mantiene palabras
     * 
     * @param string $termino Término de búsqueda
     * @return string Término sanitizado
     */
    public static function busqueda($termino)
    {
        $termino = (string) $termino;
        $termino = trim($termino);
        $termino = strip_tags($termino);
        $termino = htmlspecialchars($termino, ENT_QUOTES, 'UTF-8');
        $termino = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-]/', '', $termino);
        return $termino;
    }
    
    // ============================================
    // MÉTODOS DE VALIDACIÓN
    // ============================================

    /**
     * Verifica si un texto contiene código peligroso
     * 
     * @param string $texto Texto a verificar
     * @return bool True si es seguro, False si contiene código peligroso
     */
    public static function esSeguro($texto)
    {
        $texto = (string) $texto;

        foreach (self::PATRONES_PELIGROSOS as $patron) {
            if (preg_match($patron, $texto)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica si un texto es un número válido
     * 
     * @param mixed $valor Valor a verificar
     * @return bool True si es número, False si no
     */
    public static function esNumero($valor)
    {
        return is_numeric($valor) && floatval($valor) > 0;
    }

    /**
     * Verifica si un texto es un entero válido
     * 
     * @param mixed $valor Valor a verificar
     * @return bool True si es entero, False si no
     */
    public static function esEntero($valor)
    {
        return filter_var($valor, FILTER_VALIDATE_INT) !== false;
    }
    
    // ============================================
    // MÉTODO PARA SANITIZAR MÚLTIPLES CAMPOS
    // ============================================

    /**
     * Sanitiza un array de datos según reglas definidas
     * 
     * @param array $datos Array de datos a sanitizar
     * @param array $reglas Array con reglas para cada campo
     * @return array Datos sanitizados
     */
    public static function sanitizarArray($datos, $reglas)
    {
        $sanitizados = [];

        foreach ($reglas as $campo => $regla) {
            $valor = $datos[$campo] ?? null;

            if ($valor === null) {
                $sanitizados[$campo] = null;
                continue;
            }

            switch ($regla) {
                case 'texto':
                    $sanitizados[$campo] = self::texto($valor);
                    break;
                case 'codigo':
                    $sanitizados[$campo] = self::codigo($valor);
                    break;
                case 'precio':
                    $sanitizados[$campo] = self::precio($valor);
                    break;
                case 'cantidad':
                    $sanitizados[$campo] = self::cantidad($valor);
                    break;
                case 'email':
                    $sanitizados[$campo] = self::email($valor);
                    break;
                case 'url':
                    $sanitizados[$campo] = self::url($valor);
                    break;
                case 'busqueda':
                    $sanitizados[$campo] = self::busqueda($valor);
                    break;
                default:
                    $sanitizados[$campo] = self::texto($valor);
                    break;
            }
        }

        return $sanitizados;
    }
}

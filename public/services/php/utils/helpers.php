<?php

/**
 * Funciones auxiliares para el sistema de emails
 * Portal de Negocios
 */


/**
 * Cargar variables de entorno desde archivo .env

 */

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("El archivo .env no existe en la ruta especificada: $path");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Ignorar comentarios
        }

        if (empty(trim($line))) {
            continue; // Ignorar líneas vacías
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        $value = trim($value, '"'); // Eliminar comillas dobles si existen

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
             putenv("$name=$value");
        }
    }
}

/**
 * Obtener valor de variable de entorno
 
 */
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $default;
    }
    if ($value === false) {
        return $default;
    }

    if (is_string($value)) {
        $lower = strtolower($value);
        
        switch ($lower) {
            case 'true':
            case '(true)':
            case '1':
            case 'yes':
                return true;

            case 'false':
            case '(false)':
            case '0':
            case 'no':
                return false;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return null;
        }
    
}
    return $value;
}

/**
 * Validar formato de email

 */

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitizar string para HTML

 */

function sanitizeHTML($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Validar tipo de archivo permitido
 */

function isAllowedFileType($mimeType, $allowedTypes) {
    return in_array($mimeType,  $allowedTypes, true);
}

/**
 * Formatear tamaño de archivo en formato legible
 */

function formatFileSize($bytes) {
   $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}
    /**
 * Registrar log de errores
 */

function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

    $logFile = __DIR__ . '/../../../logs/email.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }   

    @file_put_contents($logFile, $logMessage, FILE_APPEND);

    error_log($logMessage); 
}   

/**
 * Generar respuesta JSON
 * 
 * @param bool $success - Si la operación fue exitosa
 * @param mixed $data - Datos adicionales (errores si $success = false, data si true)
 * @param string|null $message - Mensaje descriptivo
 * @param int $httpCode - Código HTTP de respuesta
 */
function jsonResponse($success, $data = null, $message = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');

    $response = [
        'success' => $success,
        'timestamp' => date('c')
    ];

    if ($message !== null) {
        $response['message'] = $message;
    }

    if ($data !== null) {
        if ($success) {
            $response['data'] = $data;  
        } else {
            $response['error'] = $data;
        }
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

    /**
 * Validar y sanitizar datos del formulario
 */

function validateFormData($data, $rules) {
    $errors = [];
  

    foreach ($rules as $field => $fieldRules) {
        $value = $data[$field] ?? null;
        
        if (isset ($fieldRules['required']) && $fieldRules['required'] && empty($value)) {
            $errors[$field] = $fieldRules['message'] ?? 'El campo {$field} es requerido.';
            continue;
        }
        if (isset($fieldRules['email']) && $fieldRules['email'] && !empty($value)) {
            if (!validateEmail($value)) {
                $errors[$field] = "el formato del mail es invalido";            
            }
        }

        if (isset($fieldRules['min']) && !empty($value) && strlen($value) < $fieldRules['min']) {
            $errors[$field] = "El campo debe tener al menos {$fieldRules['min']} caracteres.";
        }
        if (isset($fieldRules['max']) && !empty($value) && strlen($value) > $fieldRules['max']) {
            $errors[$field] = "El campo no debe exceder {$fieldRules['max']} caracteres.";
        }
    }
    return  [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
?>

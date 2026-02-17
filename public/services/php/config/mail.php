<?php
/**
 * Configuración de email - Portal de Negocios
 * Carga configuración desde variables de entorno de forma segura
 */

require_once __DIR__ . '/../utils/helpers.php';

// Cargar variables de entorno desde .env
$envPaths = [
    __DIR__ . '/../../../.env',    // Producción: public_html/.env
    __DIR__ . '/../../../../.env', // Desarrollo: mi_chofer_website/.env
];

foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        try {
            loadEnv($envPath);
            break;
        } catch (Exception $e) {
            // Continuar buscando
        }
    }
}

/**
 * Configuración SMTP
 */

return [
    'smtp' => [
        'host' => env('SMTP_HOST', 'smtp.hostinger.com'),
        'port' => (int) env('SMTP_PORT', 465),
        'secure' => env('SMTP_SECURE', true), // / true para SSL (465), false para TLS (587)
        'auth' => [
            'user' => env('SMTP_USER'),
            'pass' => env('SMTP_PASS'),
        ],
        'timeout' => 30, // Tiempo de espera en segundos
        'charset' => 'UTF-8',
    ],

     // Configuración de emails
'email' => [
    'from' => [
        'address' => env('SMTP_USER'), 
        'name' => 'Mi Chofer', 
    ],
    'recipient' => env('RECIPIENT_EMAIL', env('SMTP_USER')), 
    'reply_to' => null,
],

    // Configuración de archivos adjuntos

    'upload' => [
        'max_size' => (int) env('MAX_FILE_SIZE', 31457280), // 30MB por defecto
        'allowed_types' => [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
        ],
        'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'gif'],
    ],
    
    // Configuración de la aplicación
    'app' => [
        'name' => 'Mi Chofer',
        'tagline' => 'Tu viaje seguro, cómodo y confiable',
        'company' => 'Mi Chofer - Servicio de Remises',
        'partner' => '',
        'timezone' => 'America/Argentina/Buenos_Aires',
    ],
    
    // Configuración de validación
    'validation' => [
        'required_fields' => ['firstName', 'lastName', 'email', 'message'],
        'optional_fields' => ['phone', 'additionalData', 'hasAdditionalFields'],
        'email_regex' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
        'phone_regex' => '/^[\+]?[0-9\s\-\(\)]{10,}$/',
    ],
    
    // Plantillas de email
    'templates' => [
        'consultation' => [
            'subject' => '� Nueva consulta de contacto - {firstName} {lastName}',
            'html_template' => 'consultation.html',
            'text_template' => 'consultation.txt',
        ],
        'confirmation' => [
            'subject' => '✅ Mensaje recibido - Mi Chofer',
            'html_template' => 'confirmation.html',
            'text_template' => 'confirmation.txt',
        ],
    ],
    
    // Configuración de logs
    'logs' => [
        'enabled' => env('DEBUG', false),
        'level' => env('NODE_ENV', 'development') === 'production' ? 'ERROR' : 'INFO',
        'path' => __DIR__ . '/../../../logs/email.log',
    ],
    
    // Headers de seguridad
    'security' => [
        'cors_allowed_origins' => ['*'], // En producción, especificar dominio exacto
        'cors_allowed_methods' => ['POST', 'OPTIONS'],
        'cors_allowed_headers' => ['Content-Type', 'Authorization'],
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 10, // Máximo de requests por IP
            'time_window' => 3600, // Ventana de tiempo en segundos (1 hora)
        ],
    ],
    
    // Mensajes de respuesta
    'messages' => [
        'success' => 'Consulta enviada exitosamente. Te contactaremos pronto. Por favor, revisá tu bandeja de correos no deseados o spam.',
        'error_validation' => 'Todos los campos obligatorios deben ser completados.',
        'error_email_invalid' => 'El formato del email es inválido.',
        'error_file_type' => 'Tipo de archivo no permitido. Solo se permiten PDF e imágenes.',
        'error_file_size' => 'El tamaño del archivo excede el límite de 30MB.',
        'error_send' => 'Error al enviar el mensaje. Inténtelo nuevamente más tarde.',
        'error_server' => 'Error inesperado al procesar la consulta. Inténtelo nuevamente más tarde.',
    ],
];
?>
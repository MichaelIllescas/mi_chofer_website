<?php
/**
 * Endpoint API para envío de emails - Mi Chofer
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// 1. Cargar dependencias PRIMERO
require_once __DIR__ . '/../php/utils/helpers.php';
require_once __DIR__ . '/../email/EmailService.php';

// 2. Cargar variables de entorno (.env)
$envPaths = [
    __DIR__ . '/../../.env',      // Producción: public_html/.env
    __DIR__ . '/../../../.env',   // Desarrollo: mi_chofer_website/.env
];

foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        loadEnv($envPath);
        break;
    }
}

// 3. Configuración de errores
if (getenv('NODE_ENV') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// 4. Cargar configuración
$config = require __DIR__ . '/../php/config/mail.php';

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Método no permitido. Use POST.', 405);
}

try {
    logMessage('📧 API Mi Chofer: Recibiendo solicitud de contacto...', 'INFO');
    
    // Detectar si es JSON o FormData
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Datos JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Datos JSON inválidos');
        }
        
        // Separar nombre en firstName y lastName
        $nameParts = explode(' ', trim($data['name'] ?? ''), 2);
        $consultationData = [
            'firstName' => $nameParts[0] ?? '',
            'lastName' => $nameParts[1] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => '',
            'message' => $data['message'] ?? '',
            'additionalData' => '',
            'hasAdditionalFields' => 'false'
        ];
    } else {
        // FormData
        $nameParts = explode(' ', trim($_POST['name'] ?? ''), 2);
        $consultationData = [
            'firstName' => $nameParts[0] ?? '',
            'lastName' => $nameParts[1] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => '',
            'message' => $_POST['message'] ?? '',
            'additionalData' => '',
            'hasAdditionalFields' => 'false'
        ];
    }
    
    logMessage("📧 API Mi Chofer: Datos - Nombre: {$consultationData['firstName']}, Email: {$consultationData['email']}", 'INFO');
    
    // Validar campos obligatorios
    if (empty($consultationData['firstName'])) {
        jsonResponse(false, null, 'El nombre es requerido', 400);
    }
    
    if (empty($consultationData['email'])) {
        jsonResponse(false, null, 'El email es requerido', 400);
    }
    
    if (!validateEmail($consultationData['email'])) {
        jsonResponse(false, null, 'El formato del email es inválido', 400);
    }
    
    if (empty($consultationData['message'])) {
        jsonResponse(false, null, 'El mensaje es requerido', 400);
    }
    
    // Crear instancia del servicio de email
    $emailService = new EmailService();
    
    // Enviar email principal (consulta)
    logMessage('📤 API Mi Chofer: Enviando email de consulta...', 'INFO');
    $emailResult = $emailService->sendConsultationEmail($consultationData);
    
    if (!$emailResult['success']) {
        logMessage('❌ API Mi Chofer: Error al enviar email: ' . $emailResult['error'], 'ERROR');
        jsonResponse(false, ['error' => $emailResult['error']], 'Error al enviar el mensaje', 500);
    }
    
    // Enviar email de confirmación al cliente (no bloquear si falla)
    try {
        logMessage('📧 API Mi Chofer: Enviando confirmación al cliente...', 'INFO');
        $emailService->sendConfirmationEmail(
            $consultationData['email'],
            "{$consultationData['firstName']} {$consultationData['lastName']}"
        );
    } catch (Exception $e) {
        logMessage('⚠️ API Mi Chofer: No se pudo enviar confirmación: ' . $e->getMessage(), 'WARNING');
    }
    
    logMessage('✅ API Mi Chofer: Mensaje enviado exitosamente', 'INFO');
    
    // Respuesta exitosa
    jsonResponse(true, 'Mensaje enviado correctamente', [
        'messageId' => $emailResult['messageId'],
        'timestamp' => date('c')
    ], 200);
    
} catch (Exception $e) {
    logMessage('❌ API Mi Chofer: Error: ' . $e->getMessage(), 'ERROR');
    jsonResponse(false, ['error' => $e->getMessage()], 'Error al procesar la solicitud', 500);
}
?>

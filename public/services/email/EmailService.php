<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

/**
 * Servicio de Email - Mi Chofer
 * Maneja el envío de emails con SMTP y archivos adjuntos
 */

// Cargar dependencias
require_once __DIR__ . '/../php/utils/helpers.php';

class EmailService {
    private $config;
    private $smtpConfig;
    private $uploadConfig;
    private $appConfig;
    
    /**
     * Constructor - Carga la configuración
     */
    public function __construct() {
        // Cargar configuración
        $this->config = require __DIR__ . '/../php/config/mail.php';
        $this->smtpConfig = $this->config['smtp'];
        $this->uploadConfig = $this->config['upload'];
        $this->appConfig = $this->config['app'];
        
        // Establecer timezone
        date_default_timezone_set($this->appConfig['timezone']);
    }
    
    /**
     * Obtener configuración
     * 
     * @param string $key Clave de configuración
     * @return mixed Valor de configuración
     */
    public function getConfig($key = null) {
        if ($key === null) {
            return $this->config;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Generar HTML para el email de consulta
     * 
     * @param array $data Datos del formulario
     * @return string HTML del email
     */
    public function generateConsultationEmailHTML($data) {
        // Cargar template
        $template = file_get_contents(__DIR__ . '/templates/consultation-email.html');
        
        // Datos básicos
        $replacements = [
            '{{APP_NAME}}' => $this->appConfig['name'],
            '{{COMPANY}}' => $this->appConfig['company'],
            '{{TAGLINE}}' => $this->appConfig['tagline'],
            '{{PARTNER}}' => $this->appConfig['partner'],
            '{{CURRENT_DATE}}' => date('d \d\e F \d\e Y, H:i'),
            '{{FIRST_NAME}}' => sanitizeHtml($data['firstName']),
            '{{LAST_NAME}}' => sanitizeHtml($data['lastName']),
            '{{EMAIL}}' => sanitizeHtml($data['email']),
            '{{MESSAGE}}' => nl2br(sanitizeHtml($data['message']))
        ];
        
        // Sección de teléfono (opcional)
        if (!empty($data['phone'])) {
            $phoneSection = '<div style="background: #f8fafc; border-left: 4px solid #123142; padding: 15px; margin-bottom: 15px; border-radius: 0 5px 5px 0;">
                <p style="color: #123142; font-weight: bold; font-size: 14px; text-transform: uppercase; margin: 0 0 5px 0;">Teléfono</p>
                <p style="color: #333; font-size: 16px; margin: 0;"><a href="tel:' . sanitizeHtml($data['phone']) . '" style="color: #123142; text-decoration: none;">' . sanitizeHtml($data['phone']) . '</a></p>
            </div>';
        } else {
            $phoneSection = '';
        }
        $replacements['{{PHONE_SECTION}}'] = $phoneSection;
        
        // Sección adicional (opcional)
        if (($data['hasAdditionalFields'] ?? 'false') === 'true' && !empty($data['additionalData'])) {
            $additionalSection = '<div style="background: #e8f5e8; border: 1px solid #4caf50; border-radius: 5px; padding: 20px; margin-top: 20px;">
                <h3 style="color: #4caf50; font-size: 18px; margin: 0 0 15px 0; text-align: center;">📋 Información Adicional</h3>
                <div style="background: white; border-left: 4px solid #4caf50; padding: 15px; border-radius: 0 5px 5px 0;">
                    <p style="color: #333; font-size: 16px; margin: 0; line-height: 1.6;">' . sanitizeHtml($data['additionalData']) . '</p>
                </div>';
            
            if ($data['hasDocument'] ?? false) {
                $additionalSection .= '<div style="background: white; border-left: 4px solid #4caf50; padding: 15px; margin-top: 15px; border-radius: 0 5px 5px 0;">
                    <p style="color: #4caf50; font-weight: bold; font-size: 14px; text-transform: uppercase; margin: 0 0 5px 0;">📎 Documento</p>
                    <p style="color: #333; font-size: 16px; margin: 0;">Ver archivo adjunto</p>
                </div>';
            }
            
            $additionalSection .= '</div>';
        } else {
            $additionalSection = '';
        }
        $replacements['{{ADDITIONAL_SECTION}}'] = $additionalSection;
        
        // Reemplazar todas las variables
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Generar texto plano para el email de consulta (fallback)
     * 
     * @param array $data Datos del formulario
     * @return string Texto plano del email
     */
    public function generatePlainTextEmail($data) {
       $currentDate = date('d \d\e F \d\e Y, H:i');
        
        $text = "NUEVA CONSULTA - " . strtoupper($this->appConfig['name']) . "\n";
        $text .= "=====================================\n\n";
        $text .= "Fecha: {$currentDate}\n\n";
        $text .= "DATOS DEL CLIENTE:\n";
        $text .= "Nombre: {$data['firstName']} {$data['lastName']}\n";
        $text .= "Email: {$data['email']}\n";
        
        if (!empty($data['phone'])) {
            $text .= "Teléfono: {$data['phone']}\n";
        }
        
        $text .= "\nCONSULTA:\n{$data['message']}\n\n";
        
        if (($data['hasAdditionalFields'] ?? 'false') === 'true' && !empty($data['additionalData'])) {
            $text .= "INFORMACIÓN ADICIONAL:\n{$data['additionalData']}\n\n";
        }
        
        if ($data['hasDocument'] ?? false) {
            $text .= "DOCUMENTO: Ver archivo adjunto\n\n";
        }
        
        $text .= "\n--\n{$this->appConfig['name']} - {$this->appConfig['company']}\n";
        $text .= "{$this->appConfig['tagline']}";
        
        return $text;
    }
    
    /**
     * Generar HTML para el email de confirmación al cliente
     * 
     * @param string $clientName Nombre del cliente
     * @return string HTML del email
     */
    public function generateConfirmationEmailHTML($clientName) {
        // Cargar template
        $template = file_get_contents(__DIR__ . '/templates/confirmation-email.html');
        
        // Reemplazos
        $replacements = [
            '{{APP_NAME}}' => $this->appConfig['name'],
            '{{COMPANY}}' => $this->appConfig['company'],
            '{{TAGLINE}}' => $this->appConfig['tagline'],
            '{{PARTNER}}' => $this->appConfig['partner'],
            '{{CLIENT_NAME}}' => sanitizeHtml($clientName)
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Generar texto plano para el email de confirmación (fallback)
     * 
     * @param string $clientName Nombre del cliente
     * @return string Texto plano del email
     */
    public function generateConfirmationPlainText($clientName) {
        $text = "Hola {$clientName},\n\n";
        $text .= "Recibimos tu consulta exitosamente y nuestro equipo se pondrá en contacto contigo a la brevedad.\n\n";
        $text .= "¿Qué sigue?\n";
        $text .= "- Revisaremos tu consulta en detalle\n";
        $text .= "- Te contactaremos en las próximas 24-48 horas\n";
        $text .= "- Coordinaremos el servicio que necesites\n\n";
        $text .= "\"{$this->appConfig['tagline']}\"\n\n";
        $text .= "--\n{$this->appConfig['name']}\n";
        $text .= "{$this->appConfig['company']}";

        return $text;
    }

    /**
     * Enviar email usando PHPMailer
     *
     * @param string $to Destinatario
     * @param string $subject Asunto
     * @param string $htmlBody Cuerpo HTML
     * @param string $textBody Cuerpo texto plano
     * @param string|null $replyTo Reply-To
     * @param array|null $attachment Archivo adjunto
     * @return bool True si se envió correctamente, false si no
     */
    public function sendEmail($to, $subject, $htmlBody, $textBody = '', $replyTo = null, $attachment = null) {
        $mail = new PHPMailer(true);
        
        try {
            // Configuración SMTP desde archivo de configuración
            $mail->isSMTP();
            $mail->Host = $this->smtpConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpConfig['auth']['user'];
            $mail->Password = $this->smtpConfig['auth']['pass'];
            $mail->SMTPSecure = $this->smtpConfig['secure'] ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpConfig['port'];
            $mail->CharSet = $this->smtpConfig['charset'];
            $mail->Timeout = $this->smtpConfig['timeout'];
            $mail->SMTPDebug = 0;
            
            // Remitente y destinatario desde configuración
            $mail->setFrom($this->config['email']['from']['address'], $this->config['email']['from']['name']);
            $mail->addAddress($to);
            if ($replyTo) $mail->addReplyTo($replyTo);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;
            
            // Adjunto
            if ($attachment && isset($attachment['tmp_name']) && file_exists($attachment['tmp_name'])) {
                $mail->addAttachment($attachment['tmp_name'], $attachment['name']);
                logMessage("Archivo adjunto: {$attachment['name']}", 'INFO');
            }
            
            // Enviar
            $mail->send();
            logMessage("Email enviado a: {$to}", 'INFO');
            return true;
            
        } catch (Exception $e) {
            logMessage("Error PHPMailer: " . $mail->ErrorInfo, 'ERROR');
            return false;
        }
    }
    
    /**
     * Enviar email de consulta
     * 
     * @param array $formData Datos del formulario
     * @param array|null $document Archivo adjunto
     * @return array Resultado del envío ['success' => bool, 'message' => string]
     */
    public function sendConsultationEmail($formData, $document = null) {
        try {
            $recipient = $this->config['email']['recipient'];
            
            // Marcar si tiene documento
            if ($document) {
                $formData['hasDocument'] = true;
            }
            
            // Generar contenido del email
            $htmlBody = $this->generateConsultationEmailHTML($formData);
            $textBody = $this->generatePlainTextEmail($formData);
            
            // Generar asunto
            $subject = str_replace(
                ['{firstName}', '{lastName}'],
                [$formData['firstName'], $formData['lastName']],
                $this->config['templates']['consultation']['subject']
            );
            
            // Enviar email
            $sent = $this->sendEmail(
                $recipient,
                $subject,
                $htmlBody,
                $textBody,
                $formData['email'],
                $document
            );
            
            if ($sent) {
                logMessage("Email de consulta enviado exitosamente", 'INFO');
                return [
                    'success' => true,
                    'message' => 'Email enviado correctamente',
                    'messageId' => 'php-' . time() . '-' . md5($formData['email'])
                ];
            } else {
                throw new Exception('No se pudo enviar el email');
            }
            
        } catch (Exception $e) {
            logMessage("Error al enviar email de consulta: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al enviar el email'
            ];
        }
    }
    
    /**
     * Enviar email de confirmación al cliente
     * 
     * @param string $clientEmail Email del cliente
     * @param string $clientName Nombre del cliente
     * @return array Resultado del envío ['success' => bool, 'message' => string]
     */
    public function sendConfirmationEmail($clientEmail, $clientName) {
        try {
            // Generar contenido del email
            $htmlBody = $this->generateConfirmationEmailHTML($clientName);
            $textBody = $this->generateConfirmationPlainText($clientName);
            
            // Asunto del email
            $subject = $this->config['templates']['confirmation']['subject'];
            
            // Enviar email
            $sent = $this->sendEmail(
                $clientEmail,
                $subject,
                $htmlBody,
                $textBody,
                $this->config['email']['from']['address']
            );
            
            if ($sent) {
                logMessage("Email de confirmación enviado a: {$clientEmail}", 'INFO');
                return [
                    'success' => true,
                    'messageId' => 'php-confirm-' . time() . '-' . md5($clientEmail)
                ];
            } else {
                throw new Exception('No se pudo enviar el email de confirmación');
            }
            
        } catch (Exception $e) {
            logMessage("Error al enviar confirmación: " . $e->getMessage(), 'WARNING');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
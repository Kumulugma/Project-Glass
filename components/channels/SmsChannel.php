<?php

namespace app\components\channels;

use Yii;
use app\models\NotificationQueue;

/**
 * SmsChannel - wysyłka powiadomień SMS
 * Obsługuje różnych providerów: Twilio, Vonage (Nexmo), itp.
 */
class SmsChannel extends AbstractChannel
{
    /**
     * @inheritdoc
     */
    public function send(NotificationQueue $notification)
    {
        $provider = $this->getConfig('provider', 'twilio');
        $apiKey = $this->getConfig('api_key');
        $apiSecret = $this->getConfig('api_secret');
        $fromNumber = $this->getConfig('from_number');
        
        // Walidacja konfiguracji
        if (!$apiKey || !$apiSecret || !$fromNumber) {
            $error = 'SMS channel nie jest skonfigurowany (brak API credentials lub numeru nadawcy)';
            $this->logError($notification, $error);
            
            return [
                'success' => false,
                'response' => null,
                'error' => $error,
            ];
        }
        
        // Walidacja odbiorcy
        $recipient = $this->normalizePhoneNumber($notification->recipient);
        if (!$recipient) {
            $error = 'Nieprawidłowy numer telefonu: ' . $notification->recipient;
            $this->logError($notification, $error);
            
            return [
                'success' => false,
                'response' => null,
                'error' => $error,
            ];
        }
        
        // Wysyłka przez wybranego providera
        try {
            switch ($provider) {
                case 'twilio':
                    $result = $this->sendViaTwilio($recipient, $notification->message, $apiKey, $apiSecret, $fromNumber);
                    break;
                
                case 'vonage':
                case 'nexmo':
                    $result = $this->sendViaVonage($recipient, $notification->message, $apiKey, $apiSecret, $fromNumber);
                    break;
                
                default:
                    throw new \Exception("Nieobsługiwany provider SMS: {$provider}");
            }
            
            if ($result['success']) {
                $this->logSuccess($notification, $result['response']);
            } else {
                $this->logError($notification, $result['error']);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $error = 'SMS send failed: ' . $e->getMessage();
            $this->logError($notification, $error);
            
            return [
                'success' => false,
                'response' => null,
                'error' => $error,
            ];
        }
    }
    
    /**
     * Wysyłka przez Twilio
     */
    private function sendViaTwilio($to, $message, $accountSid, $authToken, $from)
    {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        
        $data = [
            'From' => $from,
            'To' => $to,
            'Body' => $message,
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'response' => null,
                'error' => "cURL error: {$error}",
            ];
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'response' => $decoded,
                'error' => null,
            ];
        } else {
            return [
                'success' => false,
                'response' => $decoded,
                'error' => $decoded['message'] ?? "HTTP {$httpCode}",
            ];
        }
    }
    
    /**
     * Wysyłka przez Vonage (Nexmo)
     */
    private function sendViaVonage($to, $message, $apiKey, $apiSecret, $from)
    {
        $url = "https://rest.nexmo.com/sms/json";
        
        $data = [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $from,
            'to' => $to,
            'text' => $message,
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'response' => null,
                'error' => "cURL error: {$error}",
            ];
        }
        
        $decoded = json_decode($response, true);
        
        if (isset($decoded['messages'][0]['status']) && $decoded['messages'][0]['status'] == '0') {
            return [
                'success' => true,
                'response' => $decoded,
                'error' => null,
            ];
        } else {
            $errorText = $decoded['messages'][0]['error-text'] ?? "HTTP {$httpCode}";
            return [
                'success' => false,
                'response' => $decoded,
                'error' => $errorText,
            ];
        }
    }
    
    /**
     * Normalizuje numer telefonu do formatu międzynarodowego
     */
    private function normalizePhoneNumber($phone)
    {
        // Usuń wszystko oprócz cyfr i znaku +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Jeśli zaczyna się od +, to OK
        if (substr($cleaned, 0, 1) === '+') {
            return $cleaned;
        }
        
        // Jeśli polski numer bez prefiksu, dodaj +48
        if (strlen($cleaned) === 9 && substr($cleaned, 0, 1) !== '0') {
            return '+48' . $cleaned;
        }
        
        // W przeciwnym razie zwróć jako jest (może być błędny)
        return $cleaned ?: null;
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        $errors = [];
        
        if (!$this->getConfig('api_key')) {
            $errors[] = 'API Key jest wymagany';
        }
        
        if (!$this->getConfig('api_secret')) {
            $errors[] = 'API Secret jest wymagany';
        }
        
        if (!$this->getConfig('from_number')) {
            $errors[] = 'Numer nadawcy jest wymagany';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * @inheritdoc
     */
    public function isAvailable()
    {
        $enabled = $this->getConfig('enabled', false);
        $hasCredentials = $this->getConfig('api_key') && $this->getConfig('api_secret');
        
        return $enabled && $hasCredentials;
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'SMS';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Wysyłka powiadomień SMS przez Twilio lub Vonage (Nexmo)';
    }
    
    /**
     * @inheritdoc
     */
    public static function getConfigFields()
    {
        return [
            'provider' => [
                'type' => 'select',
                'label' => 'Provider',
                'options' => [
                    'twilio' => 'Twilio',
                    'vonage' => 'Vonage (Nexmo)',
                ],
                'default' => 'twilio',
            ],
            'api_key' => [
                'type' => 'text',
                'label' => 'API Key / Account SID',
                'required' => true,
            ],
            'api_secret' => [
                'type' => 'password',
                'label' => 'API Secret / Auth Token',
                'required' => true,
            ],
            'from_number' => [
                'type' => 'text',
                'label' => 'Numer nadawcy',
                'placeholder' => '+48123456789',
                'required' => true,
                'help' => 'Numer telefonu lub Alphanumeric Sender ID',
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    protected function getDefaultCooldown()
    {
        return 120; // 2 godziny (SMS jest droższe niż email)
    }
}
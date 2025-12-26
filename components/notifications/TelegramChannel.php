<?php

namespace app\components\notifications;

use Yii;
use app\models\NotificationQueue;

/**
 * Kanał powiadomień przez Telegram Bot
 */
class TelegramChannel implements NotificationChannel
{
    /**
     * @inheritdoc
     */
    public function send(NotificationQueue $notification)
    {
        try {
            $token = Yii::$app->params['telegram']['token'] ?? null;
            $chatId = $notification->recipient;
            
            if (!$token) {
                throw new \Exception('Brak tokena Telegram w konfiguracji');
            }
            
            // Formatuj wiadomość
            $message = $notification->message;
            if ($notification->subject) {
                $message = "<b>{$notification->subject}</b>\n\n{$message}";
            }
            
            // API Telegram
            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $data = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];
            
            // Wyślij przez cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['ok']) && $result['ok']) {
                return [
                    'success' => true,
                    'response' => 'Telegram message sent, ID: ' . ($result['result']['message_id'] ?? 'unknown'),
                    'error' => null,
                ];
            } else {
                return [
                    'success' => false,
                    'response' => null,
                    'error' => $result['description'] ?? 'Unknown Telegram API error',
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        $errors = [];
        
        if (!isset(Yii::$app->params['telegram']['token'])) {
            $errors[] = 'Brak telegram.token w params';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * @inheritdoc
     */
    public function isAvailable()
    {
        return isset(Yii::$app->params['telegram']['token']);
    }
}

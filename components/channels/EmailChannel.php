<?php

namespace app\components\channels;

use Yii;
use app\models\NotificationQueue;
use app\models\Setting;
use app\models\Task;

/**
 * EmailChannel - Kanał powiadomień przez Email
 */
class EmailChannel implements NotificationChannel
{
    /**
     * Wysyła powiadomienie przez email
     * 
     * @param NotificationQueue $notification
     * @return array
     */
    public function send(NotificationQueue $notification)
    {
        try {
            // Pobierz ustawienia z bazy danych
            $fromAddress = Setting::get('channel_email_from_address');
            $fromName = Setting::get('channel_email_from_name', 'Task Reminder');
            $replyTo = Setting::get('channel_email_reply_to');
            $useHtml = Setting::get('channel_email_use_html', true);
            
            // Fallback do params jeśli nie ma w ustawieniach
            if (empty($fromAddress)) {
                $fromAddress = Yii::$app->params['adminEmail'] ?? 'noreply@example.com';
            }
            
            // Pobierz task jeśli jest dostępny (dla lepszego formatowania)
            $task = null;
            if ($notification->task_id) {
                $task = Task::findOne($notification->task_id);
            }
            
            // Przygotuj dane dla szablonu
            $viewData = [
                'subject' => $notification->subject ?? 'Powiadomienie',
                'message' => $notification->message,
                'task' => $task,
            ];
            
            // Utwórz wiadomość email
            if ($useHtml) {
                // Wyślij wersję HTML i TEXT (multipart)
                $message = Yii::$app->mailer->compose(
                    ['html' => 'notification-html', 'text' => 'notification-text'],
                    $viewData
                );
            } else {
                // Wyślij tylko wersję TEXT
                $message = Yii::$app->mailer->compose(
                    ['text' => 'notification-text'],
                    $viewData
                );
            }
            
            $message->setFrom([$fromAddress => $fromName])
                ->setTo($notification->recipient)
                ->setSubject($notification->subject ?? 'Powiadomienie');
            
            // Ustaw Reply-To jeśli jest skonfigurowane
            if (!empty($replyTo)) {
                $message->setReplyTo($replyTo);
            }
            
            // Wyślij email
            $result = $message->send();
            
            if ($result) {
                return [
                    'success' => true,
                    'response' => "Email wysłany do {$notification->recipient}",
                    'error' => null,
                ];
            } else {
                return [
                    'success' => false,
                    'response' => null,
                    'error' => 'Nie udało się wysłać emaila (mailer zwrócił false)',
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error("EmailChannel send error: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Waliduje konfigurację
     * 
     * @return array|true
     */
    public function validateConfig()
    {
        $errors = [];
        
        // Sprawdź czy mailer jest skonfigurowany
        if (!isset(Yii::$app->mailer)) {
            $errors[] = 'Yii2 mailer nie jest skonfigurowany';
        }
        
        // Sprawdź adres nadawcy
        $fromAddress = Setting::get('channel_email_from_address');
        if (empty($fromAddress) && empty(Yii::$app->params['adminEmail'])) {
            $errors[] = 'Brak adresu email nadawcy (ustaw channel_email_from_address lub adminEmail w params)';
        }
        
        // Waliduj format email
        if (!empty($fromAddress) && !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Nieprawidłowy format adresu email nadawcy';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Czy kanał jest dostępny
     * 
     * @return bool
     */
    public function isAvailable()
    {
        $enabled = Setting::get('channel_email_enabled', false);
        $hasMailer = isset(Yii::$app->mailer);
        $hasFromAddress = !empty(Setting::get('channel_email_from_address')) || !empty(Yii::$app->params['adminEmail']);
        
        return $enabled && $hasMailer && $hasFromAddress;
    }
    
    /**
     * Nazwa wyświetlana
     * 
     * @return string
     */
    public static function getDisplayName()
    {
        return 'Email';
    }
    
    /**
     * Opis kanału
     * 
     * @return string
     */
    public static function getDescription()
    {
        return 'Wysyła powiadomienia przez email (SMTP)';
    }
    
    /**
     * Pola konfiguracyjne
     * 
     * @return array
     */
    public static function getConfigFields()
    {
        return [
            'from_address' => [
                'type' => 'text',
                'label' => 'Adres email nadawcy',
                'placeholder' => 'noreply@example.com',
                'help' => 'Adres z którego będą wysyłane powiadomienia',
                'required' => true,
            ],
            'from_name' => [
                'type' => 'text',
                'label' => 'Nazwa nadawcy',
                'placeholder' => 'Task Reminder',
                'default' => 'Task Reminder',
                'help' => 'Nazwa wyświetlana w polu "Od"',
            ],
            'reply_to' => [
                'type' => 'text',
                'label' => 'Adres odpowiedzi (Reply-To)',
                'placeholder' => 'support@example.com',
                'help' => 'Opcjonalny adres do odpowiedzi',
            ],
            'use_html' => [
                'type' => 'checkbox',
                'label' => 'Używaj HTML',
                'default' => true,
                'help' => 'Wysyłaj wiadomości jako HTML (z podstawowym formatowaniem)',
            ],
        ];
    }
    
    /**
     * Identyfikator kanału
     * 
     * @return string
     */
    public static function getIdentifier()
    {
        return 'email';
    }
}
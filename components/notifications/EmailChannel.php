<?php

namespace app\components\notifications;

use Yii;
use app\models\NotificationQueue;

/**
 * Kanał powiadomień przez Email (używa Yii2 mailer)
 */
class EmailChannel implements NotificationChannel
{
    /**
     * @inheritdoc
     */
    public function send(NotificationQueue $notification)
    {
        try {
            $result = Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['adminEmail'] => 'Task Reminder'])
                ->setTo($notification->recipient)
                ->setSubject($notification->subject ?? 'Powiadomienie')
                ->setTextBody($notification->message)
                ->send();
            
            if ($result) {
                return [
                    'success' => true,
                    'response' => 'Email wysłany',
                    'error' => null,
                ];
            } else {
                return [
                    'success' => false,
                    'response' => null,
                    'error' => 'Nie udało się wysłać emaila',
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
        // Sprawdź czy Yii2 mailer jest skonfigurowany
        if (!isset(Yii::$app->mailer)) {
            return ['Yii2 mailer nie jest skonfigurowany'];
        }
        
        return true;
    }
    
    /**
     * @inheritdoc
     */
    public function isAvailable()
    {
        return isset(Yii::$app->mailer);
    }
}

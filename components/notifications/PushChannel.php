<?php

namespace app\components\notifications;

use Yii;
use app\models\NotificationQueue;
use app\models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Kanał powiadomień przez Web Push (PWA)
 */
class PushChannel implements NotificationChannel
{
    /**
     * @inheritdoc
     */
    public function send(NotificationQueue $notification)
    {
        try {
            // Pobierz wszystkie aktywne subskrypcje
            $subscriptions = PushSubscription::findActive();
            
            if (empty($subscriptions)) {
                return [
                    'success' => false,
                    'response' => null,
                    'error' => 'Brak aktywnych subskrypcji push',
                ];
            }
            
            // Konfiguracja Web Push
            $auth = [
                'VAPID' => [
                    'subject' => Yii::$app->params['webpush']['subject'],
                    'publicKey' => Yii::$app->params['webpush']['publicKey'],
                    'privateKey' => Yii::$app->params['webpush']['privateKey'],
                ],
            ];
            
            $webPush = new WebPush($auth);
            
            // Payload powiadomienia
            $payload = json_encode([
                'title' => $notification->subject ?? 'Przypomnienie',
                'body' => $notification->message,
                'icon' => '/images/icon-192.png',
                'badge' => '/images/badge-72.png',
                'data' => [
                    'notification_id' => $notification->id,
                    'task_id' => $notification->task_id,
                    'url' => '/task/view?id=' . $notification->task_id,
                ],
                'actions' => [
                    ['action' => 'open', 'title' => 'Otwórz'],
                    ['action' => 'close', 'title' => 'Zamknij'],
                ],
            ]);
            
            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            
            // Wyślij do wszystkich subskrypcji
            foreach ($subscriptions as $subscription) {
                $pushSubscription = Subscription::create($subscription->toWebPushFormat());
                
                try {
                    $report = $webPush->sendOneNotification(
                        $pushSubscription,
                        $payload
                    );
                    
                    if ($report->isSuccess()) {
                        $successCount++;
                        $subscription->touch();
                    } else {
                        $failedCount++;
                        $errors[] = $report->getReason();
                        
                        // Jeśli subskrypcja wygasła, oznacz jako nieaktywną
                        if ($report->isSubscriptionExpired()) {
                            $subscription->markAsInactive('Subscription expired');
                        }
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = $e->getMessage();
                }
            }
            
            if ($successCount > 0) {
                return [
                    'success' => true,
                    'response' => "Wysłano do {$successCount} urządzeń" . ($failedCount > 0 ? ", {$failedCount} błędów" : ''),
                    'error' => $failedCount > 0 ? implode('; ', array_unique($errors)) : null,
                ];
            } else {
                return [
                    'success' => false,
                    'response' => null,
                    'error' => 'Nie udało się wysłać do żadnego urządzenia: ' . implode('; ', array_unique($errors)),
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
        
        if (!isset(Yii::$app->params['webpush']['subject'])) {
            $errors[] = 'Brak webpush.subject w params';
        }
        
        if (!isset(Yii::$app->params['webpush']['publicKey'])) {
            $errors[] = 'Brak webpush.publicKey w params';
        }
        
        if (!isset(Yii::$app->params['webpush']['privateKey'])) {
            $errors[] = 'Brak webpush.privateKey w params';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * @inheritdoc
     */
    public function isAvailable()
    {
        return isset(Yii::$app->params['webpush']['publicKey']) 
            && isset(Yii::$app->params['webpush']['privateKey']);
    }
}

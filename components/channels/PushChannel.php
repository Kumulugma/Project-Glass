<?php

namespace app\components\channels;

use Yii;
use app\models\NotificationQueue;
use app\models\PushSubscription;
use app\models\Setting;
use app\models\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * PushChannel - Kanał powiadomień Web Push (PWA) z filtrowaniem per-użytkownik
 */
class PushChannel implements NotificationChannel
{
    /**
     * Wysyła powiadomienie przez Web Push
     * 
     * @param NotificationQueue $notification
     * @return array
     */
    public function send(NotificationQueue $notification)
    {
        try {
            // Filtruj subskrypcje po recipient
            $subscriptions = $this->getRelevantSubscriptions($notification->recipient);
            
            if (empty($subscriptions)) {
                return [
                    'success' => false,
                    'response' => null,
                    'error' => 'Brak aktywnych subskrypcji push dla odbiorcy: ' . $notification->recipient,
                ];
            }
            
            // Pobierz klucze VAPID z ustawień
            $publicKey = Setting::get('channel_push_vapid_public_key');
            $privateKey = Setting::get('channel_push_vapid_private_key');
            $subject = Setting::get('channel_push_vapid_subject', 'mailto:admin@example.com');
            
            if (!$publicKey || !$privateKey) {
                return [
                    'success' => false,
                    'response' => null,
                    'error' => 'Brak kluczy VAPID w konfiguracji',
                ];
            }
            
            // Konfiguracja Web Push
            $auth = [
                'VAPID' => [
                    'subject' => $subject,
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ];
            
            $webPush = new WebPush($auth);
            
            // Payload powiadomienia
            $payload = json_encode([
                'title' => $notification->subject ?? 'Przypomnienie',
                'body' => $notification->message,
                'icon' => '/favicon.ico',
                'badge' => '/favicon.ico',
                'tag' => 'task-' . $notification->task_id,
                'requireInteraction' => false,
                'data' => [
                    'notification_id' => $notification->id,
                    'task_id' => $notification->task_id,
                    'url' => Yii::$app->urlManager->createAbsoluteUrl(['/task/view', 'id' => $notification->task_id]),
                    'timestamp' => time(),
                ],
                'actions' => [
                    ['action' => 'open', 'title' => '🔔 Otwórz'],
                    ['action' => 'close', 'title' => '✕ Zamknij'],
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
                        $subscription->updateLastSentAt();
                    } else {
                        $failedCount++;
                        $reason = $report->getReason();
                        $errors[] = $reason;
                        
                        Yii::warning("Push notification failed: {$reason}", __METHOD__);
                        
                        // Jeśli subskrypcja wygasła lub jest nieprawidłowa, oznacz jako nieaktywną
                        if ($report->isSubscriptionExpired() || $report->getStatusCode() === 410) {
                            $subscription->markAsInactive('Subscription expired or invalid');
                        }
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = $e->getMessage();
                    Yii::error("Push notification exception: " . $e->getMessage(), __METHOD__);
                }
            }
            
            // Zwróć wynik
            if ($successCount > 0) {
                return [
                    'success' => true,
                    'response' => sprintf(
                        'Wysłano do %d urządzeń%s',
                        $successCount,
                        $failedCount > 0 ? ", {$failedCount} błędów" : ''
                    ),
                    'error' => $failedCount > 0 ? implode('; ', array_slice(array_unique($errors), 0, 3)) : null,
                ];
            } else {
                return [
                    'success' => false,
                    'response' => null,
                    'error' => 'Nie udało się wysłać do żadnego urządzenia: ' . implode('; ', array_slice(array_unique($errors), 0, 3)),
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error("PushChannel send error: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Znajduje odpowiednie subskrypcje dla danego odbiorcy
     * 
     * @param string $recipient Email lub user_id
     * @return PushSubscription[]
     */
    protected function getRelevantSubscriptions($recipient)
    {
        $query = PushSubscription::find()
            ->where(['is_active' => true])
            ->andWhere(['or', ['failed_at' => null], ['<', 'failed_at', date('Y-m-d H:i:s', strtotime('-7 days'))]]);
        
        // Sprawdź czy recipient to email
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            // Znajdź user_id po emailu
            $user = User::findOne(['email' => $recipient]);
            
            if ($user) {
                // Filtruj po user_id LUB email (dla compatibility)
                $query->andWhere([
                    'or',
                    ['user_id' => $user->id],
                    ['device_name' => $recipient], // Fallback: device_name zawiera email
                ]);
            } else {
                // Brak użytkownika - szukaj po device_name
                $query->andWhere(['device_name' => $recipient]);
            }
        } else {
            // Recipient to user_id (int) lub inna wartość
            $query->andWhere([
                'or',
                ['user_id' => $recipient],
                ['device_name' => $recipient],
            ]);
        }
        
        return $query->all();
    }
    
    /**
     * Waliduje konfigurację
     * 
     * @return array|true
     */
    public function validateConfig()
    {
        $errors = [];
        
        $publicKey = Setting::get('channel_push_vapid_public_key');
        $privateKey = Setting::get('channel_push_vapid_private_key');
        
        if (empty($publicKey)) {
            $errors[] = 'Brak VAPID Public Key';
        }
        
        if (empty($privateKey)) {
            $errors[] = 'Brak VAPID Private Key';
        }
        
        // Sprawdź czy biblioteka web-push jest zainstalowana
        if (!class_exists('Minishlink\WebPush\WebPush')) {
            $errors[] = 'Biblioteka minishlink/web-push nie jest zainstalowana. Uruchom: composer require minishlink/web-push';
        }
        
        // Sprawdź czy model PushSubscription istnieje
        if (!class_exists('app\models\PushSubscription')) {
            $errors[] = 'Model PushSubscription nie istnieje';
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
        $enabled = Setting::get('channel_push_enabled', false);
        $publicKey = Setting::get('channel_push_vapid_public_key');
        $privateKey = Setting::get('channel_push_vapid_private_key');
        
        return $enabled 
            && !empty($publicKey) 
            && !empty($privateKey)
            && class_exists('Minishlink\WebPush\WebPush');
    }
    
    /**
     * Nazwa wyświetlana
     * 
     * @return string
     */
    public static function getDisplayName()
    {
        return 'Web Push (PWA)';
    }
    
    /**
     * Opis kanału
     * 
     * @return string
     */
    public static function getDescription()
    {
        return 'Wysyła powiadomienia push do przeglądarek użytkowników. Wymaga subskrypcji użytkownika.';
    }
    
    /**
     * Pola konfiguracyjne
     * 
     * @return array
     */
    public static function getConfigFields()
    {
        return [
            'vapid_subject' => [
                'type' => 'text',
                'label' => 'VAPID Subject',
                'placeholder' => 'mailto:admin@example.com',
                'help' => 'Adres email lub URL do kontaktu',
                'required' => true,
            ],
            'vapid_public_key' => [
                'type' => 'textarea',
                'label' => 'VAPID Public Key',
                'placeholder' => 'BP1ZL...',
                'help' => 'Wygeneruj klucze: php generate-vapid-keys.php',
                'required' => true,
                'rows' => 2,
            ],
            'vapid_private_key' => [
                'type' => 'password',
                'label' => 'VAPID Private Key',
                'placeholder' => 'AAAA...',
                'help' => 'Klucz prywatny VAPID (trzymaj w tajemnicy!)',
                'required' => true,
            ],
        ];
    }
    
    /**
     * Unikatowy identyfikator
     * 
     * @return string
     */
    public static function getIdentifier()
    {
        return 'push';
    }
}
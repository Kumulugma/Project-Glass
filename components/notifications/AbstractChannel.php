<?php

namespace app\components\channels;

use Yii;
use app\models\Task;
use app\models\NotificationQueue;

/**
 * Abstrakcyjna klasa bazowa dla channeli powiadomień
 * Implementuje wspólną logikę: cooldown, walidację, itp.
 */
abstract class AbstractChannel implements NotificationChannel
{
    /**
     * Wysyła powiadomienie
     * 
     * @param NotificationQueue $notification
     * @return array ['success' => bool, 'response' => mixed, 'error' => string|null]
     */
    abstract public function send(NotificationQueue $notification);
    
    /**
     * Walidacja konfiguracji kanału
     * 
     * @return array|true Tablica błędów lub true jeśli OK
     */
    abstract public function validateConfig();
    
    /**
     * Czy kanał jest dostępny (skonfigurowany)
     * 
     * @return bool
     */
    abstract public function isAvailable();
    
    /**
     * Zwraca unikalny identyfikator channela
     * 
     * @return string
     */
    public static function getIdentifier()
    {
        $className = basename(str_replace('\\', '/', static::class));
        // Usuń "Channel" z końca jeśli istnieje
        return strtolower(str_replace('Channel', '', $className));
    }
    
    /**
     * Zwraca nazwę wyświetlaną channela
     * 
     * @return string
     */
    public static function getDisplayName()
    {
        return 'Channel';
    }
    
    /**
     * Zwraca opis channela
     * 
     * @return string
     */
    public static function getDescription()
    {
        return '';
    }
    
    /**
     * Zwraca definicję pól konfiguracyjnych
     * 
     * @return array
     */
    public static function getConfigFields()
    {
        return [];
    }
    
    /**
     * Sprawdza czy można teraz wysłać powiadomienie (cooldown)
     * 
     * @param Task $task
     * @return bool
     */
    public function canSendNow(Task $task)
    {
        if (!$task->last_notification_at) {
            return true; // Nigdy nie wysłano powiadomienia
        }
        
        $cooldownMinutes = $this->getCooldownMinutes();
        $lastNotificationTime = strtotime($task->last_notification_at);
        $elapsedSeconds = time() - $lastNotificationTime;
        
        return $elapsedSeconds >= ($cooldownMinutes * 60);
    }
    
    /**
     * Pobiera cooldown w minutach z ustawień
     * 
     * @return int
     */
    protected function getCooldownMinutes()
    {
        $settingKey = 'channel_cooldown_' . static::getIdentifier();
        $cooldown = Yii::$app->params[$settingKey] ?? null;
        
        if ($cooldown === null) {
            // Fallback do settings z bazy danych
            $setting = \app\models\Setting::findOne(['setting_key' => $settingKey]);
            $cooldown = $setting ? (int)$setting->setting_value : $this->getDefaultCooldown();
        }
        
        return (int)$cooldown;
    }
    
    /**
     * Zwraca domyślny cooldown w minutach
     * 
     * @return int
     */
    protected function getDefaultCooldown()
    {
        return 60; // Domyślnie 60 minut
    }
    
    /**
     * Pobiera konfigurację channela z params lub bazy danych
     * 
     * @param string $key Klucz konfiguracji
     * @param mixed $default Wartość domyślna
     * @return mixed
     */
    protected function getConfig($key, $default = null)
    {
        $settingKey = 'channel_' . static::getIdentifier() . '_' . $key;
        
        // Najpierw sprawdź params
        if (isset(Yii::$app->params[$settingKey])) {
            return Yii::$app->params[$settingKey];
        }
        
        // Potem settings z bazy
        $setting = \app\models\Setting::findOne(['setting_key' => $settingKey]);
        if ($setting) {
            return $setting->setting_value;
        }
        
        return $default;
    }
    
    /**
     * Loguje błąd wysyłki
     * 
     * @param NotificationQueue $notification
     * @param string $error
     */
    protected function logError(NotificationQueue $notification, $error)
    {
        Yii::error([
            'message' => 'Channel send failed',
            'channel' => static::getIdentifier(),
            'notification_id' => $notification->id,
            'task_id' => $notification->task_id,
            'recipient' => $notification->recipient,
            'error' => $error,
        ], __METHOD__);
    }
    
    /**
     * Loguje sukces wysyłki
     * 
     * @param NotificationQueue $notification
     * @param mixed $response
     */
    protected function logSuccess(NotificationQueue $notification, $response = null)
    {
        Yii::info([
            'message' => 'Channel send success',
            'channel' => static::getIdentifier(),
            'notification_id' => $notification->id,
            'task_id' => $notification->task_id,
            'recipient' => $notification->recipient,
            'response' => $response,
        ], __METHOD__);
    }
}
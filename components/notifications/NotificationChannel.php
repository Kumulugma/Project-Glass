<?php

namespace app\components\notifications;

use app\models\NotificationQueue;

/**
 * Interfejs dla kanałów powiadomień
 */
interface NotificationChannel
{
    /**
     * Wysyła powiadomienie
     * 
     * @param NotificationQueue $notification
     * @return array Wynik wysyłki: ['success' => bool, 'response' => mixed, 'error' => string|null]
     */
    public function send(NotificationQueue $notification);
    
    /**
     * Waliduje konfigurację kanału
     * 
     * @return array|true Tablica błędów lub true jeśli OK
     */
    public function validateConfig();
    
    /**
     * Czy kanał jest dostępny (skonfigurowany)
     * 
     * @return bool
     */
    public function isAvailable();
}

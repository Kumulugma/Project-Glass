<?php
// models/PushSubscription.php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Model dla przechowywania subskrypcji Web Push
 * 
 * @property int $id
 * @property int $user_id
 * @property string $endpoint
 * @property string $public_key
 * @property string $auth_token
 * @property string $user_agent
 * @property string $device_name
 * @property bool $is_active
 * @property string $last_used_at
 * @property string $failed_at
 * @property string $failure_reason
 * @property int $created_at
 * @property int $updated_at
 */
class PushSubscription extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%push_subscriptions}}';
    }
    
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }
    
    public function rules()
    {
        return [
            [['endpoint'], 'required'],
            [['user_id'], 'integer'],
            [['endpoint', 'user_agent', 'failure_reason'], 'string'],
            [['public_key', 'auth_token'], 'string', 'max' => 255],
            [['device_name'], 'string', 'max' => 100],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
            [['last_used_at', 'failed_at'], 'safe'],
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Użytkownik',
            'endpoint' => 'Endpoint',
            'public_key' => 'Klucz publiczny',
            'auth_token' => 'Token autoryzacyjny',
            'user_agent' => 'Przeglądarka',
            'device_name' => 'Nazwa urządzenia',
            'is_active' => 'Aktywna',
            'last_used_at' => 'Ostatnie użycie',
            'failed_at' => 'Błąd',
            'failure_reason' => 'Powód błędu',
            'created_at' => 'Utworzono',
            'updated_at' => 'Zaktualizowano',
        ];
    }
    
    /**
     * Znajduje aktywne subskrypcje
     * 
     * @return PushSubscription[]
     */
    public static function findActive()
    {
        return static::find()
            ->where(['is_active' => true])
            ->andWhere(['or', ['failed_at' => null], ['<', 'failed_at', date('Y-m-d H:i:s', strtotime('-7 days'))]])
            ->all();
    }
    
    /**
     * Oznacz jako nieaktywną
     * 
     * @param string $reason
     * @return bool
     */
    public function markAsInactive($reason = null)
    {
        $this->is_active = false;
        $this->failed_at = date('Y-m-d H:i:s');
        $this->failure_reason = $reason;
        
        if ($reason) {
            Yii::info("Push subscription {$this->id} marked as inactive: {$reason}", __METHOD__);
        }
        
        return $this->save(false);
    }
    
    /**
     * Aktualizuj czas ostatniego użycia
     * 
     * @return bool
     */
    public function updateLastSentAt()
    {
        $this->last_used_at = date('Y-m-d H:i:s');
        return $this->save(false, ['last_used_at', 'updated_at']);
    }
    
    /**
     * Oznacz jako ponownie aktywną (np. po naprawie)
     * 
     * @return bool
     */
    public function markAsActive()
    {
        $this->is_active = true;
        $this->failed_at = null;
        $this->failure_reason = null;
        
        return $this->save(false);
    }
    
    /**
     * Konwertuj do formatu dla web-push-php
     * 
     * @return array
     */
    public function toWebPushFormat()
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->public_key,
                'auth' => $this->auth_token,
            ],
        ];
    }
    
    /**
     * Utwórz lub zaktualizuj subskrypcję
     * 
     * @param array $subscriptionData
     * @param int|null $userId
     * @param string|null $deviceName Email lub nazwa urządzenia
     * @return PushSubscription|null
     */
    public static function createOrUpdate($subscriptionData, $userId = null, $deviceName = null)
    {
        $endpoint = $subscriptionData['endpoint'] ?? null;
        
        if (!$endpoint) {
            return null;
        }
        
        // Znajdź istniejącą lub utwórz nową
        $subscription = static::findOne(['endpoint' => $endpoint]);
        
        if (!$subscription) {
            $subscription = new static();
            $subscription->endpoint = $endpoint;
        }
        
        // Aktualizuj dane
        $subscription->user_id = $userId;
        $subscription->public_key = $subscriptionData['keys']['p256dh'] ?? null;
        $subscription->auth_token = $subscriptionData['keys']['auth'] ?? null;
        $subscription->is_active = true;
        $subscription->failed_at = null;
        $subscription->failure_reason = null;
        
        // Device name (email lub nazwa)
        if ($deviceName) {
            $subscription->device_name = $deviceName;
        }
        
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $subscription->user_agent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        if ($subscription->save()) {
            return $subscription;
        }
        
        Yii::error("Failed to save push subscription: " . print_r($subscription->errors, true), __METHOD__);
        return null;
    }
}
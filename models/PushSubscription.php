<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Model PushSubscription
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $endpoint
 * @property string|null $public_key
 * @property string|null $auth_token
 * @property string|null $user_agent
 * @property string|null $device_name
 * @property bool $is_active
 * @property string|null $last_used_at
 * @property string|null $failed_at
 * @property string|null $failure_reason
 * @property int $created_at
 * @property int $updated_at
 */
class PushSubscription extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%push_subscriptions}}';
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['endpoint'], 'required'],
            [['user_id'], 'integer'],
            [['endpoint', 'user_agent', 'failure_reason'], 'string'],
            [['public_key', 'auth_token'], 'string', 'max' => 255],
            [['device_name'], 'string', 'max' => 100],
            [['is_active'], 'boolean'],
            [['last_used_at', 'failed_at'], 'safe'],
            [['endpoint'], 'unique'],
            
            // Domyślne wartości
            [['is_active'], 'default', 'value' => true],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'endpoint' => 'Endpoint',
            'device_name' => 'Urządzenie',
            'is_active' => 'Aktywna',
            'created_at' => 'Utworzono',
        ];
    }
    
    /**
     * Tworzy lub aktualizuje subskrypcję
     */
    public static function createOrUpdate($subscriptionData, $userId = null)
    {
        $endpoint = $subscriptionData['endpoint'] ?? null;
        if (!$endpoint) {
            throw new \Exception('Brak endpointu w danych subskrypcji');
        }
        
        // Szukaj istniejącej
        $subscription = self::findOne(['endpoint' => $endpoint]);
        if (!$subscription) {
            $subscription = new self();
            $subscription->endpoint = $endpoint;
        }
        
        // Aktualizuj dane
        $subscription->user_id = $userId;
        $subscription->public_key = $subscriptionData['keys']['p256dh'] ?? null;
        $subscription->auth_token = $subscriptionData['keys']['auth'] ?? null;
        $subscription->is_active = true;
        $subscription->last_used_at = date('Y-m-d H:i:s');
        
        // User agent z request
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $subscription->user_agent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        $subscription->save();
        
        return $subscription;
    }
    
    /**
     * Oznacza jako nieaktywną
     */
    public function markAsInactive($reason = null)
    {
        $this->is_active = false;
        $this->failed_at = date('Y-m-d H:i:s');
        $this->failure_reason = $reason;
        $this->save();
    }
    
    /**
     * Aktualizuje last_used_at
     */
    public function touch()
    {
        $this->last_used_at = date('Y-m-d H:i:s');
        $this->save(false, ['last_used_at']);
    }
    
    /**
     * Zwraca wszystkie aktywne subskrypcje
     */
    public static function findActive($userId = null)
    {
        $query = self::find()->where(['is_active' => true]);
        
        if ($userId !== null) {
            $query->andWhere(['user_id' => $userId]);
        }
        
        return $query->all();
    }
    
    /**
     * Zwraca dane subskrypcji w formacie dla web-push library
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
}

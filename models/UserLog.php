<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model UserLog - logi aktywności użytkowników
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property string|null $description
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int $created_at
 * 
 * @property User $user
 */
class UserLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_logs}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['action'], 'required'],
            [['user_id', 'entity_id', 'created_at'], 'integer'],
            [['description', 'user_agent'], 'string'],
            [['action'], 'string', 'max' => 100],
            [['entity_type'], 'string', 'max' => 50],
            [['ip_address'], 'string', 'max' => 45],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Użytkownik',
            'action' => 'Akcja',
            'entity_type' => 'Typ',
            'entity_id' => 'ID Encji',
            'description' => 'Opis',
            'ip_address' => 'Adres IP',
            'created_at' => 'Data',
        ];
    }
    
    /**
     * Relacje
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
    
    /**
     * Loguje akcję użytkownika
     */
    public static function log($action, $description = null, $entityType = null, $entityId = null)
    {
        $log = new self();
        $log->user_id = Yii::$app->user->id ?? null;
        $log->action = $action;
        $log->description = $description;
        $log->entity_type = $entityType;
        $log->entity_id = $entityId;
        $log->ip_address = Yii::$app->request->userIP;
        $log->user_agent = Yii::$app->request->userAgent;
        $log->created_at = time();
        $log->save();
        
        return $log;
    }
    
    /**
     * Pomocnicze metody do logowania konkretnych akcji
     */
    public static function logLogin($userId)
    {
        $log = new self();
        $log->user_id = $userId;
        $log->action = 'login';
        $log->description = 'Użytkownik zalogował się do systemu';
        $log->ip_address = Yii::$app->request->userIP;
        $log->user_agent = Yii::$app->request->userAgent;
        $log->created_at = time();
        $log->save();
    }
    
    public static function logLogout($userId)
    {
        $log = new self();
        $log->user_id = $userId;
        $log->action = 'logout';
        $log->description = 'Użytkownik wylogował się z systemu';
        $log->ip_address = Yii::$app->request->userIP;
        $log->user_agent = Yii::$app->request->userAgent;
        $log->created_at = time();
        $log->save();
    }
    
    /**
     * Zwraca czytelny opis akcji
     */
    public function getActionLabel()
    {
        $labels = [
            'login' => '🔓 Logowanie',
            'logout' => '🔒 Wylogowanie',
            'create_task' => '➕ Utworzenie zadania',
            'update_task' => '✏️ Edycja zadania',
            'delete_task' => '🗑️ Usunięcie zadania',
            'run_task' => '▶️ Uruchomienie zadania',
            'complete_task' => '✓ Oznaczenie jako wykonane',
            'pause_task' => '⏸ Wstrzymanie zadania',
            'resume_task' => '▶️ Wznowienie zadania',
        ];
        
        return $labels[$this->action] ?? $this->action;
    }
}
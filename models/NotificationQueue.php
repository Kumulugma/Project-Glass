<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Model NotificationQueue
 *
 * @property int $id
 * @property int $task_id
 * @property int|null $execution_id
 * @property string $type
 * @property string $channel
 * @property string $recipient
 * @property string|null $subject
 * @property string $message
 * @property string|null $data
 * @property string $status
 * @property int $attempts
 * @property int $max_attempts
 * @property int $priority
 * @property int $scheduled_for
 * @property int|null $sent_at
 * @property string|null $error_message
 * @property string|null $response_data
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Task $task
 * @property TaskExecution $execution
 */
class NotificationQueue extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%notification_queue}}';
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
            [['task_id', 'recipient', 'message', 'scheduled_for'], 'required'],
            [['task_id', 'execution_id', 'attempts', 'max_attempts', 'priority', 'scheduled_for', 'sent_at'], 'integer'],
            [['type'], 'in', 'range' => ['alert', 'reminder', 'report']],
            [['channel'], 'in', 'range' => ['email', 'sms', 'push', 'telegram', 'messenger']],
            [['status'], 'in', 'range' => ['pending', 'processing', 'sent', 'failed', 'cancelled']],
            [['recipient', 'subject'], 'string', 'max' => 255],
            [['message', 'data', 'error_message', 'response_data'], 'string'],
            
            // Domyślne wartości
            [['type'], 'default', 'value' => 'alert'],
            [['channel'], 'default', 'value' => 'email'],
            [['status'], 'default', 'value' => 'pending'],
            [['attempts'], 'default', 'value' => 0],
            [['max_attempts'], 'default', 'value' => 3],
            [['priority'], 'default', 'value' => 5],
            [['scheduled_for'], 'default', 'value' => function() { return time(); }],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task',
            'type' => 'Typ',
            'channel' => 'Kanał',
            'recipient' => 'Odbiorca',
            'subject' => 'Temat',
            'message' => 'Wiadomość',
            'status' => 'Status',
            'attempts' => 'Próby',
            'priority' => 'Priorytet',
            'scheduled_for' => 'Zaplanowane na',
            'sent_at' => 'Wysłano',
            'created_at' => 'Utworzono',
        ];
    }
    
    /**
     * Relacje
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }
    
    public function getExecution()
    {
        return $this->hasOne(TaskExecution::class, ['id' => 'execution_id']);
    }
    
    /**
     * Zwraca dodatkowe dane jako tablicę
     */
    public function getDataArray()
    {
        return $this->data ? json_decode($this->data, true) : [];
    }
    
    /**
     * Tworzy nowe powiadomienie
     */
    public static function create($taskId, $executionId, $notification, $task)
    {
        // Pobierz kanały i odbiorców z taska
        $channels = json_decode($task->notification_channels, true) ?: ['email'];
        $recipients = json_decode($task->notification_recipients, true) ?: [];
        
        // Jeśli nie ma odbiorców, użyj domyślnego z params
        if (empty($recipients)) {
            $recipients = [Yii::$app->params['adminEmail']];
        }
        
        // Utwórz powiadomienie dla każdego kanału i odbiorcy
        $created = [];
        foreach ($channels as $channel) {
            foreach ($recipients as $recipient) {
                $queue = new self();
                $queue->task_id = $taskId;
                $queue->execution_id = $executionId;
                $queue->type = $notification['type'] ?? 'alert';
                $queue->channel = $channel;
                $queue->recipient = $recipient;
                $queue->subject = $notification['subject'] ?? null;
                $queue->message = $notification['message'];
                $queue->priority = $notification['priority'] ?? 5;
                $queue->data = isset($notification['data']) ? json_encode($notification['data']) : null;
                $queue->scheduled_for = $notification['scheduled_for'] ?? time();
                $queue->save();
                
                $created[] = $queue;
            }
        }
        
        return $created;
    }
    
    /**
     * Oznacza jako wysłane
     */
    public function markAsSent($responseData = null)
    {
        $this->status = 'sent';
        $this->sent_at = time();
        if ($responseData) {
            $this->response_data = json_encode($responseData);
        }
        $this->save();
    }
    
    /**
     * Oznacza jako nieudane
     */
    public function markAsFailed($error)
    {
        $this->status = 'failed';
        $this->attempts++;
        $this->error_message = $error instanceof \Exception ? $error->getMessage() : (string)$error;
        $this->save();
    }
    
    /**
     * Oznacza jako przetwarzane
     */
    public function markAsProcessing()
    {
        $this->status = 'processing';
        $this->attempts++;
        $this->save();
    }
    
    /**
     * Czy można ponowić wysyłkę
     */
    public function canRetry()
    {
        return $this->attempts < $this->max_attempts;
    }
    
    /**
     * Czy czas na wysłanie
     */
    public function isReadyToSend()
    {
        return $this->scheduled_for <= time();
    }
    
    /**
     * Query scope: gotowe do wysłania
     */
    public static function findReadyToSend($limit = 50)
    {
        return self::find()
            ->where(['status' => 'pending'])
            ->andWhere(['<=', 'scheduled_for', time()])
            ->orderBy(['priority' => SORT_ASC, 'created_at' => SORT_ASC])
            ->limit($limit)
            ->all();
    }
}

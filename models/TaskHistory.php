<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model TaskHistory
 *
 * @property int $id
 * @property int $task_id
 * @property string $action
 * @property string|null $old_values
 * @property string|null $new_values
 * @property string|null $changed_fields
 * @property string|null $user_ip
 * @property string|null $user_agent
 * @property int $created_at
 *
 * @property Task $task
 */
class TaskHistory extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%task_history}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task_id', 'created_at'], 'required'],
            [['task_id', 'created_at'], 'integer'],
            [['action'], 'in', 'range' => ['created', 'updated', 'deleted', 'completed', 'paused', 'resumed']],
            [['old_values', 'new_values', 'changed_fields', 'user_agent'], 'string'],
            [['user_ip'], 'string', 'max' => 45],
            
            // Domyślne wartości
            [['action'], 'default', 'value' => 'updated'],
            [['created_at'], 'default', 'value' => function() { return time(); }],
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
            'action' => 'Akcja',
            'created_at' => 'Data',
        ];
    }
    
    /**
     * Relacja z taskiem
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }
    
    /**
     * Zwraca stare wartości jako tablicę
     */
    public function getOldValuesArray()
    {
        return $this->old_values ? json_decode($this->old_values, true) : [];
    }
    
    /**
     * Zwraca nowe wartości jako tablicę
     */
    public function getNewValuesArray()
    {
        return $this->new_values ? json_decode($this->new_values, true) : [];
    }
    
    /**
     * Zwraca zmienione pola jako tablicę
     */
    public function getChangedFieldsArray()
    {
        return $this->changed_fields ? json_decode($this->changed_fields, true) : [];
    }
    
    /**
     * Zapisuje zmianę w historii
     */
    public static function recordChange(Task $task, $action, $changedAttributes = [])
    {
        $history = new self();
        $history->task_id = $task->id;
        $history->action = $action;
        
        // Zapisz stare i nowe wartości
        if (!empty($changedAttributes)) {
            $oldValues = [];
            $newValues = [];
            
            foreach ($changedAttributes as $attribute => $oldValue) {
                $oldValues[$attribute] = $oldValue;
                $newValues[$attribute] = $task->$attribute;
            }
            
            $history->old_values = json_encode($oldValues);
            $history->new_values = json_encode($newValues);
            $history->changed_fields = json_encode(array_keys($changedAttributes));
        }
        
        // Zapisz IP i User Agent
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $history->user_ip = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $history->user_agent = $_SERVER['HTTP_USER_AGENT'];
        }
        
        $history->save();
        
        return $history;
    }
    
    /**
     * Zwraca historię dla taska
     */
    public static function findForTask($taskId, $limit = 50)
    {
        return self::find()
            ->where(['task_id' => $taskId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
    
    /**
     * Zwraca czytelny opis zmiany
     */
    public function getDescription()
    {
        $actions = [
            'created' => 'Utworzono',
            'updated' => 'Zaktualizowano',
            'deleted' => 'Usunięto',
            'completed' => 'Oznaczono jako wykonane',
            'paused' => 'Wstrzymano',
            'resumed' => 'Wznowiono',
        ];
        
        $description = $actions[$this->action] ?? $this->action;
        
        if ($this->action === 'updated' && $this->changed_fields) {
            $fields = $this->getChangedFieldsArray();
            if (!empty($fields)) {
                $description .= ': ' . implode(', ', $fields);
            }
        }
        
        return $description;
    }
}

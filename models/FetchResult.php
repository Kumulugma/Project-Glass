<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model FetchResult
 *
 * @property int $id
 * @property int $task_id
 * @property int|null $execution_id
 * @property string $fetcher_class
 * @property string|null $source_info
 * @property string|null $raw_data
 * @property int|null $data_size
 * @property int|null $rows_count
 * @property string $status
 * @property string|null $error_message
 * @property int $fetched_at
 *
 * @property Task $task
 * @property TaskExecution $execution
 */
class FetchResult extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%fetch_results}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task_id', 'fetcher_class', 'fetched_at'], 'required'],
            [['task_id', 'execution_id', 'data_size', 'rows_count', 'fetched_at'], 'integer'],
            [['fetcher_class'], 'string', 'max' => 255],
            [['source_info', 'raw_data', 'error_message'], 'string'],
            [['status'], 'in', 'range' => ['success', 'failed', 'partial']],
            [['status'], 'default', 'value' => 'success'],
            [['fetched_at'], 'default', 'value' => function() { return time(); }],
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
            'execution_id' => 'Execution',
            'fetcher_class' => 'Fetcher',
            'source_info' => 'Źródło',
            'data_size' => 'Rozmiar danych',
            'rows_count' => 'Liczba wierszy',
            'status' => 'Status',
            'error_message' => 'Błąd',
            'fetched_at' => 'Pobrano',
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
     * Zwraca source_info jako tablicę
     */
    public function getSourceInfoArray()
    {
        return $this->source_info ? json_decode($this->source_info, true) : [];
    }
    
    /**
     * Zwraca raw_data jako tablicę (jeśli to JSON)
     */
    public function getRawDataArray()
    {
        if (!$this->raw_data) return [];
        
        $decoded = json_decode($this->raw_data, true);
        return is_array($decoded) ? $decoded : [];
    }
    
    /**
     * Tworzy nowy wynik fetch
     * 
     * @param int $taskId
     * @param int|null $executionId
     * @param string $fetcherClass
     * @param array $fetchData Dane z fetchera
     * @return static
     */
    public static function create($taskId, $executionId, $fetcherClass, $fetchData)
    {
        $result = new static();
        $result->task_id = $taskId;
        $result->execution_id = $executionId;
        $result->fetcher_class = $fetcherClass;
        
        // Zapisz source info
        if (isset($fetchData['url'])) {
            $result->source_info = json_encode(['url' => $fetchData['url']]);
        } elseif (isset($fetchData['source'])) {
            $result->source_info = json_encode(['source' => $fetchData['source']]);
        }
        
        // Zapisz raw data
        if (isset($fetchData['data'])) {
            $result->raw_data = is_string($fetchData['data']) ? $fetchData['data'] : json_encode($fetchData['data']);
        } elseif (isset($fetchData['response'])) {
            $result->raw_data = is_string($fetchData['response']) ? $fetchData['response'] : json_encode($fetchData['response']);
        }
        
        // Oblicz rozmiar
        if ($result->raw_data) {
            $result->data_size = strlen($result->raw_data);
        }
        
        // Liczba wierszy
        if (isset($fetchData['rows_count'])) {
            $result->rows_count = $fetchData['rows_count'];
        } elseif (isset($fetchData['data']) && is_array($fetchData['data'])) {
            $result->rows_count = count($fetchData['data']);
        }
        
        // Status
        $result->status = ($fetchData['success'] ?? true) ? 'success' : 'failed';
        
        // Error message
        if (isset($fetchData['error']) && $fetchData['error']) {
            $result->error_message = $fetchData['error'];
            $result->status = 'failed';
        }
        
        $result->fetched_at = $fetchData['timestamp'] ?? $fetchData['fetched_at'] ?? time();
        
        $result->save();
        
        return $result;
    }
    
    /**
     * Zwraca ostatnie wyniki dla taska
     * 
     * @param int $taskId
     * @param int $limit
     * @return static[]
     */
    public static function findLastForTask($taskId, $limit = 10)
    {
        return static::find()
            ->where(['task_id' => $taskId])
            ->orderBy(['fetched_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
    
    /**
     * Czyści stare wyniki (starsze niż X dni)
     * 
     * @param int $days Liczba dni
     * @return int Liczba usuniętych rekordów
     */
    public static function cleanup($days = 30)
    {
        $threshold = time() - ($days * 86400);
        
        return static::deleteAll(['<', 'fetched_at', $threshold]);
    }
    
    /**
     * Czy fetch był udany
     */
    public function isSuccess()
    {
        return $this->status === 'success';
    }
    
    /**
     * Czy fetch się nie powiódł
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }
}
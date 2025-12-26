<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model TaskExecution
 *
 * @property int $id
 * @property int $task_id
 * @property string $status
 * @property string $stage
 * @property string|null $raw_data
 * @property string|null $parsed_data
 * @property string|null $evaluation_result
 * @property string|null $error_message
 * @property string|null $error_trace
 * @property int $started_at
 * @property int|null $finished_at
 * @property int|null $duration_ms
 *
 * @property Task $task
 */
class TaskExecution extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%task_executions}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task_id', 'started_at'], 'required'],
            [['task_id', 'started_at', 'finished_at', 'duration_ms'], 'integer'],
            [['status'], 'in', 'range' => ['running', 'success', 'failed', 'skipped']],
            [['stage'], 'in', 'range' => ['fetch', 'parse', 'evaluate', 'notify', 'completed']],
            [['raw_data', 'parsed_data', 'evaluation_result', 'error_message', 'error_trace'], 'string'],
            
            // Domyślne wartości
            [['status'], 'default', 'value' => 'running'],
            [['stage'], 'default', 'value' => 'fetch'],
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
            'status' => 'Status',
            'stage' => 'Etap',
            'started_at' => 'Rozpoczęto',
            'finished_at' => 'Zakończono',
            'duration_ms' => 'Czas (ms)',
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
     * Zwraca surowe dane jako tablicę
     */
    public function getRawDataArray()
    {
        return $this->raw_data ? json_decode($this->raw_data, true) : [];
    }
    
    /**
     * Zwraca przetworzone dane jako tablicę
     */
    public function getParsedDataArray()
    {
        return $this->parsed_data ? json_decode($this->parsed_data, true) : [];
    }
    
    /**
     * Zwraca wynik ewaluacji jako tablicę
     */
    public function getEvaluationResultArray()
    {
        return $this->evaluation_result ? json_decode($this->evaluation_result, true) : [];
    }
    
    /**
     * Rozpoczyna wykonanie
     */
    public static function start($taskId)
    {
        $execution = new self();
        $execution->task_id = $taskId;
        $execution->status = 'running';
        $execution->stage = 'fetch';
        $execution->started_at = time();
        $execution->save();
        
        return $execution;
    }
    
    /**
     * Kończy wykonanie jako sukces
     */
    public function complete()
    {
        $this->status = 'success';
        $this->stage = 'completed';
        $this->finished_at = time();
        $this->duration_ms = ($this->finished_at - $this->started_at) * 1000;
        $this->save();
    }
    
    /**
     * Kończy wykonanie jako błąd
     */
    public function fail($error)
    {
        $this->status = 'failed';
        $this->error_message = $error instanceof \Exception ? $error->getMessage() : (string)$error;
        $this->error_trace = $error instanceof \Exception ? $error->getTraceAsString() : '';
        $this->finished_at = time();
        $this->duration_ms = ($this->finished_at - $this->started_at) * 1000;
        $this->save();
    }
    
    /**
     * Pomija wykonanie
     */
    public function skip($reason = null)
    {
        $this->status = 'skipped';
        $this->error_message = $reason;
        $this->finished_at = time();
        $this->duration_ms = ($this->finished_at - $this->started_at) * 1000;
        $this->save();
    }
    
    /**
     * Aktualizuje stage
     */
    public function setStage($stage)
    {
        $this->stage = $stage;
        $this->save(false, ['stage']);
    }
    
    /**
     * Zapisuje surowe dane
     */
    public function saveRawData($data)
    {
        $this->raw_data = json_encode($data);
        $this->save(false, ['raw_data']);
    }
    
    /**
     * Zapisuje przetworzone dane
     */
    public function saveParsedData($data)
    {
        $this->parsed_data = json_encode($data);
        $this->save(false, ['parsed_data']);
    }
    
    /**
     * Zapisuje wynik ewaluacji
     */
    public function saveEvaluationResult($result)
    {
        $this->evaluation_result = json_encode($result);
        $this->save(false, ['evaluation_result']);
    }
    
    /**
     * Czy wykonanie się powiodło
     */
    public function isSuccess()
    {
        return $this->status === 'success';
    }
    
    /**
     * Czy wykonanie się nie powiodło
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }
    
    /**
     * Zwraca czas trwania w sekundach
     */
    public function getDurationSeconds()
    {
        return $this->duration_ms ? round($this->duration_ms / 1000, 2) : null;
    }
}

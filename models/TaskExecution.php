<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Model TaskExecution - historia wykonań tasków
 *
 * @property int $id
 * @property int $task_id
 * @property string $status
 * @property string $current_stage
 * @property string $started_at
 * @property string $finished_at
 * @property string $error_message
 * @property string $raw_data
 * @property string $parsed_data
 * @property string $evaluation_result
 * @property int $created_at
 * @property int $updated_at
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
        [['task_id', 'status'], 'required'],
        [['task_id', 'started_at', 'finished_at', 'duration_ms'], 'integer'],
        [['status'], 'in', 'range' => ['running', 'success', 'failed', 'skipped']],
        [['stage'], 'in', 'range' => ['fetch', 'parse', 'evaluate', 'notify', 'completed']], // ← Dodaj tę linię
        [['stage'], 'string', 'max' => 50], // ← I tę
        [['raw_data', 'parsed_data', 'evaluation_result', 'error_message', 'error_trace'], 'string'],
    ];
}
    
    /**
 * @inheritdoc
 */
public function fields()
{
    return [
        'id',
        'task_id',
        'status',
        'stage', // ← Upewnij się że to jest 'stage', nie 'current_stage'
        'started_at',
        'finished_at',
        'duration_ms',
        'raw_data',
        'parsed_data',
        'evaluation_result',
        'error_message',
        'error_trace',
    ];
}

    public function attributeLabels()
{
    return [
        'id' => 'ID',
        'task_id' => 'Zadanie',
        'status' => 'Status',
        'stage' => 'Obecny etap', // ← ZMIEŃ z 'current_stage' na 'stage'
        'started_at' => 'Rozpoczęto',
        'finished_at' => 'Zakończono',
        'error_message' => 'Komunikat błędu',
        'error_trace' => 'Ślad błędu',
        'raw_data' => 'Surowe dane',
        'parsed_data' => 'Przetworzone dane',
        'evaluation_result' => 'Wynik ewaluacji',
        'duration_ms' => 'Czas wykonania (ms)',
    ];
}

    /**
     * Relacja do Task
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    /**
     * Zwraca czas trwania wykonania w sekundach
     *
     * @return float|null
     */
    public function getDuration()
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        $start = strtotime($this->started_at);
        $end = strtotime($this->finished_at);

        return round($end - $start, 2);
    }

    /**
     * Sprawdza czy wykonanie było udane
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->status === 'success';
    }

    /**
     * Sprawdza czy wykonanie się nie powiodło
     *
     * @return bool
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Sprawdza czy wykonanie jest w trakcie
     *
     * @return bool
     */
    public function isRunning()
    {
        return $this->status === 'running';
    }

    /**
     * Zwraca czytelny status
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            'running' => 'W trakcie',
            'success' => 'Sukces',
            'failed' => 'Błąd',
            'partial' => 'Częściowy',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Zwraca status z ikoną (HTML)
     *
     * @return string
     */
    public function getStatusBadge()
    {
        $badges = [
            'running' => '<span class="badge bg-info">W trakcie</span>',
            'success' => '<span class="badge bg-success">Sukces</span>',
            'failed' => '<span class="badge bg-danger">Błąd</span>',
            'partial' => '<span class="badge bg-warning">Częściowy</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . $this->status . '</span>';
    }

/**
 * Ustawia stage wykonania
 *
 * @param string $stage
 * @return bool
 */
public function setStage($stage)
{
    $this->stage = $stage; // ← ZMIEŃ z 'current_stage' na 'stage'
    return $this->save(false, ['stage', 'updated_at']);
}

    /**
     * Zapisuje surowe dane z fetchera
     *
     * @param mixed $data
     * @return bool
     */
    public function saveRawData($data)
    {
        $this->raw_data = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $data;
        return $this->save(false, ['raw_data', 'updated_at']);
    }

    /**
     * Zapisuje przetworzone dane z parsera
     *
     * @param mixed $data
     * @return bool
     */
    public function saveParsedData($data)
    {
        $this->parsed_data = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $data;
        return $this->save(false, ['parsed_data', 'updated_at']);
    }

    /**
     * Zapisuje wynik ewaluacji
     *
     * @param mixed $result
     * @return bool
     */
    public function saveEvaluationResult($result)
    {
        $this->evaluation_result = is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $result;
        return $this->save(false, ['evaluation_result', 'updated_at']);
    }

    /**
     * Oznacz jako sukces
     *
     * @return bool
     */
    public function markAsSuccess()
    {
        $this->status = 'success';
        $this->finished_at = date('Y-m-d H:i:s');
        return $this->save(false, ['status', 'finished_at', 'updated_at']);
    }

    /**
     * Oznacz jako błąd
     *
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed($errorMessage)
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->finished_at = date('Y-m-d H:i:s');
        return $this->save(false, ['status', 'error_message', 'finished_at', 'updated_at']);
    }

    /**
     * Oznacz jako częściowy sukces
     *
     * @param string $message
     * @return bool
     */
    public function markAsPartial($message = null)
    {
        $this->status = 'partial';
        if ($message) {
            $this->error_message = $message;
        }
        $this->finished_at = date('Y-m-d H:i:s');
        return $this->save(false, ['status', 'error_message', 'finished_at', 'updated_at']);
    }

    /**
     * Pobiera surowe dane jako array
     *
     * @return array|null
     */
    public function getRawDataArray()
    {
        if (!$this->raw_data) {
            return null;
        }

        if (is_array($this->raw_data)) {
            return $this->raw_data;
        }

        $decoded = json_decode($this->raw_data, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * Pobiera przetworzone dane jako array
     *
     * @return array|null
     */
    public function getParsedDataArray()
    {
        if (!$this->parsed_data) {
            return null;
        }

        if (is_array($this->parsed_data)) {
            return $this->parsed_data;
        }

        $decoded = json_decode($this->parsed_data, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * Pobiera wynik ewaluacji jako array
     *
     * @return array|null
     */
    public function getEvaluationResultArray()
    {
        if (!$this->evaluation_result) {
            return null;
        }

        if (is_array($this->evaluation_result)) {
            return $this->evaluation_result;
        }

        $decoded = json_decode($this->evaluation_result, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * Tworzy nowe wykonanie dla taska
     *
     * @param int $taskId
     * @return TaskExecution
     */
    public static function create($taskId)
    {
        $execution = new self();
        $execution->task_id = $taskId;
        $execution->status = 'running';
        $execution->started_at = date('Y-m-d H:i:s');
        $execution->save();

        return $execution;
    }

    /**
     * Pobiera ostatnie wykonanie dla taska
     *
     * @param int $taskId
     * @return TaskExecution|null
     */
    public static function getLastForTask($taskId)
    {
        return self::find()
            ->where(['task_id' => $taskId])
            ->orderBy(['started_at' => SORT_DESC])
            ->one();
    }

    /**
     * Pobiera ostatnie N wykonań dla taska
     *
     * @param int $taskId
     * @param int $limit
     * @return TaskExecution[]
     */
    public static function getRecentForTask($taskId, $limit = 10)
    {
        return self::find()
            ->where(['task_id' => $taskId])
            ->orderBy(['started_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Pobiera wykonania z błędami dla taska
     *
     * @param int $taskId
     * @param int $limit
     * @return TaskExecution[]
     */
    public static function getFailedForTask($taskId, $limit = 10)
    {
        return self::find()
            ->where(['task_id' => $taskId, 'status' => 'failed'])
            ->orderBy(['started_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Czyści stare wykonania (starsze niż X dni)
     *
     * @param int $days Liczba dni do zachowania
     * @return int Liczba usuniętych rekordów
     */
    public static function cleanup($days = 30)
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return self::deleteAll(['<', 'created_at', strtotime($date)]);
    }

    /**
     * Zwraca czytelny opis czasu trwania
     *
     * @return string
     */
    public function getDurationFormatted()
    {
        $duration = $this->getDuration();

        if ($duration === null) {
            return 'N/A';
        }

        if ($duration < 1) {
            return round($duration * 1000) . 'ms';
        }

        if ($duration < 60) {
            return round($duration, 2) . 's';
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return "{$minutes}m " . round($seconds) . "s";
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Automatycznie ustaw finished_at jeśli status zmienił się na success/failed
            if (!$insert && in_array($this->status, ['success', 'failed', 'partial'])) {
                if (!$this->finished_at) {
                    $this->finished_at = date('Y-m-d H:i:s');
                }
            }

            return true;
        }

        return false;
    }
    
// ============================================================
// METODY STATYCZNE (FABRYKI) - używane przez TaskRunner
// ============================================================

/**
 * Rozpoczyna nowe wykonanie taska (STATYCZNA - tworzy obiekt)
 *
 * @param int $taskId
 * @return TaskExecution
 */
public static function start($taskId)
{
    $execution = new self();
    $execution->task_id = $taskId;
    $execution->status = 'running';
    $execution->stage = 'fetch';
    $execution->started_at = time();
    
    // Zapisz z walidacją, żeby task_id został uwzględniony
    if (!$execution->save()) {
        throw new \Exception('Failed to create TaskExecution: ' . json_encode($execution->errors));
    }
    
    return $execution;
}

// ============================================================
// METODY INSTANCYJNE - działają na istniejącym obiekcie
// ============================================================

/**
 * Rozpoczyna wykonanie (na istniejącym obiekcie)
 *
 * @return bool
 */
public function begin()
{
    $this->status = 'running';
    $this->started_at = date('Y-m-d H:i:s');
    return $this->save(false, ['status', 'started_at', 'updated_at']);
}

/**
 * Kończy wykonanie z sukcesem
 *
 * @return bool
 */
public function complete()
{
    $this->status = 'success';
    $this->finished_at = date('Y-m-d H:i:s');
    return $this->save(false, ['status', 'finished_at', 'updated_at']);
}

/**
 * Kończy wykonanie z błędem
 *
 * @param \Exception|string $error
 * @return bool
 */
public function fail($error)
{
    $this->status = 'failed';
    $this->finished_at = date('Y-m-d H:i:s');
    
    if ($error instanceof \Exception) {
        $this->error_message = $error->getMessage();
        
        // Opcjonalnie zapisz stack trace w logu
        Yii::error([
            'message' => 'Task execution failed',
            'task_id' => $this->task_id,
            'execution_id' => $this->id,
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString(),
        ], __METHOD__);
    } else {
        $this->error_message = (string)$error;
    }
    
    return $this->save(false, ['status', 'error_message', 'finished_at', 'updated_at']);
}
}
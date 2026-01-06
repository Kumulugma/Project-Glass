<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model S3Transfer - historia transferów na S3
 *
 * @property int $id
 * @property string $archive_type Typ archiwum (task_executions, fetch_results)
 * @property string $archive_date Data archiwum (Y-m-d)
 * @property string $status Status (pending, uploading, completed, failed)
 * @property int $file_size Rozmiar pliku w bajtach
 * @property string|null $s3_key Klucz na S3
 * @property string|null $error_message Komunikat błędu
 * @property int $started_at
 * @property int|null $completed_at
 * @property int|null $duration_ms
 */
class S3Transfer extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%s3_transfers}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['archive_type', 'archive_date', 'file_size', 'started_at'], 'required'],
            [['archive_type'], 'in', 'range' => ['task_executions', 'fetch_results']],
            [['archive_date'], 'date', 'format' => 'php:Y-m-d'],
            [['status'], 'in', 'range' => ['pending', 'uploading', 'completed', 'failed']],
            [['file_size', 'started_at', 'completed_at', 'duration_ms'], 'integer'],
            [['s3_key', 'error_message'], 'string'],
            [['status'], 'default', 'value' => 'pending'],
            [['started_at'], 'default', 'value' => function() { return time(); }],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'archive_type' => 'Typ archiwum',
            'archive_date' => 'Data archiwum',
            'status' => 'Status',
            'file_size' => 'Rozmiar pliku',
            's3_key' => 'Klucz S3',
            'error_message' => 'Błąd',
            'started_at' => 'Rozpoczęto',
            'completed_at' => 'Zakończono',
            'duration_ms' => 'Czas trwania (ms)',
        ];
    }
    
    /**
     * Rozpoczyna nowy transfer
     * 
     * @param string $archiveType
     * @param string $archiveDate
     * @param int $fileSize
     * @return static
     */
    public static function startTransfer($archiveType, $archiveDate, $fileSize)
    {
        $transfer = new static();
        $transfer->archive_type = $archiveType;
        $transfer->archive_date = $archiveDate;
        $transfer->file_size = $fileSize;
        $transfer->status = 'uploading';
        $transfer->save();
        
        return $transfer;
    }
    
    /**
     * Oznacza transfer jako zakończony
     * 
     * @param string $s3Key
     */
    public function complete($s3Key)
    {
        $this->status = 'completed';
        $this->s3_key = $s3Key;
        $this->completed_at = time();
        $this->duration_ms = ($this->completed_at - $this->started_at) * 1000;
        $this->save();
    }
    
    /**
     * Oznacza transfer jako nieudany
     * 
     * @param string $errorMessage
     */
    public function fail($errorMessage)
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->completed_at = time();
        $this->duration_ms = ($this->completed_at - $this->started_at) * 1000;
        $this->save();
    }
    
    /**
     * Zwraca rozmiar w MB
     * 
     * @return float
     */
    public function getFileSizeMb()
    {
        return round($this->file_size / 1024 / 1024, 2);
    }
}
<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model ArchiveLog - logi operacji archiwizacji
 *
 * @property int $id
 * @property string $operation Typ operacji (archive, upload, download, delete)
 * @property string $type Typ (daily, weekly, manual)
 * @property string|null $details JSON z szczegółami
 * @property int $created_at
 */
class ArchiveLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%archive_logs}}';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['operation', 'type', 'created_at'], 'required'],
            [['operation'], 'in', 'range' => ['archive', 'upload', 'download', 'delete']],
            [['type'], 'in', 'range' => ['daily', 'weekly', 'manual']],
            [['details'], 'string'],
            [['created_at'], 'integer'],
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
            'operation' => 'Operacja',
            'type' => 'Typ',
            'details' => 'Szczegóły',
            'created_at' => 'Data',
        ];
    }
    
    /**
     * Zwraca szczegóły jako tablicę
     */
    public function getDetailsArray()
    {
        return $this->details ? json_decode($this->details, true) : [];
    }
    
    /**
     * Loguje operację archiwizacji
     * 
     * @param string $operation
     * @param string $type
     * @param array $details
     * @return static
     */
    public static function log($operation, $type, $details = [])
    {
        $log = new static();
        $log->operation = $operation;
        $log->type = $type;
        $log->details = json_encode($details);
        $log->save();
        
        return $log;
    }
}
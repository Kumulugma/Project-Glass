<?php

use yii\db\Migration;

/**
 * Tworzy tabelę s3_transfers dla śledzenia transferów na S3
 */
class m260106_120001_create_s3_transfers_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%s3_transfers}}', [
            'id' => $this->primaryKey(),
            'archive_type' => "ENUM('task_executions', 'fetch_results') NOT NULL COMMENT 'Typ archiwum'",
            'archive_date' => $this->date()->notNull()->comment('Data archiwum (Y-m-d)'),
            'status' => "ENUM('pending', 'uploading', 'completed', 'failed') DEFAULT 'pending' COMMENT 'Status transferu'",
            'file_size' => $this->bigInteger()->notNull()->comment('Rozmiar pliku w bajtach'),
            's3_key' => $this->string(500)->null()->comment('Klucz na S3'),
            'error_message' => $this->text()->null()->comment('Komunikat błędu'),
            'started_at' => $this->integer()->notNull()->comment('Timestamp rozpoczęcia'),
            'completed_at' => $this->integer()->null()->comment('Timestamp zakończenia'),
            'duration_ms' => $this->integer()->null()->comment('Czas trwania w milisekundach'),
        ]);
        
        // Indeksy
        $this->createIndex('idx-s3_transfers-archive_type', '{{%s3_transfers}}', 'archive_type');
        $this->createIndex('idx-s3_transfers-archive_date', '{{%s3_transfers}}', 'archive_date');
        $this->createIndex('idx-s3_transfers-status', '{{%s3_transfers}}', 'status');
        $this->createIndex('idx-s3_transfers-started_at', '{{%s3_transfers}}', 'started_at');
        
        // Unikalność - jeden transfer na typ+datę
        $this->createIndex('idx-s3_transfers-unique', '{{%s3_transfers}}', ['archive_type', 'archive_date'], true);
        
        echo "S3 transfers table created successfully.\n";
    }

    public function safeDown()
    {
        $this->dropTable('{{%s3_transfers}}');
    }
}
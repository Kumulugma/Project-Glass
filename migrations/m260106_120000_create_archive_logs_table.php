<?php

use yii\db\Migration;

/**
 * Tworzy tabelę archive_logs dla logowania operacji archiwizacji
 */
class m260106_120000_create_archive_logs_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%archive_logs}}', [
            'id' => $this->primaryKey(),
            'operation' => "ENUM('archive', 'upload', 'download', 'delete') NOT NULL COMMENT 'Typ operacji'",
            'type' => "ENUM('daily', 'weekly', 'manual') NOT NULL COMMENT 'Typ uruchomienia'",
            'details' => $this->text()->null()->comment('JSON z szczegółami operacji'),
            'created_at' => $this->integer()->notNull()->comment('Timestamp operacji'),
        ]);
        
        // Indeksy
        $this->createIndex('idx-archive_logs-operation', '{{%archive_logs}}', 'operation');
        $this->createIndex('idx-archive_logs-created_at', '{{%archive_logs}}', 'created_at');
        
        echo "Archive logs table created successfully.\n";
    }

    public function safeDown()
    {
        $this->dropTable('{{%archive_logs}}');
    }
}
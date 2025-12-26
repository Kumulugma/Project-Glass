<?php

use yii\db\Migration;

/**
 * Historia wykonań tasków
 */
class m250101_000002_create_task_executions_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%task_executions}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull()->comment('ID taska'),
            
            // Status i stage
            'status' => "ENUM('running', 'success', 'failed', 'skipped') DEFAULT 'running'",
            'stage' => "ENUM('fetch', 'parse', 'evaluate', 'notify', 'completed') DEFAULT 'fetch'",
            
            // Dane z poszczególnych etapów
            'raw_data' => $this->text()->null()->comment('JSON - surowe dane z fetchera'),
            'parsed_data' => $this->text()->null()->comment('JSON - przetworzone dane z parsera'),
            'evaluation_result' => $this->text()->null()->comment('JSON - wynik ewaluacji (czy wysłać powiadomienie)'),
            
            // Błędy
            'error_message' => $this->text()->null()->comment('Komunikat błędu jeśli failed'),
            'error_trace' => $this->text()->null()->comment('Stack trace dla debugowania'),
            
            // Czasy
            'started_at' => $this->integer()->notNull()->comment('Timestamp rozpoczęcia'),
            'finished_at' => $this->integer()->null()->comment('Timestamp zakończenia'),
            'duration_ms' => $this->integer()->null()->comment('Czas wykonania w milisekundach'),
        ]);
        
        // Foreign key
        $this->addForeignKey(
            'fk-task_executions-task_id',
            '{{%task_executions}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        // Indeksy
        $this->createIndex('idx-task_executions-task_id', '{{%task_executions}}', 'task_id');
        $this->createIndex('idx-task_executions-status', '{{%task_executions}}', 'status');
        $this->createIndex('idx-task_executions-started_at', '{{%task_executions}}', 'started_at');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-task_executions-task_id', '{{%task_executions}}');
        $this->dropTable('{{%task_executions}}');
    }
}

<?php

use yii\db\Migration;

/**
 * Tworzy tabelę fetch_results dla przechowywania wyników fetcherów
 */
class m250126_120002_create_fetch_results_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%fetch_results}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull()->comment('ID taska'),
            'execution_id' => $this->integer()->null()->comment('ID wykonania (jeśli związane)'),
            
            // Info o fetcherze
            'fetcher_class' => $this->string(255)->notNull()->comment('Klasa fetchera'),
            'source_info' => $this->text()->null()->comment('JSON z info o źródle (URL, DB query, etc)'),
            
            // Dane
            'raw_data' => 'LONGTEXT COMMENT "Surowe dane pobrane przez fetcher"',
            'data_size' => $this->integer()->null()->comment('Rozmiar danych w bajtach'),
            'rows_count' => $this->integer()->null()->comment('Liczba rekordów/wierszy'),
            
            // Status
            'status' => "ENUM('success', 'failed', 'partial') DEFAULT 'success'",
            'error_message' => $this->text()->null()->comment('Komunikat błędu jeśli failed'),
            
            // Timestamp
            'fetched_at' => $this->integer()->notNull()->comment('Kiedy pobrano'),
        ]);
        
        // Foreign keys
        $this->addForeignKey(
            'fk-fetch_results-task_id',
            '{{%fetch_results}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-fetch_results-execution_id',
            '{{%fetch_results}}',
            'execution_id',
            '{{%task_executions}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        // Indeksy
        $this->createIndex('idx-fetch_results-task_id', '{{%fetch_results}}', 'task_id');
        $this->createIndex('idx-fetch_results-execution_id', '{{%fetch_results}}', 'execution_id');
        $this->createIndex('idx-fetch_results-fetcher_class', '{{%fetch_results}}', 'fetcher_class');
        $this->createIndex('idx-fetch_results-fetched_at', '{{%fetch_results}}', 'fetched_at');
        $this->createIndex('idx-fetch_results-status', '{{%fetch_results}}', 'status');
        
        echo "Fetch results table created successfully.\n";
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-fetch_results-execution_id', '{{%fetch_results}}');
        $this->dropForeignKey('fk-fetch_results-task_id', '{{%fetch_results}}');
        $this->dropTable('{{%fetch_results}}');
    }
}
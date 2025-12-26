<?php

use yii\db\Migration;

/**
 * Historia zmian w taskach (audyt)
 */
class m250101_000005_create_task_history_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%task_history}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull()->comment('ID taska'),
            
            // Typ zmiany
            'action' => "ENUM('created', 'updated', 'deleted', 'completed', 'paused', 'resumed') DEFAULT 'updated'",
            
            // Dane przed i po
            'old_values' => $this->text()->null()->comment('JSON ze starymi wartościami'),
            'new_values' => $this->text()->null()->comment('JSON z nowymi wartościami'),
            'changed_fields' => $this->text()->null()->comment('JSON z listą zmienionych pól'),
            
            // Kontekst
            'user_ip' => $this->string(45)->null()->comment('IP użytkownika'),
            'user_agent' => $this->text()->null()->comment('User agent'),
            
            // Timestamp
            'created_at' => $this->integer()->notNull(),
        ]);
        
        // Foreign key
        $this->addForeignKey(
            'fk-task_history-task_id',
            '{{%task_history}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        // Indeksy
        $this->createIndex('idx-task_history-task_id', '{{%task_history}}', 'task_id');
        $this->createIndex('idx-task_history-action', '{{%task_history}}', 'action');
        $this->createIndex('idx-task_history-created_at', '{{%task_history}}', 'created_at');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-task_history-task_id', '{{%task_history}}');
        $this->dropTable('{{%task_history}}');
    }
}

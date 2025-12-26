<?php

use yii\db\Migration;

/**
 * Kolejka powiadomień do wysłania
 */
class m250101_000003_create_notification_queue_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%notification_queue}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull()->comment('ID taska'),
            'execution_id' => $this->integer()->null()->comment('ID wykonania (jeśli związane z konkretnym wykonaniem)'),
            
            // Typ i kanał
            'type' => $this->string(50)->notNull()->comment('Typ: alert, reminder, report'),
            'channel' => "ENUM('email', 'sms', 'push', 'telegram', 'messenger') DEFAULT 'email'",
            
            // Odbiorca i treść
            'recipient' => $this->string(255)->notNull()->comment('Email, telefon, chat_id itp.'),
            'subject' => $this->string(255)->null()->comment('Temat (dla email)'),
            'message' => $this->text()->notNull()->comment('Treść powiadomienia'),
            'data' => $this->text()->null()->comment('JSON z dodatkowymi danymi'),
            
            // Status wysyłki
            'status' => "ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'pending'",
            'attempts' => $this->integer()->defaultValue(0)->comment('Liczba prób wysłania'),
            'max_attempts' => $this->integer()->defaultValue(3)->comment('Maksymalna liczba prób'),
            
            // Kolejkowanie
            'priority' => $this->integer()->defaultValue(5)->comment('Priorytet 1-10 (1=najwyższy)'),
            'scheduled_for' => $this->integer()->notNull()->comment('Kiedy wysłać (timestamp)'),
            
            // Wynik wysyłki
            'sent_at' => $this->integer()->null()->comment('Kiedy wysłano'),
            'error_message' => $this->text()->null()->comment('Błąd jeśli failed'),
            'response_data' => $this->text()->null()->comment('JSON z odpowiedzią z API'),
            
            // Audyt
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Foreign keys
        $this->addForeignKey(
            'fk-notification_queue-task_id',
            '{{%notification_queue}}',
            'task_id',
            '{{%tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-notification_queue-execution_id',
            '{{%notification_queue}}',
            'execution_id',
            '{{%task_executions}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        // Indeksy
        $this->createIndex('idx-notification_queue-status', '{{%notification_queue}}', 'status');
        $this->createIndex('idx-notification_queue-scheduled_for', '{{%notification_queue}}', 'scheduled_for');
        $this->createIndex('idx-notification_queue-channel', '{{%notification_queue}}', 'channel');
        $this->createIndex('idx-notification_queue-priority', '{{%notification_queue}}', 'priority');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-notification_queue-task_id', '{{%notification_queue}}');
        $this->dropForeignKey('fk-notification_queue-execution_id', '{{%notification_queue}}');
        $this->dropTable('{{%notification_queue}}');
    }
}

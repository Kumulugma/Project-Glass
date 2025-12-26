<?php

use yii\db\Migration;

/**
 * Logi aktywności użytkowników
 */
class m250101_000007_create_user_logs_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_logs}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->comment('ID użytkownika (null dla anonimowych)'),
            'action' => $this->string(100)->notNull()->comment('Akcja: login, logout, create_task, update_task, delete_task, itp'),
            'entity_type' => $this->string(50)->comment('Typ encji: task, notification, execution'),
            'entity_id' => $this->integer()->comment('ID encji'),
            'description' => $this->text()->comment('Opis akcji'),
            'ip_address' => $this->string(45)->comment('Adres IP'),
            'user_agent' => $this->text()->comment('User Agent'),
            'created_at' => $this->integer()->notNull(),
        ]);
        
        // Foreign key
        $this->addForeignKey(
            'fk-user_logs-user_id',
            '{{%user_logs}}',
            'user_id',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
        
        // Indeksy
        $this->createIndex('idx-user_logs-user_id', '{{%user_logs}}', 'user_id');
        $this->createIndex('idx-user_logs-action', '{{%user_logs}}', 'action');
        $this->createIndex('idx-user_logs-created_at', '{{%user_logs}}', 'created_at');
        $this->createIndex('idx-user_logs-entity', '{{%user_logs}}', ['entity_type', 'entity_id']);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-user_logs-user_id', '{{%user_logs}}');
        $this->dropTable('{{%user_logs}}');
    }
}
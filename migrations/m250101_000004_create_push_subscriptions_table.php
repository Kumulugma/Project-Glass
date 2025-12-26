<?php

use yii\db\Migration;

/**
 * Subskrypcje web push dla PWA
 */
class m250101_000004_create_push_subscriptions_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%push_subscriptions}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->null()->comment('ID użytkownika (jeśli masz auth)'),
            
            // Dane subskrypcji z Web Push API
            'endpoint' => $this->text()->notNull()->comment('Push endpoint URL'),
            'public_key' => $this->string(255)->null()->comment('Public key (p256dh)'),
            'auth_token' => $this->string(255)->null()->comment('Auth token'),
            
            // Metadata
            'user_agent' => $this->text()->null()->comment('Browser/device info'),
            'device_name' => $this->string(100)->null()->comment('Nazwa urządzenia (opcjonalna)'),
            
            // Status
            'is_active' => $this->boolean()->defaultValue(true)->comment('Czy aktywna'),
            'last_used_at' => $this->timestamp()->null()->comment('Ostatnie użycie'),
            'failed_at' => $this->timestamp()->null()->comment('Kiedy przestała działać'),
            'failure_reason' => $this->text()->null()->comment('Powód błędu'),
            
            // Audyt
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Indeksy
        $this->createIndex('idx-push_subscriptions-endpoint', '{{%push_subscriptions}}', 'endpoint(100)', true); // Unique
        $this->createIndex('idx-push_subscriptions-is_active', '{{%push_subscriptions}}', 'is_active');
        $this->createIndex('idx-push_subscriptions-user_id', '{{%push_subscriptions}}', 'user_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%push_subscriptions}}');
    }
}

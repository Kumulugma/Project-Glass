<?php

use yii\db\Migration;

/**
 * Tabela główna z definicjami tasków
 */
class m250101_000001_create_tasks_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%tasks}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull()->comment('Nazwa zadania'),
            'category' => $this->string(100)->null()->comment('Kategoria: rachunki, zakupy, rośliny, monitoring'),
            
            // Klasy odpowiedzialne za działanie taska
            'fetcher_class' => $this->string(255)->null()->comment('Klasa fetchera (null dla tasków bez fetchowania)'),
            'parser_class' => $this->string(255)->notNull()->comment('Klasa parsera'),
            
            // Konfiguracja
            'config' => $this->text()->null()->comment('JSON z konfiguracją dla fetcher/parser'),
            
            // Harmonogram
            'schedule' => $this->string(100)->notNull()->comment('Cron expression lub "manual"'),
            
            // Statusy i daty
            'status' => "ENUM('active', 'paused', 'completed', 'archived') DEFAULT 'active'",
            'last_run_at' => $this->timestamp()->null()->comment('Kiedy ostatnio wykonano'),
            'next_run_at' => $this->timestamp()->null()->comment('Kiedy kolejne wykonanie'),
            
            // Dodatkowe pola dla różnych typów tasków
            'amount' => $this->decimal(10, 2)->null()->comment('Kwota dla rachunków/zakupów'),
            'currency' => $this->string(3)->defaultValue('PLN')->comment('Waluta'),
            'due_date' => $this->date()->null()->comment('Termin (dla reminderów)'),
            'completed_at' => $this->timestamp()->null()->comment('Kiedy oznaczono jako wykonane'),
            
            // Notyfikacje
            'notification_channels' => $this->text()->null()->comment('JSON z kanałami powiadomień'),
            'notification_recipients' => $this->text()->null()->comment('JSON z odbiorcami'),
            'cooldown_minutes' => $this->integer()->defaultValue(60)->comment('Cooldown między powiadomieniami (minuty)'),
            'last_notification_at' => $this->timestamp()->null()->comment('Kiedy ostatnio wysłano powiadomienie'),
            
            // State tracking dla wykrywania zmian
            'last_state' => $this->text()->null()->comment('JSON z ostatnim stanem (do wykrywania zmian)'),
            
            // Audyt
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Indeksy
        $this->createIndex('idx-tasks-status', '{{%tasks}}', 'status');
        $this->createIndex('idx-tasks-category', '{{%tasks}}', 'category');
        $this->createIndex('idx-tasks-next_run_at', '{{%tasks}}', 'next_run_at');
        $this->createIndex('idx-tasks-due_date', '{{%tasks}}', 'due_date');
    }

    public function safeDown()
    {
        $this->dropTable('{{%tasks}}');
    }
}

<?php

use yii\db\Migration;

/**
 * Tworzy tabelę settings dla konfiguracji aplikacji i channeli
 */
class m250126_120001_create_settings_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%settings}}', [
            'id' => $this->primaryKey(),
            'setting_key' => $this->string(100)->notNull()->unique()->comment('Unikalny klucz ustawienia'),
            'setting_value' => $this->text()->null()->comment('Wartość ustawienia (może być JSON)'),
            'description' => $this->string(255)->null()->comment('Opis ustawienia'),
            'setting_type' => "ENUM('string', 'number', 'boolean', 'json', 'password') DEFAULT 'string' COMMENT 'Typ wartości'",
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Indeksy
        $this->createIndex('idx-settings-setting_key', '{{%settings}}', 'setting_key');
        
        // Dodaj domyślne ustawienia dla channeli
        $this->batchInsert('{{%settings}}', ['setting_key', 'setting_value', 'description', 'setting_type', 'created_at', 'updated_at'], [
            // Email channel
            ['channel_email_cooldown', '60', 'Cooldown dla Email (minuty)', 'number', time(), time()],
            ['channel_email_enabled', '1', 'Czy Email jest włączony', 'boolean', time(), time()],
            ['channel_email_from_address', '', 'Adres email nadawcy', 'string', time(), time()],
            ['channel_email_from_name', 'Task Reminder', 'Nazwa nadawcy', 'string', time(), time()],
            
            // SMS channel
            ['channel_sms_cooldown', '120', 'Cooldown dla SMS (minuty)', 'number', time(), time()],
            ['channel_sms_enabled', '0', 'Czy SMS jest włączony', 'boolean', time(), time()],
            ['channel_sms_provider', 'twilio', 'Dostawca SMS (twilio, vonage, etc)', 'string', time(), time()],
            ['channel_sms_api_key', '', 'API Key dla SMS', 'password', time(), time()],
            ['channel_sms_api_secret', '', 'API Secret dla SMS', 'password', time(), time()],
            ['channel_sms_from_number', '', 'Numer telefonu nadawcy', 'string', time(), time()],
            
            // Push channel
            ['channel_push_cooldown', '30', 'Cooldown dla Push (minuty)', 'number', time(), time()],
            ['channel_push_enabled', '0', 'Czy Push jest włączony', 'boolean', time(), time()],
            ['channel_push_vapid_public_key', '', 'VAPID Public Key', 'password', time(), time()],
            ['channel_push_vapid_private_key', '', 'VAPID Private Key', 'password', time(), time()],
        ]);
        
        echo "Settings table created and default values inserted.\n";
    }

    public function safeDown()
    {
        $this->dropTable('{{%settings}}');
    }
}
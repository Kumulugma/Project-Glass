<?php

use yii\db\Migration;

/**
 * Tabela użytkowników systemu GlassSystem
 */
class m250101_000006_create_users_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull()->unique()->comment('Login użytkownika'),
            'email' => $this->string(255)->notNull()->unique()->comment('Email'),
            'password_hash' => $this->string(255)->notNull()->comment('Hash hasła'),
            'auth_key' => $this->string(32)->notNull()->comment('Klucz autoryzacji'),
            'password_reset_token' => $this->string(255)->unique()->comment('Token resetowania hasła'),
            
            // Dane osobowe
            'first_name' => $this->string(100)->comment('Imię'),
            'last_name' => $this->string(100)->comment('Nazwisko'),
            
            // Role i uprawnienia
            'role' => "ENUM('admin', 'user') DEFAULT 'user'",
            
            // Status
            'status' => $this->smallInteger()->notNull()->defaultValue(10)->comment('Status: 9-usuń, 10-aktywny'),
            'last_login_at' => $this->integer()->comment('Ostatnie logowanie'),
            'last_login_ip' => $this->string(45)->comment('IP ostatniego logowania'),
            
            // Audyt
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Indeksy
        $this->createIndex('idx-users-username', '{{%users}}', 'username');
        $this->createIndex('idx-users-email', '{{%users}}', 'email');
        $this->createIndex('idx-users-status', '{{%users}}', 'status');
        
        // Dodaj domyślnego admina (hasło: admin123)
        $this->insert('{{%users}}', [
            'username' => 'admin',
            'email' => 'admin@glasssystem.local',
            'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'first_name' => 'Administrator',
            'last_name' => 'Systemu',
            'role' => 'admin',
            'status' => 10,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%users}}');
    }
}
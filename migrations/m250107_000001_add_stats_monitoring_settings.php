<?php

use yii\db\Migration;

/**
 * Dodaje ustawienia dla systemu statystyk i monitoringu
 */
class m250107_000001_add_stats_monitoring_settings extends Migration
{
    public function safeUp()
    {
        $time = time();
        
        // Dodaj ustawienia API monitoringu
        $this->batchInsert('{{%settings}}', 
            ['setting_key', 'setting_value', 'description', 'setting_type', 'created_at', 'updated_at'], 
            [
                // Konfiguracja API
                ['monitoring_api_url', 'https://k3e.pl/wp-json/k3e-stats/v1/update', 'URL endpoint API monitoringu', 'string', $time, $time],
                ['monitoring_api_token', 'ffb2a83fef091dc62026e48ba0c08dc73b1d02bc25a7209e144cd6c2d0b4ab3c', 'Token autoryzacyjny API', 'password', $time, $time],
                ['monitoring_enabled', '1', 'Czy wysyłanie statystyk jest włączone', 'boolean', $time, $time],
                ['monitoring_interval', '10', 'Interwał wysyłania statystyk (minuty)', 'number', $time, $time],
                
                // Liczniki (incrementowane przy każdej operacji)
                ['stats_total_executions', '0', 'Łączna liczba wykonań tasków', 'number', $time, $time],
                ['stats_total_notifications', '0', 'Łączna liczba powiadomień', 'number', $time, $time],
                ['stats_last_execution_date', null, 'Data ostatniego wykonania taska', 'string', $time, $time],
                
                // Metadata
                ['stats_last_sent_at', null, 'Kiedy ostatnio wysłano statystyki', 'string', $time, $time],
                ['stats_last_send_status', null, 'Status ostatniego wysłania', 'string', $time, $time],
            ]
        );
        
        echo "Stats monitoring settings added successfully.\n";
    }

    public function safeDown()
    {
        $this->delete('{{%settings}}', ['like', 'setting_key', 'monitoring_%', false]);
        $this->delete('{{%settings}}', ['like', 'setting_key', 'stats_%', false]);
    }
}
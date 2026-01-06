<?php

namespace app\components;

use Yii;
use app\models\Setting;

/**
 * Komponent do wysyłania statystyk do zewnętrznego API
 * 
 * Wykorzystuje liczniki z tabeli settings zamiast COUNT() - znacznie szybsze!
 * Liczniki są automatycznie incrementowane przez TaskRunner i NotificationQueue.
 * 
 * Wykorzystanie:
 * - php yii stats/send - ręczne wysłanie
 */
class StatsReporter
{
    /**
     * Pobiera aktualne statystyki z settings (liczniki)
     * 
     * @return array
     */
    public static function collectStats()
    {
        // Pobierz liczniki z settings - nie używamy COUNT()!
        $totalExecutions = (int)Setting::get('stats_total_executions', 0);
        $totalNotifications = (int)Setting::get('stats_total_notifications', 0);
        $lastDate = Setting::get('stats_last_execution_date');
        
        return [
            'zapytania' => $totalExecutions,
            'powiadomienia' => $totalNotifications,
            'ostatnia_data' => $lastDate,
        ];
    }
    
    /**
     * Incrementuje licznik wykonań tasków
     * Wywoływane automatycznie przez TaskRunner po każdym wykonaniu
     * 
     * @param string $executionDate Data wykonania (Y-m-d H:i:s)
     */
    public static function incrementExecutions($executionDate = null)
    {
        $current = (int)Setting::get('stats_total_executions', 0);
        Setting::set('stats_total_executions', $current + 1, 'number');
        
        if ($executionDate) {
            Setting::set('stats_last_execution_date', $executionDate, 'string');
        }
    }
    
    /**
     * Incrementuje licznik powiadomień
     * Wywoływane automatycznie gdy tworzone jest nowe powiadomienie
     */
    public static function incrementNotifications()
    {
        $current = (int)Setting::get('stats_total_notifications', 0);
        Setting::set('stats_total_notifications', $current + 1, 'number');
    }
    
    /**
     * Wysyła statystyki do API
     * 
     * @return array ['success' => bool, 'response' => mixed, 'error' => string|null]
     */
    public static function sendStats()
    {
        // Sprawdź czy monitoring jest włączony
        if (!Setting::get('monitoring_enabled', false)) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Monitoring jest wyłączony w konfiguracji',
            ];
        }
        
        // Pobierz konfigurację z bazy
        $apiUrl = Setting::get('monitoring_api_url');
        $apiToken = Setting::get('monitoring_api_token');
        
        if (!$apiUrl || !$apiToken) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Brak konfiguracji API (URL lub token)',
            ];
        }
        
        $stats = self::collectStats();
        
        // Jeśli brak danych, nie wysyłaj
        if ($stats['zapytania'] === 0 && $stats['powiadomienia'] === 0) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Brak danych do wysłania',
            ];
        }
        
        try {
            // Przygotuj żądanie cURL
            $ch = curl_init($apiUrl);
            
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => json_encode($stats),
                CURLOPT_TIMEOUT => 10,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Sprawdź wynik
            if ($error) {
                throw new \Exception("cURL error: $error");
            }
            
            if ($httpCode !== 200) {
                throw new \Exception("HTTP error $httpCode: $response");
            }
            
            // Zapisz metadane wysyłki
            Setting::set('stats_last_sent_at', date('Y-m-d H:i:s'), 'string');
            Setting::set('stats_last_send_status', 'success', 'string');
            
            // Loguj sukces
            Yii::info([
                'message' => 'Statystyki wysłane pomyślnie',
                'stats' => $stats,
                'response' => $response,
            ], __METHOD__);
            
            return [
                'success' => true,
                'response' => $response,
                'error' => null,
            ];
            
        } catch (\Exception $e) {
            // Zapisz błąd
            Setting::set('stats_last_sent_at', date('Y-m-d H:i:s'), 'string');
            Setting::set('stats_last_send_status', 'error: ' . $e->getMessage(), 'string');
            
            // Loguj błąd
            Yii::error([
                'message' => 'Błąd podczas wysyłania statystyk',
                'stats' => $stats,
                'error' => $e->getMessage(),
            ], __METHOD__);
            
            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Pobiera szczegółowe statystyki (opcjonalnie)
     * 
     * @return array
     */
    public static function getDetailedStats()
    {
        return [
            // Podstawowe liczniki
            'total_executions' => (int)Setting::get('stats_total_executions', 0),
            'total_notifications' => (int)Setting::get('stats_total_notifications', 0),
            'last_execution_date' => Setting::get('stats_last_execution_date'),
            
            // Metadata monitoringu
            'monitoring_enabled' => Setting::get('monitoring_enabled', false),
            'monitoring_interval' => Setting::get('monitoring_interval', 10),
            'last_sent_at' => Setting::get('stats_last_sent_at'),
            'last_send_status' => Setting::get('stats_last_send_status'),
        ];
    }
}
<?php

namespace app\components\parsers;

/**
 * Parser sprawdzający dostępność URL
 * Używany do monitorowania czy strona jest online
 */
class UrlHealthCheckParser extends AbstractParser
{
    /**
     * @inheritdoc
     */
    public function parse($rawData)
    {
        return [
            'is_up' => $rawData['success'] ?? false,
            'http_code' => $rawData['http_code'] ?? null,
            'response_time_ms' => $rawData['duration_ms'] ?? null,
            'error' => $rawData['error'] ?? null,
            'url' => $rawData['url'] ?? $this->config['url'],
            'timestamp' => $rawData['timestamp'] ?? time(),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function evaluate($parsedData)
    {
        $notifications = [];
        
        // Sprawdź czy stan się zmienił
        $previousState = $this->task->getLastState();
        $currentIsUp = $parsedData['is_up'];
        $previousIsUp = $previousState['is_up'] ?? null;
        
        // Wykryj zmianę stanu
        $stateChanged = ($previousIsUp !== null && $previousIsUp !== $currentIsUp);
        
        // Strona padła
        if (!$currentIsUp) {
            // Wysyłaj tylko jeśli:
            // 1. Stan się zmienił (była UP, teraz DOWN) - natychmiastowe powiadomienie
            // 2. LUB minął cooldown period
            if ($stateChanged || $this->shouldNotifyDueToCooldown()) {
                $notifications[] = [
                    'type' => 'alert',
                    'subject' => 'Strona nie odpowiada!',
                    'message' => $this->renderTemplate(
                        $this->config['down_message'] ?? 'Strona {{url}} nie odpowiada! Kod HTTP: {{http_code}}, Błąd: {{error}}',
                        $parsedData
                    ),
                    'priority' => 3, // Wysoki priorytet
                    'data' => $parsedData,
                ];
            }
        }
        
        // Strona wróciła do życia
        if ($currentIsUp && $stateChanged && $previousIsUp === false) {
            $notifications[] = [
                'type' => 'alert',
                'subject' => 'Strona znowu działa!',
                'message' => $this->renderTemplate(
                    $this->config['up_message'] ?? 'Strona {{url}} jest znowu dostępna! Czas odpowiedzi: {{response_time_ms}}ms',
                    $parsedData
                ),
                'priority' => 5, // Normalny priorytet
                'data' => $parsedData,
            ];
        }
        
        // Powolna odpowiedź (opcjonalnie)
        if ($currentIsUp && isset($this->config['slow_threshold_ms'])) {
            $threshold = (int)$this->config['slow_threshold_ms'];
            if ($parsedData['response_time_ms'] > $threshold) {
                // Tylko jeśli minął cooldown
                if ($this->shouldNotifyDueToCooldown()) {
                    $notifications[] = [
                        'type' => 'alert',
                        'subject' => 'Strona działa wolno',
                        'message' => $this->renderTemplate(
                            'Strona {{url}} odpowiada wolno: {{response_time_ms}}ms (próg: ' . $threshold . 'ms)',
                            $parsedData
                        ),
                        'priority' => 7, // Niski priorytet
                        'data' => $parsedData,
                    ];
                }
            }
        }
        
        return $notifications;
    }
    
    /**
     * Sprawdza czy minął cooldown period od ostatniego powiadomienia
     */
    private function shouldNotifyDueToCooldown()
    {
        if (!$this->task->last_notification_at) {
            return true; // Pierwsze powiadomienie
        }
        
        $cooldownMinutes = $this->task->cooldown_minutes ?? 60;
        $cooldownSeconds = $cooldownMinutes * 60;
        $lastNotificationTime = strtotime($this->task->last_notification_at);
        
        return (time() - $lastNotificationTime) >= $cooldownSeconds;
    }
    
    /**
     * @inheritdoc
     */
    public function hasStateChanged($parsedData, $previousState)
    {
        // Interesuje nas tylko zmiana is_up
        return ($parsedData['is_up'] ?? null) !== ($previousState['is_up'] ?? null);
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        // Nie ma specjalnych wymagań, fetcher już waliduje URL
        return true;
    }
    
    /**
     * @inheritdoc
     */
    public static function getConfigFields()
    {
        return [
            'slow_threshold_ms' => [
                'type' => 'number',
                'label' => 'Próg wolnej odpowiedzi (ms)',
                'placeholder' => '3000',
                'help' => 'Powiadom jeśli strona odpowiada dłużej niż X milisekund',
            ],
            'down_message' => [
                'type' => 'textarea',
                'label' => 'Wiadomość gdy strona nie działa',
                'placeholder' => 'Strona {{url}} nie odpowiada!',
                'help' => 'Możesz użyć: {{url}}, {{http_code}}, {{error}}, {{response_time_ms}}',
            ],
            'up_message' => [
                'type' => 'textarea',
                'label' => 'Wiadomość gdy strona wraca',
                'placeholder' => 'Strona {{url}} jest znowu dostępna!',
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'Sprawdzenie dostępności URL';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Monitoruje czy strona WWW jest dostępna i odpowiada poprawnie. Wykrywa zmiany stanu (up/down) i powiadamia o problemach.';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDefaultFetcherClass()
    {
        return 'UrlFetcher';
    }
}

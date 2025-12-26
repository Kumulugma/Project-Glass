<?php

namespace app\components\parsers;

/**
 * Parser dla przypomnień o roślinach (siew, podlewanie, pielęgnacja)
 */
class PlantReminderParser extends AbstractParser
{
    /**
     * @inheritdoc
     */
    public function parse($rawData)
    {
        $startDate = $this->config['start_date'] ?? null;
        $endDate = $this->config['end_date'] ?? null;
        
        if (!$startDate || !$endDate) {
            throw new \Exception('Brak dat start_date i end_date w konfiguracji');
        }
        
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $now = new \DateTime();
        
        $isInPeriod = ($now >= $start && $now <= $end);
        
        return [
            'plant_name' => $this->task->name,
            'action' => $this->config['action'] ?? 'wysiew',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_in_period' => $isInPeriod,
            'days_until_start' => $start > $now ? $now->diff($start)->days : 0,
            'days_until_end' => $end > $now ? $now->diff($end)->days : 0,
            'is_overdue' => ($now > $end),
            'timestamp' => time(),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function evaluate($parsedData)
    {
        $notifications = [];
        
        // Task już wykonany - nie przypominaj
        if ($this->task->status === 'completed' || $this->task->completed_at) {
            return [];
        }
        
        // Poza okresem - nie przypominaj
        if (!$parsedData['is_in_period'] && !$parsedData['is_overdue']) {
            return [];
        }
        
        // Okres przeterminowany - ostatnie przypomnienie
        if ($parsedData['is_overdue']) {
            // Tylko raz po zakończeniu okresu
            $lastState = $this->task->getLastState();
            if (!isset($lastState['is_overdue']) || !$lastState['is_overdue']) {
                $notifications[] = [
                    'type' => 'reminder',
                    'subject' => 'Minął okres: ' . $parsedData['plant_name'],
                    'message' => $this->renderTemplate(
                        $this->config['overdue_message'] ?? 'Uwaga! Minął okres {{action}} dla: {{plant_name}}. Koniec: {{end_date}}',
                        $parsedData
                    ),
                    'priority' => 3,
                    'data' => $parsedData,
                ];
            }
            return $notifications;
        }
        
        // W okresie - przypominaj codziennie
        if ($parsedData['is_in_period']) {
            // Sprawdź czy już wysyłano dzisiaj
            if (!$this->shouldNotifyToday()) {
                return [];
            }
            
            $notifications[] = [
                'type' => 'reminder',
                'subject' => 'Przypomnienie: ' . $parsedData['plant_name'],
                'message' => $this->renderTemplate(
                    $this->config['reminder_message'] ?? '🌱 Pamiętaj o {{action}}: {{plant_name}} (do {{end_date}})',
                    $parsedData
                ),
                'priority' => 5,
                'data' => $parsedData,
            ];
        }
        
        return $notifications;
    }
    
    /**
     * Sprawdza czy już wysyłano dzisiaj
     */
    private function shouldNotifyToday()
    {
        if (!$this->task->last_notification_at) {
            return true;
        }
        
        $lastNotificationDate = date('Y-m-d', strtotime($this->task->last_notification_at));
        $today = date('Y-m-d');
        
        return $lastNotificationDate !== $today;
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        $errors = [];
        
        if (empty($this->config['start_date'])) {
            $errors[] = 'Data rozpoczęcia (start_date) jest wymagana';
        }
        
        if (empty($this->config['end_date'])) {
            $errors[] = 'Data zakończenia (end_date) jest wymagana';
        }
        
        if (!empty($this->config['start_date']) && !empty($this->config['end_date'])) {
            $start = new \DateTime($this->config['start_date']);
            $end = new \DateTime($this->config['end_date']);
            if ($start > $end) {
                $errors[] = 'Data rozpoczęcia musi być wcześniej niż data zakończenia';
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * @inheritdoc
     */
    public static function getConfigFields()
    {
        return [
            'action' => [
                'type' => 'text',
                'label' => 'Czynność',
                'placeholder' => 'wysiew, podlewanie, nawożenie',
                'default' => 'wysiew',
            ],
            'start_date' => [
                'type' => 'date',
                'label' => 'Data rozpoczęcia okresu',
                'required' => true,
            ],
            'end_date' => [
                'type' => 'date',
                'label' => 'Data zakończenia okresu',
                'required' => true,
            ],
            'reminder_message' => [
                'type' => 'textarea',
                'label' => 'Wiadomość przypomnienia',
                'placeholder' => '🌱 Pamiętaj o {{action}}: {{plant_name}}',
                'help' => 'Dostępne: {{plant_name}}, {{action}}, {{start_date}}, {{end_date}}, {{days_until_end}}',
            ],
            'overdue_message' => [
                'type' => 'textarea',
                'label' => 'Wiadomość po okresie',
                'placeholder' => 'Minął okres {{action}} dla: {{plant_name}}',
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'Kalendarz roślin';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Przypomina o czynnościach związanych z roślinami w określonym okresie (siew, podlewanie, nawożenie).';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDefaultFetcherClass()
    {
        return 'EmptyFetcher';
    }
}

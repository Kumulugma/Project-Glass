<?php

namespace app\components\parsers;

/**
 * Parser dla przypomnień o rachunkach, terminach i innych jednorazowych/cyklicznych zadaniach
 */
class ReminderParser extends AbstractParser
{
    /**
     * @inheritdoc
     */
    public function parse($rawData)
    {
        $dueDate = $this->config['due_date'] ?? $this->task->due_date;
        
        if (!$dueDate) {
            throw new \Exception('Brak daty terminu (due_date) w konfiguracji');
        }
        
        $dueDateObj = new \DateTime($dueDate);
        $now = new \DateTime();
        
        $diff = $now->diff($dueDateObj);
        $daysUntil = (int)$diff->format('%r%a'); // z minusem jeśli przeszłość
        
        return [
            'due_date' => $dueDate,
            'days_until' => $daysUntil,
            'is_overdue' => $daysUntil < 0,
            'is_today' => $daysUntil === 0,
            'is_soon' => $daysUntil > 0 && $daysUntil <= ($this->config['notify_before_days'] ?? 3),
            'amount' => $this->task->amount,
            'currency' => $this->task->currency ?? 'PLN',
            'task_name' => $this->task->name,
            'timestamp' => time(),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function evaluate($parsedData)
    {
        $notifications = [];
        $notifyBeforeDays = $this->config['notify_before_days'] ?? 3;
        
        // Task już wykonany - nie przypominaj
        if ($this->task->status === 'completed' || $this->task->completed_at) {
            return [];
        }
        
        // Task przeterminowany
        if ($parsedData['is_overdue']) {
            // Przypominaj co jakiś czas (cooldown)
            if ($this->shouldNotifyDueToCooldown()) {
                $notifications[] = [
                    'type' => 'reminder',
                    'subject' => 'PRZETERMINOWANE: ' . $parsedData['task_name'],
                    'message' => $this->renderTemplate(
                        $this->config['overdue_message'] ?? 'UWAGA! Termin minął {{days_until}} dni temu: {{task_name}} {{amount}} {{currency}}',
                        array_merge($parsedData, ['days_until' => abs($parsedData['days_until'])])
                    ),
                    'priority' => 1, // Najwyższy priorytet
                    'data' => $parsedData,
                ];
            }
            return $notifications;
        }
        
        // Termin dzisiaj
        if ($parsedData['is_today']) {
            // Przypominaj raz dziennie
            if ($this->shouldNotifyToday()) {
                $notifications[] = [
                    'type' => 'reminder',
                    'subject' => 'DZISIAJ: ' . $parsedData['task_name'],
                    'message' => $this->renderTemplate(
                        $this->config['today_message'] ?? 'DZISIAJ upływa termin: {{task_name}} {{amount}} {{currency}}',
                        $parsedData
                    ),
                    'priority' => 2,
                    'data' => $parsedData,
                ];
            }
            return $notifications;
        }
        
        // Zbliża się termin (X dni przed)
        if ($parsedData['is_soon']) {
            // Przypominaj raz dziennie
            if ($this->shouldNotifyToday()) {
                $notifications[] = [
                    'type' => 'reminder',
                    'subject' => 'Przypomnienie: ' . $parsedData['task_name'],
                    'message' => $this->renderTemplate(
                        $this->config['reminder_message'] ?? 'Za {{days_until}} dni: {{task_name}} {{amount}} {{currency}}',
                        $parsedData
                    ),
                    'priority' => 5,
                    'data' => $parsedData,
                ];
            }
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
     * Sprawdza cooldown dla przeterminowanych
     */
    private function shouldNotifyDueToCooldown()
    {
        if (!$this->task->last_notification_at) {
            return true;
        }
        
        $cooldownMinutes = $this->task->cooldown_minutes ?? 1440; // Domyślnie 24h dla przeterminowanych
        $cooldownSeconds = $cooldownMinutes * 60;
        $lastNotificationTime = strtotime($this->task->last_notification_at);
        
        return (time() - $lastNotificationTime) >= $cooldownSeconds;
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        $errors = [];
        
        if (empty($this->config['due_date']) && empty($this->task->due_date)) {
            $errors[] = 'Data terminu (due_date) jest wymagana';
        }
        
        if (isset($this->config['notify_before_days']) && !is_numeric($this->config['notify_before_days'])) {
            $errors[] = 'notify_before_days musi być liczbą';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * @inheritdoc
     */
    public static function getConfigFields()
    {
        return [
            'notify_before_days' => [
                'type' => 'number',
                'label' => 'Ile dni przed przypominać',
                'default' => 3,
                'min' => 1,
                'max' => 30,
            ],
            'reminder_message' => [
                'type' => 'textarea',
                'label' => 'Wiadomość przypomnienia',
                'placeholder' => 'Za {{days_until}} dni: {{task_name}}',
                'help' => 'Dostępne: {{days_until}}, {{task_name}}, {{amount}}, {{currency}}, {{due_date}}',
            ],
            'today_message' => [
                'type' => 'textarea',
                'label' => 'Wiadomość na dzień terminu',
                'placeholder' => 'DZISIAJ: {{task_name}}',
            ],
            'overdue_message' => [
                'type' => 'textarea',
                'label' => 'Wiadomość po terminie',
                'placeholder' => 'PRZETERMINOWANE: {{task_name}}',
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'Przypomnienie';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Przypomina o terminach (rachunki, zadania). Powiadamia X dni przed terminem, w dniu terminu i po przeterminowaniu.';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDefaultFetcherClass()
    {
        return 'EmptyFetcher';
    }
}

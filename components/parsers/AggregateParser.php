<?php

namespace app\components\parsers;

use app\models\Task;

/**
 * Parser agregujący dane z innych tasków
 * Używany do raportów: suma rachunków w miesiącu, lista zakupów, statystyki
 */
class AggregateParser extends AbstractParser
{
    /**
     * @inheritdoc
     */
    public function parse($rawData)
    {
        $sourceCategory = $this->config['source_category'] ?? null;
        $aggregateType = $this->config['aggregate_type'] ?? 'sum_amount';
        $period = $this->config['period'] ?? 'current_month';
        
        if (!$sourceCategory) {
            throw new \Exception('Brak source_category w konfiguracji');
        }
        
        // Pobierz taski z danej kategorii
        $query = Task::find()
            ->where(['category' => $sourceCategory])
            ->andWhere(['status' => 'active']);
        
        // Filtruj po okresie (dla rachunków - po due_date)
        if ($period !== 'all') {
            $dateRange = $this->getDateRange($period);
            if ($dateRange) {
                $query->andWhere(['>=', 'due_date', $dateRange['start']])
                      ->andWhere(['<=', 'due_date', $dateRange['end']]);
            }
        }
        
        $tasks = $query->all();
        
        // Agreguj dane
        $result = [
            'category' => $sourceCategory,
            'period' => $period,
            'aggregate_type' => $aggregateType,
            'total_tasks' => count($tasks),
            'completed_tasks' => 0,
            'pending_tasks' => 0,
            'overdue_tasks' => 0,
            'total_amount' => 0,
            'items' => [],
            'timestamp' => time(),
        ];
        
        foreach ($tasks as $task) {
            $isCompleted = ($task->status === 'completed' || $task->completed_at);
            $isOverdue = (!$isCompleted && $task->due_date && $task->due_date < date('Y-m-d'));
            
            if ($isCompleted) {
                $result['completed_tasks']++;
            } elseif ($isOverdue) {
                $result['overdue_tasks']++;
            } else {
                $result['pending_tasks']++;
            }
            
            if ($task->amount) {
                $result['total_amount'] += $task->amount;
            }
            
            $result['items'][] = [
                'id' => $task->id,
                'name' => $task->name,
                'amount' => $task->amount,
                'due_date' => $task->due_date,
                'status' => $task->status,
                'is_completed' => $isCompleted,
                'is_overdue' => $isOverdue,
            ];
        }
        
        return $result;
    }
    
    /**
     * @inheritdoc
     */
    public function evaluate($parsedData)
    {
        $notifications = [];
        
        // Sprawdź czy jest coś do raportowania
        if ($parsedData['total_tasks'] === 0) {
            return [];
        }
        
        // Sprawdź cooldown - nie wysyłaj raportów za często
        if (!$this->shouldNotifyDueToCooldown()) {
            return [];
        }
        
        // Generuj raport
        $reportType = $this->config['report_type'] ?? 'summary';
        
        if ($reportType === 'summary') {
            $message = $this->generateSummaryReport($parsedData);
        } elseif ($reportType === 'detailed') {
            $message = $this->generateDetailedReport($parsedData);
        } else {
            $message = $this->renderTemplate(
                $this->config['custom_message'] ?? 'Raport {{category}}: {{total_tasks}} zadań, suma: {{total_amount}} PLN',
                $parsedData
            );
        }
        
        $notifications[] = [
            'type' => 'report',
            'subject' => $this->renderTemplate(
                $this->config['subject'] ?? 'Raport: {{category}} ({{period}})',
                $parsedData
            ),
            'message' => $message,
            'priority' => 6,
            'data' => $parsedData,
        ];
        
        return $notifications;
    }
    
    /**
     * Generuje podsumowanie
     */
    private function generateSummaryReport($data)
    {
        $lines = [];
        $lines[] = "📊 PODSUMOWANIE: " . strtoupper($data['category']);
        $lines[] = "Okres: " . $this->getPeriodLabel($data['period']);
        $lines[] = "";
        $lines[] = "Łącznie zadań: " . $data['total_tasks'];
        $lines[] = "✅ Wykonanych: " . $data['completed_tasks'];
        $lines[] = "⏳ Do zrobienia: " . $data['pending_tasks'];
        
        if ($data['overdue_tasks'] > 0) {
            $lines[] = "⚠️ Przeterminowanych: " . $data['overdue_tasks'];
        }
        
        if ($data['total_amount'] > 0) {
            $lines[] = "";
            $lines[] = "💰 Suma: " . number_format($data['total_amount'], 2, ',', ' ') . " PLN";
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Generuje szczegółowy raport
     */
    private function generateDetailedReport($data)
    {
        $lines = [];
        $lines[] = "📊 SZCZEGÓŁOWY RAPORT: " . strtoupper($data['category']);
        $lines[] = "Okres: " . $this->getPeriodLabel($data['period']);
        $lines[] = "";
        
        // Grupuj po statusie
        $pending = array_filter($data['items'], fn($i) => !$i['is_completed'] && !$i['is_overdue']);
        $overdue = array_filter($data['items'], fn($i) => $i['is_overdue']);
        $completed = array_filter($data['items'], fn($i) => $i['is_completed']);
        
        if (!empty($overdue)) {
            $lines[] = "⚠️ PRZETERMINOWANE:";
            foreach ($overdue as $item) {
                $lines[] = "  - {$item['name']} ({$item['amount']} PLN) - termin: {$item['due_date']}";
            }
            $lines[] = "";
        }
        
        if (!empty($pending)) {
            $lines[] = "⏳ DO ZROBIENIA:";
            foreach ($pending as $item) {
                $lines[] = "  - {$item['name']} ({$item['amount']} PLN) - termin: {$item['due_date']}";
            }
            $lines[] = "";
        }
        
        if (!empty($completed)) {
            $lines[] = "✅ WYKONANE:";
            foreach ($completed as $item) {
                $lines[] = "  - {$item['name']} ({$item['amount']} PLN)";
            }
            $lines[] = "";
        }
        
        if ($data['total_amount'] > 0) {
            $lines[] = "💰 SUMA: " . number_format($data['total_amount'], 2, ',', ' ') . " PLN";
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Oblicza zakres dat dla okresu
     */
    private function getDateRange($period)
    {
        $now = new \DateTime();
        
        switch ($period) {
            case 'current_month':
                return [
                    'start' => $now->format('Y-m-01'),
                    'end' => $now->format('Y-m-t'),
                ];
            
            case 'next_month':
                $next = (clone $now)->modify('+1 month');
                return [
                    'start' => $next->format('Y-m-01'),
                    'end' => $next->format('Y-m-t'),
                ];
            
            case 'current_week':
                $start = (clone $now)->modify('monday this week');
                $end = (clone $now)->modify('sunday this week');
                return [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                ];
            
            case 'all':
            default:
                return null;
        }
    }
    
    /**
     * Zwraca czytelną nazwę okresu
     */
    private function getPeriodLabel($period)
    {
        $labels = [
            'current_month' => 'Bieżący miesiąc',
            'next_month' => 'Następny miesiąc',
            'current_week' => 'Bieżący tydzień',
            'all' => 'Wszystkie',
        ];
        
        return $labels[$period] ?? $period;
    }
    
    /**
     * Sprawdza cooldown
     */
    private function shouldNotifyDueToCooldown()
    {
        if (!$this->task->last_notification_at) {
            return true;
        }
        
        $cooldownMinutes = $this->task->cooldown_minutes ?? 1440; // Domyślnie raz dziennie
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
        
        if (empty($this->config['source_category'])) {
            $errors[] = 'Kategoria źródłowa (source_category) jest wymagana';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * @inheritdoc
     */
    public static function getConfigFields()
    {
        return [
            'source_category' => [
                'type' => 'text',
                'label' => 'Kategoria źródłowa',
                'placeholder' => 'rachunki',
                'required' => true,
                'help' => 'Z jakiej kategorii tasków zbierać dane',
            ],
            'period' => [
                'type' => 'dropdown',
                'label' => 'Okres',
                'options' => [
                    'current_month' => 'Bieżący miesiąc',
                    'next_month' => 'Następny miesiąc',
                    'current_week' => 'Bieżący tydzień',
                    'all' => 'Wszystkie',
                ],
                'default' => 'current_month',
            ],
            'report_type' => [
                'type' => 'dropdown',
                'label' => 'Typ raportu',
                'options' => [
                    'summary' => 'Podsumowanie',
                    'detailed' => 'Szczegółowy',
                    'custom' => 'Niestandardowy',
                ],
                'default' => 'summary',
            ],
            'subject' => [
                'type' => 'text',
                'label' => 'Temat wiadomości',
                'placeholder' => 'Raport: {{category}} ({{period}})',
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'Raport agregujący';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Zbiera dane z tasków w danej kategorii i generuje raporty (np. suma rachunków w miesiącu).';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDefaultFetcherClass()
    {
        return 'EmptyFetcher';
    }
}

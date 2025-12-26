<?php

namespace app\components\parsers;

/**
 * Parser dla listy zakupów
 * Obsługuje różne kategorie (normalny sklep vs specjalny) z różnymi rytmami przypominania
 */
class ShoppingItemParser extends AbstractParser
{
    /**
     * @inheritdoc
     */
    public function parse($rawData)
    {
        $category = $this->config['shopping_category'] ?? 'normalny';
        $now = new \DateTime();
        $dayOfWeek = (int)$now->format('N'); // 1=poniedziałek, 7=niedziela
        
        return [
            'item_name' => $this->task->name,
            'amount' => $this->task->amount,
            'currency' => $this->task->currency ?? 'PLN',
            'category' => $category,
            'is_weekend' => ($dayOfWeek >= 6), // Sobota lub niedziela
            'day_of_week' => $dayOfWeek,
            'should_remind_today' => $this->shouldRemindToday($category, $dayOfWeek),
            'timestamp' => time(),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function evaluate($parsedData)
    {
        $notifications = [];
        
        // Task już kupiony - nie przypominaj
        if ($this->task->status === 'completed' || $this->task->completed_at) {
            return [];
        }
        
        // Sprawdź czy dzisiaj powinno przypomnieć
        if (!$parsedData['should_remind_today']) {
            return [];
        }
        
        // Sprawdź czy już wysyłano dzisiaj
        if (!$this->shouldNotifyToday()) {
            return [];
        }
        
        // Wysyłaj powiadomienie
        $categoryLabel = $parsedData['category'] === 'specjalny' ? ' (weekend)' : '';
        
        $notifications[] = [
            'type' => 'reminder',
            'subject' => 'Lista zakupów' . $categoryLabel,
            'message' => $this->renderTemplate(
                $this->config['message'] ?? '🛒 Do kupienia: {{item_name}} ({{amount}} {{currency}})',
                $parsedData
            ),
            'priority' => 5,
            'data' => $parsedData,
        ];
        
        return $notifications;
    }
    
    /**
     * Sprawdza czy dzisiaj powinno przypomnieć w zależności od kategorii
     */
    private function shouldRemindToday($category, $dayOfWeek)
    {
        if ($category === 'normalny') {
            // Normalny sklep - codziennie
            return true;
        }
        
        if ($category === 'specjalny') {
            // Specjalny sklep - tylko weekendy (sobota=6, niedziela=7)
            return ($dayOfWeek >= 6);
        }
        
        // Niestandardowa kategoria - sprawdź konfigurację dni
        if (isset($this->config['reminder_days'])) {
            $reminderDays = $this->config['reminder_days']; // np. [1,3,5] = pon, śr, pt
            return in_array($dayOfWeek, $reminderDays);
        }
        
        return true; // Domyślnie codziennie
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
        
        if (isset($this->config['shopping_category'])) {
            $validCategories = ['normalny', 'specjalny'];
            if (!in_array($this->config['shopping_category'], $validCategories)) {
                $errors[] = 'Nieprawidłowa kategoria zakupów. Dozwolone: ' . implode(', ', $validCategories);
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
            'shopping_category' => [
                'type' => 'dropdown',
                'label' => 'Kategoria zakupów',
                'options' => [
                    'normalny' => 'Normalny sklep (codziennie)',
                    'specjalny' => 'Specjalny sklep (tylko weekend)',
                ],
                'default' => 'normalny',
            ],
            'message' => [
                'type' => 'textarea',
                'label' => 'Treść przypomnienia',
                'placeholder' => '🛒 Do kupienia: {{item_name}} ({{amount}} {{currency}})',
                'help' => 'Dostępne: {{item_name}}, {{amount}}, {{currency}}, {{category}}',
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'Lista zakupów';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Przypomina o zakupach do zrobienia. Obsługuje różne kategorie (normalny sklep - codziennie, specjalny - weekendy).';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDefaultFetcherClass()
    {
        return 'EmptyFetcher';
    }
}

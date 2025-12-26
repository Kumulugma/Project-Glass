<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Model Task
 *
 * @property int $id
 * @property string $name
 * @property string|null $category
 * @property string|null $fetcher_class
 * @property string $parser_class
 * @property string|null $config
 * @property string $schedule
 * @property string $status
 * @property string|null $last_run_at
 * @property string|null $next_run_at
 * @property float|null $amount
 * @property string $currency
 * @property string|null $due_date
 * @property string|null $completed_at
 * @property string|null $notification_channels
 * @property string|null $notification_recipients
 * @property int $cooldown_minutes
 * @property string|null $last_notification_at
 * @property string|null $last_state
 * @property int $created_at
 * @property int $updated_at
 *
 * @property TaskExecution[] $executions
 * @property NotificationQueue[] $notifications
 * @property TaskHistory[] $history
 */
class Task extends ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%tasks}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['name', 'parser_class', 'schedule'], 'required'],
            [['name', 'parser_class', 'fetcher_class'], 'string', 'max' => 255],
            [['category'], 'string', 'max' => 100],
            [['config', 'notification_channels', 'notification_recipients', 'last_state'], 'string'],
            [['schedule'], 'string', 'max' => 100],
            [['status'], 'in', 'range' => ['active', 'paused', 'completed', 'archived']],
            [['amount'], 'number'],
            [['currency'], 'string', 'max' => 3],
            [['cooldown_minutes'], 'integer', 'min' => 1],
            [['due_date'], 'date', 'format' => 'php:Y-m-d'],
            [['last_run_at', 'next_run_at', 'completed_at', 'last_notification_at'], 'safe'],
            // Domyślne wartości
            [['status'], 'default', 'value' => 'active'],
            [['currency'], 'default', 'value' => 'PLN'],
            [['cooldown_minutes'], 'default', 'value' => 60],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'name' => 'Nazwa zadania',
            'category' => 'Kategoria',
            'fetcher_class' => 'Fetcher',
            'parser_class' => 'Parser',
            'config' => 'Konfiguracja',
            'schedule' => 'Harmonogram',
            'status' => 'Status',
            'amount' => 'Kwota',
            'currency' => 'Waluta',
            'due_date' => 'Termin',
            'completed_at' => 'Wykonano',
            'cooldown_minutes' => 'Cooldown (minuty)',
            'created_at' => 'Utworzono',
            'updated_at' => 'Zaktualizowano',
        ];
    }

    /**
     * Relacje
     */
    public function getExecutions() {
        return $this->hasMany(TaskExecution::class, ['task_id' => 'id'])
                        ->orderBy(['started_at' => SORT_DESC]);
    }

    public function getNotifications() {
        return $this->hasMany(NotificationQueue::class, ['task_id' => 'id'])
                        ->orderBy(['created_at' => SORT_DESC]);
    }

    public function getHistory() {
        return $this->hasMany(TaskHistory::class, ['task_id' => 'id'])
                        ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Zwraca ostatnie wykonanie
     */
    public function getLastExecution() {
        return $this->getExecutions()->one();
    }

    /**
     * Zwraca konfigurację jako tablicę
     */
    public function getConfigArray() {
        return $this->config ? json_decode($this->config, true) : [];
    }

    /**
     * Ustawia konfigurację z tablicy
     */
    public function setConfigArray($config) {
        $this->config = json_encode($config);
    }

    /**
     * Zwraca ostatni stan
     */
    public function getLastState() {
        return $this->last_state ? json_decode($this->last_state, true) : [];
    }

    /**
     * Zapisuje aktualny stan
     */
    public function saveState($state) {
        $this->last_state = json_encode($state);
        $this->save(false, ['last_state']);
    }

    /**
     * Oznacza task jako wykonany
     */
    public function markAsCompleted() {
        $this->status = 'completed';
        $this->completed_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * Anuluje wykonanie
     */
    public function markAsUncompleted() {
        $this->status = 'active';
        $this->completed_at = null;
        return $this->save();
    }

    /**
     * Aktualizuje czas ostatniego powiadomienia
     */
    public function updateLastNotificationTime() {
        $this->last_notification_at = date('Y-m-d H:i:s');
        $this->save(false, ['last_notification_at']);
    }

    /**
     * Sprawdza czy task powinien się uruchomić teraz
     */
    public function shouldRun() {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->schedule === 'manual') {
            return false;
        }

        if (!$this->next_run_at) {
            return true; // Nigdy nie był uruchamiany
        }

        return strtotime($this->next_run_at) <= time();
    }

    /**
     * Eksportuje do JSON
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true) {
        $data = parent::toArray($fields, $expand, $recursive);

        // Dodaj ludzkie nazwy dla klas
        if (isset($data['parser_class'])) {
            $parserClass = '\\app\\components\\parsers\\' . $data['parser_class'];
            if (class_exists($parserClass)) {
                $data['parser_display_name'] = $parserClass::getDisplayName();
            }
        }

        return $data;
    }

    /**
     * Hook before save - zapisz historię zmian
     */
    public function afterSave($insert, $changedAttributes) {
        parent::afterSave($insert, $changedAttributes);

        // Zapisz historię
        if (!$insert && !empty($changedAttributes)) {
            TaskHistory::recordChange($this, $insert ? 'created' : 'updated', $changedAttributes);
        }
    }

    /**
     * Sprawdza czy task powinien się uruchomić teraz (według cron schedule)
     * 
     * @return bool
     */
    public function shouldRunNow() {
        // Jeśli schedule to "manual", nigdy nie uruchamiaj automatycznie
        if ($this->schedule === 'manual') {
            return false;
        }

        // Użyj biblioteki cron-expression do sprawdzenia
        try {
            $cron = new \Cron\CronExpression($this->schedule);

            // Sprawdź czy task był już uruchomiony w tym oknie czasowym
            if ($this->last_run_at) {
                $lastRun = new \DateTime($this->last_run_at);
                $now = new \DateTime();

                // Jeśli ostatnie uruchomienie było w tym samym "oknie" crona, pomiń
                if ($cron->getNextRunDate($lastRun)->getTimestamp() > $now->getTimestamp()) {
                    return false;
                }
            }

            // Sprawdź czy "teraz" pasuje do wyrażenia cron
            return $cron->isDue();
        } catch (\Exception $e) {
            \Yii::error("Invalid cron expression for task #{$this->id}: {$this->schedule} - {$e->getMessage()}", __METHOD__);
            return false;
        }
    }

    /**
     * Zwraca czas następnego uruchomienia
     * 
     * @return string
     */
    public function getNextRunTime() {
        if ($this->schedule === 'manual') {
            return 'Manual only';
        }

        try {
            $cron = new \Cron\CronExpression($this->schedule);
            return $cron->getNextRunDate()->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return 'Invalid schedule';
        }
    }

    /**
     * Zwraca czytelny opis harmonogramu
     * 
     * @return string
     */
    public function getScheduleDescription() {
        if ($this->schedule === 'manual') {
            return 'Tylko ręcznie';
        }

        // Popularne wzorce
        $patterns = [
            '* * * * *' => 'Co minutę',
            '*/5 * * * *' => 'Co 5 minut',
            '*/15 * * * *' => 'Co 15 minut',
            '0 * * * *' => 'Co godzinę',
            '0 0 * * *' => 'Codziennie o północy',
            '0 9 * * *' => 'Codziennie o 9:00',
            '0 12 * * *' => 'Codziennie o 12:00',
            '0 0 * * 0' => 'Każdą niedzielę o północy',
            '0 0 1 * *' => 'Pierwszego dnia miesiąca',
        ];

        if (isset($patterns[$this->schedule])) {
            return $patterns[$this->schedule];
        }

        return $this->schedule;
    }

}

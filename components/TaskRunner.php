<?php

namespace app\components;

use Yii;
use app\models\Task;
use app\models\TaskExecution;
use app\models\NotificationQueue;

/**
 * TaskRunner - główny komponent uruchamiający taski
 */
class TaskRunner
{
    /** @var Task */
    private $task;
    
    /** @var TaskExecution */
    private $execution;
    
    /**
     * @param Task $task
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }
    
    /**
     * Uruchamia task
     * 
     * @return TaskExecution
     */
    public function run()
    {
        // Rozpocznij execution
        $this->execution = TaskExecution::start($this->task->id);
        
        try {
            // 1. FETCH - pobierz dane
            $rawData = $this->fetchStage();
            
            // 2. PARSE - przetwórz dane
            $parsedData = $this->parseStage($rawData);
            
            // 3. EVALUATE - oceń warunki i zdecyduj o powiadomieniach
            $notifications = $this->evaluateStage($parsedData);
            
            // 4. NOTIFY - dodaj do kolejki powiadomień
            if (!empty($notifications)) {
                $this->notifyStage($notifications);
            }
            
            // Zakończ sukcedem
            $this->execution->complete();
            
            // Aktualizuj task
            $this->updateTaskAfterExecution();
            
        } catch (\Exception $e) {
            // Zapisz błąd
            $this->execution->fail($e);
            
            Yii::error([
                'message' => 'Task execution failed',
                'task_id' => $this->task->id,
                'task_name' => $this->task->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], __METHOD__);
        }
        
        return $this->execution;
    }
    
    /**
     * Stage 1: FETCH - pobierz dane
     */
    private function fetchStage()
    {
        $this->execution->setStage('fetch');
        
        // Jeśli nie ma fetchera, zwróć puste dane
        if (!$this->task->fetcher_class) {
            $fetcherClass = '\\app\\components\\fetchers\\EmptyFetcher';
        } else {
            $fetcherClass = '\\app\\components\\fetchers\\' . $this->task->fetcher_class;
        }
        
        if (!class_exists($fetcherClass)) {
            throw new \Exception("Fetcher class not found: {$fetcherClass}");
        }
        
        $fetcher = new $fetcherClass($this->task);
        $rawData = $fetcher->fetch();
        
        // Zapisz surowe dane
        $this->execution->saveRawData($rawData);
        
        return $rawData;
    }
    
    /**
     * Stage 2: PARSE - przetwórz dane
     */
    private function parseStage($rawData)
    {
        $this->execution->setStage('parse');
        
        $parserClass = '\\app\\components\\parsers\\' . $this->task->parser_class;
        
        if (!class_exists($parserClass)) {
            throw new \Exception("Parser class not found: {$parserClass}");
        }
        
        $parser = new $parserClass($this->task, $this->execution);
        $parsedData = $parser->parse($rawData);
        
        // Zapisz przetworzone dane
        $this->execution->saveParsedData($parsedData);
        
        // Zapisz stan w tasku (dla wykrywania zmian)
        $this->task->saveState($parsedData);
        
        return $parsedData;
    }
    
    /**
     * Stage 3: EVALUATE - oceń warunki
     */
    private function evaluateStage($parsedData)
    {
        $this->execution->setStage('evaluate');
        
        $parserClass = '\\app\\components\\parsers\\' . $this->task->parser_class;
        $parser = new $parserClass($this->task, $this->execution);
        
        $notifications = $parser->evaluate($parsedData);
        
        // Zapisz wynik ewaluacji
        $this->execution->saveEvaluationResult([
            'notifications_count' => count($notifications),
            'notifications' => $notifications,
        ]);
        
        return $notifications;
    }
    
    /**
     * Stage 4: NOTIFY - dodaj do kolejki
     */
    private function notifyStage($notifications)
    {
        $this->execution->setStage('notify');
        
        foreach ($notifications as $notification) {
            NotificationQueue::create(
                $this->task->id,
                $this->execution->id,
                $notification,
                $this->task
            );
        }
        
        // Aktualizuj czas ostatniego powiadomienia
        $this->task->updateLastNotificationTime();
    }
    
    /**
     * Aktualizuj task po wykonaniu
     */
    private function updateTaskAfterExecution()
    {
        $this->task->last_run_at = date('Y-m-d H:i:s');
        
        // Oblicz next_run_at
        if ($this->task->schedule !== 'manual') {
            $this->task->next_run_at = $this->calculateNextRun($this->task->schedule);
        }
        
        $this->task->save(false, ['last_run_at', 'next_run_at']);
    }
    
    /**
     * Oblicza następne wykonanie na podstawie cron expression
     */
    private function calculateNextRun($schedule)
    {
        try {
            $cron = \Cron\CronExpression::factory($schedule);
            $nextRun = $cron->getNextRunDate();
            return $nextRun->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Yii::warning([
                'message' => 'Invalid cron expression',
                'task_id' => $this->task->id,
                'schedule' => $schedule,
                'error' => $e->getMessage(),
            ], __METHOD__);
            
            // Jeśli błędny cron, ustaw na za godzinę
            return date('Y-m-d H:i:s', strtotime('+1 hour'));
        }
    }
}

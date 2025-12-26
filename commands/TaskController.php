<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\Task;
use app\components\TaskRunner;

/**
 * Console controller dla zarządzania taskami
 */
class TaskController extends Controller
{
    /**
     * Uruchamia zaplanowane taski (według cron schedule)
     * 
     * Użycie: php yii task/run-scheduled
     */
    public function actionRunScheduled()
    {
        $this->stdout("Checking for scheduled tasks...\n", \yii\helpers\Console::FG_CYAN);
        
        // Znajdź aktywne taski
        $tasks = Task::find()
            ->where(['status' => 'active'])
            ->andWhere(['!=', 'schedule', 'manual'])
            ->all();
        
        if (empty($tasks)) {
            $this->stdout("No active tasks found.\n", \yii\helpers\Console::FG_YELLOW);
            return ExitCode::OK;
        }
        
        $this->stdout("Found " . count($tasks) . " active tasks\n\n");
        
        $executed = 0;
        $skipped = 0;
        $failed = 0;
        
        foreach ($tasks as $task) {
            $this->stdout("Task #{$task->id}: {$task->name}\n", \yii\helpers\Console::BOLD);
            $this->stdout("  Schedule: {$task->schedule}\n");
            
            // Sprawdź czy task powinien się uruchomić
            if (!$task->shouldRunNow()) {
                $nextRun = $task->getNextRunTime();
                $this->stdout("  Status: Not due yet (next: {$nextRun})\n", \yii\helpers\Console::FG_YELLOW);
                $skipped++;
                continue;
            }
            
            // Uruchom task
            $this->stdout("  Status: Running...\n", \yii\helpers\Console::FG_GREEN);
            
            try {
                $runner = new TaskRunner($task);
                $execution = $runner->run();
                
                if ($execution->isSuccess()) {
                    $this->stdout("  ✓ Completed successfully\n", \yii\helpers\Console::FG_GREEN);
                    $executed++;
                } else {
                    $this->stdout("  ✗ Failed: {$execution->error_message}\n", \yii\helpers\Console::FG_RED);
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->stdout("  ✗ Exception: {$e->getMessage()}\n", \yii\helpers\Console::FG_RED);
                $failed++;
            }
            
            $this->stdout("\n");
        }
        
        // Podsumowanie
        $this->stdout("=== Summary ===\n", \yii\helpers\Console::BOLD);
        $this->stdout("Executed: {$executed}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("Skipped: {$skipped}\n", \yii\helpers\Console::FG_YELLOW);
        $this->stdout("Failed: {$failed}\n", \yii\helpers\Console::FG_RED);
        
        return ExitCode::OK;
    }
    
    /**
 * Uruchamia konkretny task (ignoruje harmonogram)
 * 
 * Użycie: 
 *   php yii task/run 1          - uruchom task #1
 *   php yii task/run --all      - uruchom wszystkie active taski
 * 
 * @param int|null $id ID taska do uruchomienia
 */
public function actionRun($id = null)
{
    // Jeśli użyto flagi --all
    if ($this->all ?? false) {
        return $this->runAllTasks();
    }
    
    // Jeśli nie podano ID
    if ($id === null) {
        $this->stderr("Error: Missing required argument: id\n\n", \yii\helpers\Console::FG_RED);
        $this->stdout("Usage:\n");
        $this->stdout("  php yii task/run <id>     Run specific task\n");
        $this->stdout("  php yii task/run --all    Run all active tasks\n");
        $this->stdout("  php yii task/list         Show all tasks\n");
        return ExitCode::USAGE;
    }
    
    $task = Task::findOne($id);
    
    if (!$task) {
        $this->stderr("Task #{$id} not found.\n", \yii\helpers\Console::FG_RED);
        return ExitCode::DATAERR;
    }
    
    // ... reszta kodu bez zmian ...
}

/**
 * Flaga --all dla uruchomienia wszystkich tasków
 */
public $all = false;

/**
 * @inheritdoc
 */
public function options($actionID)
{
    $options = parent::options($actionID);
    if ($actionID === 'run') {
        $options[] = 'all';
    }
    return $options;
}

/**
 * Uruchamia wszystkie aktywne taski
 */
private function runAllTasks()
{
    $tasks = Task::find()
        ->where(['status' => 'active'])
        ->all();
    
    if (empty($tasks)) {
        $this->stdout("No active tasks found.\n", \yii\helpers\Console::FG_YELLOW);
        return ExitCode::OK;
    }
    
    $this->stdout("Running " . count($tasks) . " active tasks...\n\n", \yii\helpers\Console::BOLD);
    
    $success = 0;
    $failed = 0;
    
    foreach ($tasks as $task) {
        $this->stdout("Task #{$task->id}: {$task->name} ... ", \yii\helpers\Console::FG_CYAN);
        
        try {
            $runner = new \app\components\TaskRunner($task);
            $execution = $runner->run();
            
            if ($execution->isSuccess()) {
                $this->stdout("✓ OK\n", \yii\helpers\Console::FG_GREEN);
                $success++;
            } else {
                $this->stdout("✗ FAILED\n", \yii\helpers\Console::FG_RED);
                $failed++;
            }
        } catch (\Exception $e) {
            $this->stdout("✗ ERROR: {$e->getMessage()}\n", \yii\helpers\Console::FG_RED);
            $failed++;
        }
    }
    
    $this->stdout("\n=== Summary ===\n", \yii\helpers\Console::BOLD);
    $this->stdout("Success: {$success}\n", \yii\helpers\Console::FG_GREEN);
    $this->stdout("Failed: {$failed}\n", \yii\helpers\Console::FG_RED);
    
    return $failed === 0 ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
}
    
    /**
     * Lista wszystkich tasków
     * 
     * Użycie: php yii task/list
     */
    public function actionList()
    {
        $tasks = Task::find()->orderBy(['id' => SORT_ASC])->all();
        
        if (empty($tasks)) {
            $this->stdout("No tasks found.\n", \yii\helpers\Console::FG_YELLOW);
            return ExitCode::OK;
        }
        
        $this->stdout(sprintf("%-5s %-30s %-20s %-15s %-10s\n",
            'ID', 'Name', 'Parser', 'Schedule', 'Status'
        ), \yii\helpers\Console::BOLD);
        
        $this->stdout(str_repeat('-', 85) . "\n");
        
        foreach ($tasks as $task) {
            $color = match($task->status) {
                'active' => \yii\helpers\Console::FG_GREEN,
                'paused' => \yii\helpers\Console::FG_YELLOW,
                'completed' => \yii\helpers\Console::FG_CYAN,
                default => \yii\helpers\Console::FG_GREY,
            };
            
            $this->stdout(sprintf("%-5s %-30s %-20s %-15s %-10s\n",
                $task->id,
                substr($task->name, 0, 30),
                substr($task->parser_class, 0, 20),
                substr($task->schedule, 0, 15),
                $task->status
            ), $color);
        }
        
        $this->stdout("\nTotal: " . count($tasks) . " tasks\n");
        
        return ExitCode::OK;
    }
}
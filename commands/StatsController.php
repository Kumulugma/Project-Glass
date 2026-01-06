<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\components\StatsReporter;

/**
 * Console controller dla wysyłania statystyk
 * 
 * Użycie:
 * php yii stats/send          - wyślij statystyki do API
 * php yii stats/show          - pokaż aktualne statystyki
 * php yii stats/show-detailed - pokaż szczegółowe statystyki
 */
class StatsController extends Controller
{
    /**
     * Wysyła statystyki do zewnętrznego API
     * 
     * Użycie: php yii stats/send
     */
    public function actionSend()
    {
        $this->stdout("Wysyłanie statystyk do API...\n", \yii\helpers\Console::FG_CYAN);
        
        // Pobierz i wyświetl statystyki
        $stats = StatsReporter::collectStats();
        
        $this->stdout("\nStatystyki do wysłania:\n", \yii\helpers\Console::BOLD);
        $this->stdout("  Zapytania (task_executions): {$stats['zapytania']}\n");
        $this->stdout("  Powiadomienia: {$stats['powiadomienia']}\n");
        $this->stdout("  Ostatnia data: {$stats['ostatnia_data']}\n\n");
        
        // Wyślij
        $result = StatsReporter::sendStats();
        
        if ($result['success']) {
            $this->stdout("✓ Statystyki wysłane pomyślnie!\n", \yii\helpers\Console::FG_GREEN);
            $this->stdout("  Odpowiedź API: {$result['response']}\n");
            return ExitCode::OK;
        } else {
            $this->stderr("✗ Błąd podczas wysyłania statystyk!\n", \yii\helpers\Console::FG_RED);
            $this->stderr("  Błąd: {$result['error']}\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
    
    /**
     * Wyświetla aktualne statystyki bez wysyłania
     * 
     * Użycie: php yii stats/show
     */
    public function actionShow()
    {
        $stats = StatsReporter::collectStats();
        
        $this->stdout("\n=== Aktualne statystyki ===\n", \yii\helpers\Console::BOLD);
        $this->stdout("\n");
        $this->stdout(sprintf("  %-30s %s\n", 
            "Zapytania (task_executions):", 
            $this->colorNumber($stats['zapytania'])
        ));
        $this->stdout(sprintf("  %-30s %s\n", 
            "Powiadomienia:", 
            $this->colorNumber($stats['powiadomienia'])
        ));
        $this->stdout(sprintf("  %-30s %s\n", 
            "Ostatnie wykonanie:", 
            $stats['ostatnia_data'] ?? 'brak'
        ));
        $this->stdout("\n");
        
        return ExitCode::OK;
    }
    
    /**
     * Wyświetla szczegółowe statystyki
     * 
     * Użycie: php yii stats/show-detailed
     */
    public function actionShowDetailed()
    {
        $stats = StatsReporter::getDetailedStats();
        
        $this->stdout("\n=== Szczegółowe statystyki ===\n\n", \yii\helpers\Console::BOLD);
        
        // Task Executions
        $this->stdout("TASK EXECUTIONS:\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout(sprintf("  %-25s %s\n", "Wszystkie:", $this->colorNumber($stats['total_executions'])));
        $this->stdout(sprintf("  %-25s %s\n", "Udane:", $this->colorNumber($stats['successful_executions'], \yii\helpers\Console::FG_GREEN)));
        $this->stdout(sprintf("  %-25s %s\n", "Nieudane:", $this->colorNumber($stats['failed_executions'], \yii\helpers\Console::FG_RED)));
        
        if ($stats['last_execution']) {
            $lastDate = date('Y-m-d H:i:s', $stats['last_execution']->started_at);
            $this->stdout(sprintf("  %-25s %s\n", "Ostatnie:", $lastDate));
        }
        
        $this->stdout("\n");
        
        // Notifications
        $this->stdout("POWIADOMIENIA:\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout(sprintf("  %-25s %s\n", "Wszystkie:", $this->colorNumber($stats['total_notifications'])));
        $this->stdout(sprintf("  %-25s %s\n", "Wysłane:", $this->colorNumber($stats['sent_notifications'], \yii\helpers\Console::FG_GREEN)));
        $this->stdout(sprintf("  %-25s %s\n", "Oczekujące:", $this->colorNumber($stats['pending_notifications'], \yii\helpers\Console::FG_YELLOW)));
        $this->stdout(sprintf("  %-25s %s\n", "Nieudane:", $this->colorNumber($stats['failed_notifications'], \yii\helpers\Console::FG_RED)));
        
        $this->stdout("\n");
        
        return ExitCode::OK;
    }
    
    /**
     * Helper do kolorowania liczb
     */
    private function colorNumber($number, $color = null)
    {
        if ($color === null) {
            $color = $number > 0 ? \yii\helpers\Console::FG_GREEN : \yii\helpers\Console::FG_GREY;
        }
        
        return \yii\helpers\Console::ansiFormat($number, [$color]);
    }
}
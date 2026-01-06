<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Archiwizacja i zarządzanie starymi danymi
 * 
 * Użycie:
 * php yii archive/daily - codzienne archiwizowanie (uruchamiać o północy)
 * php yii archive/weekly-upload - cotygodniowy upload na S3 (uruchamiać w weekend)
 * php yii archive/stats - statystyki archiwów
 * php yii archive/cleanup - czyszczenie starych lokalnych archiwów po uploaderze
 */
class ArchiveController extends Controller
{
    /**
     * Codzienne archiwizowanie danych
     * Uruchamiać o północy przez cron
     */
    public function actionDaily()
    {
        $this->stdout("=== Daily Archive Process ===\n\n");
        
        $archiver = Yii::$app->archiver;
        
        $this->stdout("Archiving old data...\n");
        $stats = $archiver->archiveOldData();
        
        $this->stdout("\n--- Task Executions ---\n");
        $this->stdout("Archived: {$stats['task_executions']['archived']}\n");
        $this->stdout("Deleted: {$stats['task_executions']['deleted']}\n");
        $this->stdout("Cutoff date: {$stats['task_executions']['cutoff_date']}\n");
        
        $this->stdout("\n--- Fetch Results ---\n");
        $this->stdout("Archived: {$stats['fetch_results']['archived']}\n");
        $this->stdout("Deleted: {$stats['fetch_results']['deleted']}\n");
        $this->stdout("Cutoff date: {$stats['fetch_results']['cutoff_date']}\n");
        
        $this->stdout("\n✓ Daily archive completed\n");
        
        return ExitCode::OK;
    }
    
    /**
     * Cotygodniowy upload archiwów na S3
     * Uruchamiać raz w tygodniu (np. niedziela w nocy)
     */
    public function actionWeeklyUpload()
    {
        $this->stdout("=== Weekly S3 Upload Process ===\n\n");
        
        // Sprawdź czy S3 jest włączone
        $enabled = \app\models\Setting::get('s3_enabled', false);
        if (!$enabled) {
            $this->stdout("❌ S3 integration is not enabled\n");
            return ExitCode::CONFIG;
        }
        
        $uploader = Yii::$app->s3Uploader;
        
        // Test połączenia
        $this->stdout("Testing S3 connection...\n");
        $testResult = $uploader->testConnection();
        
        if (!$testResult['success']) {
            $this->stdout("❌ Connection failed: {$testResult['message']}\n");
            return ExitCode::UNAVAILABLE;
        }
        
        $this->stdout("✓ Connection successful\n\n");
        
        // Upload wszystkich archiwów
        $this->stdout("Uploading archives to S3...\n");
        $stats = $uploader->uploadAllArchives();
        
        $this->stdout("\n--- Upload Statistics ---\n");
        $this->stdout("Uploaded: {$stats['uploaded']}\n");
        $this->stdout("Skipped (already on S3): {$stats['skipped']}\n");
        $this->stdout("Failed: {$stats['failed']}\n");
        $this->stdout("Total size: " . round($stats['total_size'] / 1024 / 1024, 2) . " MB\n");
        
        // Loguj
        \app\models\ArchiveLog::log('upload', 'weekly', $stats);
        
        $this->stdout("\n✓ Weekly upload completed\n");
        
        return ExitCode::OK;
    }
    
    /**
     * Wyświetla statystyki archiwów
     */
    public function actionStats()
    {
        $this->stdout("=== Archive Statistics ===\n\n");
        
        $archiver = Yii::$app->archiver;
        $stats = $archiver->getArchiveStats();
        
        $this->stdout("--- Local Archives ---\n");
        $this->stdout("Task Executions: {$stats['execution_archives_count']}\n");
        $this->stdout("Fetch Results: {$stats['fetch_result_archives_count']}\n");
        $this->stdout("Total archives: {$stats['total_archives']}\n");
        $this->stdout("Total size: {$stats['total_size_mb']} MB\n");
        
        // Lista najnowszych archiwów
        $this->stdout("\n--- Recent Task Execution Archives ---\n");
        $executionArchives = $archiver->listExecutionArchives();
        $count = 0;
        foreach ($executionArchives as $archive) {
            if ($count++ >= 5) break;
            $this->stdout("{$archive['date']}: {$archive['size_mb']} MB\n");
        }
        
        $this->stdout("\n--- Recent Fetch Result Archives ---\n");
        $fetchArchives = $archiver->listFetchResultArchives();
        $count = 0;
        foreach ($fetchArchives as $archive) {
            if ($count++ >= 5) break;
            $this->stdout("{$archive['date']}: {$archive['size_mb']} MB\n");
        }
        
        // S3 stats jeśli włączone
        $s3Enabled = \app\models\Setting::get('s3_enabled', false);
        if ($s3Enabled) {
            try {
                $uploader = Yii::$app->s3Uploader;
                $uploader->connect();
                
                $this->stdout("\n--- S3 Archives ---\n");
                $s3Archives = $uploader->listS3Archives();
                $this->stdout("Total on S3: " . count($s3Archives) . "\n");
                
                $totalSize = 0;
                foreach ($s3Archives as $archive) {
                    $totalSize += $archive['size'];
                }
                $this->stdout("Total S3 size: " . round($totalSize / 1024 / 1024, 2) . " MB\n");
                
            } catch (\Exception $e) {
                $this->stdout("\n❌ Could not fetch S3 stats: {$e->getMessage()}\n");
            }
        }
        
        return ExitCode::OK;
    }
    
    /**
     * Czyszczenie starych lokalnych archiwów (które są już na S3)
     * 
     * @param int $daysOld Usuń lokalne archiwa starsze niż X dni (domyślnie 90)
     */
    public function actionCleanup($daysOld = 90)
    {
        $this->stdout("=== Cleanup Local Archives ===\n\n");
        
        $s3Enabled = \app\models\Setting::get('s3_enabled', false);
        if (!$s3Enabled) {
            $this->stdout("❌ S3 is not enabled. Cleanup requires S3 backup.\n");
            return ExitCode::CONFIG;
        }
        
        $archiver = Yii::$app->archiver;
        $uploader = Yii::$app->s3Uploader;
        
        // Pobierz listę archiwów na S3
        $this->stdout("Fetching S3 archive list...\n");
        $s3Archives = $uploader->listS3Archives();
        
        $s3Keys = [];
        foreach ($s3Archives as $archive) {
            $key = $archive['type'] . '/' . $archive['date'];
            $s3Keys[$key] = true;
        }
        
        $this->stdout("Found " . count($s3Keys) . " archives on S3\n\n");
        
        // Sprawdź lokalne archiwa
        $cutoffDate = date('Y-m-d', time() - ($daysOld * 86400));
        $deleted = 0;
        
        $this->stdout("Checking task execution archives...\n");
        $executionArchives = $archiver->listExecutionArchives();
        foreach ($executionArchives as $archive) {
            if ($archive['date'] >= $cutoffDate) {
                continue; // Za nowe
            }
            
            $key = 'task_executions/' . $archive['date'];
            if (isset($s3Keys[$key])) {
                $this->stdout("Deleting local: {$archive['date']} ({$archive['size_mb']} MB)\n");
                $archiver->deleteArchive('task_executions', $archive['date']);
                $deleted++;
            }
        }
        
        $this->stdout("\nChecking fetch result archives...\n");
        $fetchArchives = $archiver->listFetchResultArchives();
        foreach ($fetchArchives as $archive) {
            if ($archive['date'] >= $cutoffDate) {
                continue; // Za nowe
            }
            
            $key = 'fetch_results/' . $archive['date'];
            if (isset($s3Keys[$key])) {
                $this->stdout("Deleting local: {$archive['date']} ({$archive['size_mb']} MB)\n");
                $archiver->deleteArchive('fetch_results', $archive['date']);
                $deleted++;
            }
        }
        
        $this->stdout("\n✓ Cleanup completed. Deleted {$deleted} local archives.\n");
        
        // Loguj
        \app\models\ArchiveLog::log('delete', 'manual', [
            'deleted' => $deleted,
            'cutoff_date' => $cutoffDate,
        ]);
        
        return ExitCode::OK;
    }
    
    /**
     * Manualne archiwizowanie konkretnego dnia
     * 
     * @param string $date Data w formacie Y-m-d
     */
    public function actionArchiveDate($date)
    {
        $this->stdout("=== Archive Specific Date: {$date} ===\n\n");
        
        // Walidacja daty
        $timestamp = strtotime($date);
        if (!$timestamp) {
            $this->stdout("❌ Invalid date format. Use Y-m-d (e.g., 2026-01-05)\n");
            return ExitCode::DATAERR;
        }
        
        $archiver = Yii::$app->archiver;
        
        // Archiwizuj task executions dla tego dnia
        $this->stdout("Archiving task executions for {$date}...\n");
        $startOfDay = strtotime($date . ' 00:00:00');
        $endOfDay = strtotime($date . ' 23:59:59');
        
        $executions = \app\models\TaskExecution::find()
            ->where(['>=', 'started_at', $startOfDay])
            ->andWhere(['<=', 'started_at', $endOfDay])
            ->all();
        
        if (!empty($executions)) {
            $batch = [];
            foreach ($executions as $execution) {
                $batch[] = [
                    'id' => $execution->id,
                    'task_id' => $execution->task_id,
                    'status' => $execution->status,
                    'stage' => $execution->stage,
                    'started_at' => $execution->started_at,
                    'finished_at' => $execution->finished_at,
                    'duration_ms' => $execution->duration_ms,
                    'raw_data' => $execution->raw_data,
                    'parsed_data' => $execution->parsed_data,
                    'evaluation_result' => $execution->evaluation_result,
                    'error_message' => $execution->error_message,
                    'error_trace' => $execution->error_trace,
                ];
            }
            
            $filename = $archiver->archiveDir . '/task_executions/' . $date . '.jsonl.gz';
            $gz = gzopen($filename, 'wb9');
            foreach ($batch as $record) {
                gzwrite($gz, json_encode($record, JSON_UNESCAPED_UNICODE) . "\n");
            }
            gzclose($gz);
            
            $this->stdout("Archived " . count($batch) . " task executions\n");
        } else {
            $this->stdout("No task executions found for this date\n");
        }
        
        // Archiwizuj fetch results dla tego dnia
        $this->stdout("\nArchiving fetch results for {$date}...\n");
        
        $results = \app\models\FetchResult::find()
            ->where(['>=', 'fetched_at', $startOfDay])
            ->andWhere(['<=', 'fetched_at', $endOfDay])
            ->all();
        
        if (!empty($results)) {
            $batch = [];
            foreach ($results as $result) {
                $batch[] = [
                    'id' => $result->id,
                    'task_id' => $result->task_id,
                    'execution_id' => $result->execution_id,
                    'fetcher_class' => $result->fetcher_class,
                    'source_info' => $result->source_info,
                    'raw_data' => $result->raw_data,
                    'data_size' => $result->data_size,
                    'rows_count' => $result->rows_count,
                    'status' => $result->status,
                    'error_message' => $result->error_message,
                    'fetched_at' => $result->fetched_at,
                ];
            }
            
            $filename = $archiver->archiveDir . '/fetch_results/' . $date . '.jsonl.gz';
            $gz = gzopen($filename, 'wb9');
            foreach ($batch as $record) {
                gzwrite($gz, json_encode($record, JSON_UNESCAPED_UNICODE) . "\n");
            }
            gzclose($gz);
            
            $this->stdout("Archived " . count($batch) . " fetch results\n");
        } else {
            $this->stdout("No fetch results found for this date\n");
        }
        
        $this->stdout("\n✓ Archive completed for {$date}\n");
        
        return ExitCode::OK;
    }
}
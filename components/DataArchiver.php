<?php

namespace app\components;

use Yii;
use yii\base\Component;
use app\models\TaskExecution;
use app\models\FetchResult;
use app\models\ArchiveLog;

/**
 * DataArchiver - komponent do archiwizacji starych danych z bazy
 * 
 * Funkcje:
 * - Archiwizacja task_executions i fetch_results do plików JSONL.GZ
 * - Automatyczne czyszczenie starych rekordów z bazy
 * - Optymalizacja tabel po usunięciu danych
 * - Wczytywanie zarchiwizowanych danych
 */
class DataArchiver extends Component
{
    /**
     * @var string Katalog archiwów
     */
    public $archiveDir = '@runtime/archives';
    
    /**
     * @var int Liczba dni po których dane są archiwizowane
     */
    public $archiveAfterDays = 2;
    
    /**
     * @var int Rozmiar batcha do przetwarzania
     */
    public $batchSize = 1000;
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        // Upewnij się że katalog istnieje
        $this->archiveDir = Yii::getAlias($this->archiveDir);
        if (!is_dir($this->archiveDir)) {
            mkdir($this->archiveDir, 0755, true);
        }
        
        // Utwórz podkatalogi
        foreach (['task_executions', 'fetch_results'] as $subdir) {
            $path = $this->archiveDir . '/' . $subdir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
    
    /**
     * Główna metoda archiwizacji - archiwizuje wszystkie stare dane
     * 
     * @return array Statystyki
     */
    public function archiveOldData()
    {
        $stats = [
            'task_executions' => $this->archiveTaskExecutions(),
            'fetch_results' => $this->archiveFetchResults(),
        ];
        
        // Optymalizuj tabele po usunięciu danych
        $optimizeStats = $this->optimizeTables();
        $stats['optimization'] = $optimizeStats;
        
        // Zaloguj operację
        ArchiveLog::log('archive', 'daily', $stats);
        
        return $stats;
    }
    
    /**
     * Optymalizuje tabele MySQL po usunięciu danych
     * Usuwa fragmentację i odzyskuje niewykorzystane miejsce
     * 
     * @return array Statystyki optymalizacji
     */
    protected function optimizeTables()
    {
        $startTime = microtime(true);
        $optimizedTables = [];
        $errors = [];
        
        // Lista tabel do optymalizacji
        $tables = [
            'archive_logs',
            'fetch_results',
            'migration',
            'notification_queue',
            'push_subscriptions',
            's3_transfers',
            'settings',
            'tasks',
            'task_executions',
            'task_history',
            'users',
            'user_logs',
        ];
        
        try {
            $db = Yii::$app->db;
            
            foreach ($tables as $table) {
                try {
                    // Użyj prefiksu tabeli z konfiguracji Yii
                    $fullTableName = $db->tablePrefix . $table;
                    
                    // Wykonaj OPTIMIZE TABLE
                    $db->createCommand("OPTIMIZE TABLE `{$fullTableName}`")->execute();
                    
                    $optimizedTables[] = $fullTableName;
                    
                    Yii::info("Optimized table: {$fullTableName}", __METHOD__);
                } catch (\Exception $e) {
                    $errors[] = [
                        'table' => $table,
                        'error' => $e->getMessage(),
                    ];
                    
                    Yii::warning("Failed to optimize table {$table}: " . $e->getMessage(), __METHOD__);
                }
            }
            
        } catch (\Exception $e) {
            Yii::error("Database optimization failed: " . $e->getMessage(), __METHOD__);
            $errors[] = [
                'general' => $e->getMessage(),
            ];
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'optimized_count' => count($optimizedTables),
            'optimized_tables' => $optimizedTables,
            'errors' => $errors,
            'duration_ms' => $duration,
        ];
    }
    
    /**
     * Archiwizuje stare task_executions
     * 
     * @return array Statystyki
     */
    public function archiveTaskExecutions()
    {
        $cutoffDate = time() - ($this->archiveAfterDays * 86400);
        $archived = 0;
        $deleted = 0;
        
        // Pobierz stare rekordy pogrupowane po dniach
        $query = TaskExecution::find()
            ->where(['<', 'started_at', $cutoffDate])
            ->orderBy(['started_at' => SORT_ASC]);
        
        // Grupuj po dniach i archiwizuj
        $currentDate = null;
        $batch = [];
        
        foreach ($query->batch($this->batchSize) as $executions) {
            foreach ($executions as $execution) {
                $date = date('Y-m-d', $execution->started_at);
                
                // Jeśli zmienił się dzień, zapisz poprzedni batch
                if ($currentDate && $currentDate !== $date && !empty($batch)) {
                    $this->writeExecutionArchive($currentDate, $batch);
                    $archived += count($batch);
                    $batch = [];
                }
                
                $currentDate = $date;
                $batch[] = $this->serializeExecution($execution);
            }
        }
        
        // Zapisz ostatni batch
        if (!empty($batch)) {
            $this->writeExecutionArchive($currentDate, $batch);
            $archived += count($batch);
        }
        
        // Usuń zarchiwizowane rekordy z bazy
        if ($archived > 0) {
            $deleted = TaskExecution::deleteAll(['<', 'started_at', $cutoffDate]);
        }
        
        return [
            'archived' => $archived,
            'deleted' => $deleted,
            'cutoff_date' => date('Y-m-d H:i:s', $cutoffDate),
        ];
    }
    
    /**
     * Archiwizuje stare fetch_results
     * 
     * @return array Statystyki
     */
    public function archiveFetchResults()
    {
        $cutoffDate = time() - ($this->archiveAfterDays * 86400);
        $archived = 0;
        $deleted = 0;
        
        // Pobierz stare rekordy pogrupowane po dniach
        $query = FetchResult::find()
            ->where(['<', 'fetched_at', $cutoffDate])
            ->orderBy(['fetched_at' => SORT_ASC]);
        
        // Grupuj po dniach i archiwizuj
        $currentDate = null;
        $batch = [];
        
        foreach ($query->batch($this->batchSize) as $results) {
            foreach ($results as $result) {
                $date = date('Y-m-d', $result->fetched_at);
                
                // Jeśli zmienił się dzień, zapisz poprzedni batch
                if ($currentDate && $currentDate !== $date && !empty($batch)) {
                    $this->writeFetchResultArchive($currentDate, $batch);
                    $archived += count($batch);
                    $batch = [];
                }
                
                $currentDate = $date;
                $batch[] = $this->serializeFetchResult($result);
            }
        }
        
        // Zapisz ostatni batch
        if (!empty($batch)) {
            $this->writeFetchResultArchive($currentDate, $batch);
            $archived += count($batch);
        }
        
        // Usuń zarchiwizowane rekordy z bazy
        if ($archived > 0) {
            $deleted = FetchResult::deleteAll(['<', 'fetched_at', $cutoffDate]);
        }
        
        return [
            'archived' => $archived,
            'deleted' => $deleted,
            'cutoff_date' => date('Y-m-d H:i:s', $cutoffDate),
        ];
    }
    
    /**
     * Zapisuje archiwum task_executions
     * 
     * @param string $date Data (Y-m-d)
     * @param array $records Rekordy do zapisania
     */
    protected function writeExecutionArchive($date, $records)
    {
        $filename = $this->archiveDir . '/task_executions/' . $date . '.jsonl.gz';
        
        // Otwórz plik do dopisywania (może już istnieć)
        $gz = gzopen($filename, 'ab9');
        
        foreach ($records as $record) {
            gzwrite($gz, json_encode($record, JSON_UNESCAPED_UNICODE) . "\n");
        }
        
        gzclose($gz);
        
        Yii::info("Archived " . count($records) . " task executions to {$date}.jsonl.gz", __METHOD__);
    }
    
    /**
     * Zapisuje archiwum fetch_results
     * 
     * @param string $date Data (Y-m-d)
     * @param array $records Rekordy do zapisania
     */
    protected function writeFetchResultArchive($date, $records)
    {
        $filename = $this->archiveDir . '/fetch_results/' . $date . '.jsonl.gz';
        
        // Otwórz plik do dописywania
        $gz = gzopen($filename, 'ab9');
        
        foreach ($records as $record) {
            gzwrite($gz, json_encode($record, JSON_UNESCAPED_UNICODE) . "\n");
        }
        
        gzclose($gz);
        
        Yii::info("Archived " . count($records) . " fetch results to {$date}.jsonl.gz", __METHOD__);
    }
    
    /**
     * Serializuje TaskExecution do array
     */
    protected function serializeExecution(TaskExecution $execution)
    {
        return [
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
    
    /**
     * Serializuje FetchResult do array
     */
    protected function serializeFetchResult(FetchResult $result)
    {
        return [
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
    
    /**
     * Wczytuje task_executions z archiwum dla danego dnia
     * 
     * @param string $date Data (Y-m-d)
     * @return array|null Tablica rekordów lub null jeśli brak archiwum
     */
    public function loadExecutionsFromArchive($date)
    {
        $filename = $this->archiveDir . '/task_executions/' . $date . '.jsonl.gz';
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $records = [];
        $gz = gzopen($filename, 'r');
        
        while (!gzeof($gz)) {
            $line = gzgets($gz);
            if (trim($line)) {
                $records[] = json_decode($line, true);
            }
        }
        
        gzclose($gz);
        
        return $records;
    }
    
    /**
     * Wczytuje fetch_results z archiwum dla danego dnia
     * 
     * @param string $date Data (Y-m-d)
     * @return array|null Tablica rekordów lub null jeśli brak archiwum
     */
    public function loadFetchResultsFromArchive($date)
    {
        $filename = $this->archiveDir . '/fetch_results/' . $date . '.jsonl.gz';
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $records = [];
        $gz = gzopen($filename, 'r');
        
        while (!gzeof($gz)) {
            $line = gzgets($gz);
            if (trim($line)) {
                $records[] = json_decode($line, true);
            }
        }
        
        gzclose($gz);
        
        return $records;
    }
    
    /**
     * Lista dostępnych archiwów task_executions
     * 
     * @return array Tablica dat [date => filesize]
     */
    public function listExecutionArchives()
    {
        return $this->listArchives('task_executions');
    }
    
    /**
     * Lista dostępnych archiwów fetch_results
     * 
     * @return array Tablica dat [date => filesize]
     */
    public function listFetchResultArchives()
    {
        return $this->listArchives('fetch_results');
    }
    
    /**
     * Lista archiwów w podkatalogu
     * 
     * @param string $subdir Podkatalog (task_executions lub fetch_results)
     * @return array
     */
    protected function listArchives($subdir)
    {
        $dir = $this->archiveDir . '/' . $subdir;
        $archives = [];
        
        if (!is_dir($dir)) {
            return $archives;
        }
        
        $files = scandir($dir);
        foreach ($files as $file) {
            if (preg_match('/^(\d{4}-\d{2}-\d{2})\.jsonl\.gz$/', $file, $matches)) {
                $date = $matches[1];
                $archives[$date] = [
                    'date' => $date,
                    'filename' => $file,
                    'filepath' => $dir . '/' . $file,
                    'size' => filesize($dir . '/' . $file),
                    'size_mb' => round(filesize($dir . '/' . $file) / 1024 / 1024, 2),
                ];
            }
        }
        
        krsort($archives); // Sortuj od najnowszych
        
        return $archives;
    }
    
    /**
     * Usuwa archiwum dla danego dnia
     * 
     * @param string $type 'task_executions' lub 'fetch_results'
     * @param string $date Data (Y-m-d)
     * @return bool
     */
    public function deleteArchive($type, $date)
    {
        $filename = $this->archiveDir . '/' . $type . '/' . $date . '.jsonl.gz';
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return false;
    }
    
    /**
     * Zwraca statystyki archiwów
     * 
     * @return array
     */
    public function getArchiveStats()
    {
        $executionArchives = $this->listExecutionArchives();
        $fetchResultArchives = $this->listFetchResultArchives();
        
        $totalSize = 0;
        foreach ($executionArchives as $archive) {
            $totalSize += $archive['size'];
        }
        foreach ($fetchResultArchives as $archive) {
            $totalSize += $archive['size'];
        }
        
        return [
            'execution_archives_count' => count($executionArchives),
            'fetch_result_archives_count' => count($fetchResultArchives),
            'total_archives' => count($executionArchives) + count($fetchResultArchives),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
        ];
    }
}
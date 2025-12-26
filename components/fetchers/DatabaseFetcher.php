<?php

namespace app\components\fetchers;

use Yii;

/**
 * DatabaseFetcher - pobiera dane z wewnętrznej bazy danych
 * Używany do raportów, agregacji danych z innych tasków itp.
 */
class DatabaseFetcher extends AbstractFetcher
{
    /**
     * @inheritdoc
     */
    public function fetch()
    {
        $table = $this->config['table'] ?? null;
        $query = $this->config['query'] ?? null;
        
        if (!$table && !$query) {
            throw new \Exception('Wymagane: "table" lub "query" w konfiguracji');
        }
        
        try {
            if ($query) {
                // Wykonaj surowe zapytanie SQL
                $results = $this->executeRawQuery($query);
            } else {
                // Wykonaj proste zapytanie na tabeli
                $results = $this->executeTableQuery($table);
            }
            
            return [
                'source' => $table ?? 'custom_query',
                'query' => $query ?? null,
                'rows_count' => count($results),
                'data' => $results,
                'fetched_at' => time(),
                'success' => true,
            ];
            
        } catch (\Exception $e) {
            throw new \Exception("Database fetch failed: " . $e->getMessage());
        }
    }
    
    /**
     * Wykonuje surowe zapytanie SQL (tylko SELECT)
     * 
     * @param string $query
     * @return array
     * @throws \Exception
     */
    private function executeRawQuery($query)
    {
        // Bezpieczeństwo: tylko SELECT queries
        $query = trim($query);
        if (!preg_match('/^SELECT\s/i', $query)) {
            throw new \Exception('Tylko zapytania SELECT są dozwolone');
        }
        
        // Wykonaj zapytanie
        return Yii::$app->db->createCommand($query)->queryAll();
    }
    
    /**
     * Wykonuje zapytanie na tabeli z opcjonalnymi warunkami
     * 
     * @param string $table
     * @return array
     */
    private function executeTableQuery($table)
    {
        $select = $this->config['select'] ?? '*';
        $where = $this->config['where'] ?? [];
        $orderBy = $this->config['order_by'] ?? null;
        $limit = $this->config['limit'] ?? 100;
        
        $command = Yii::$app->db->createCommand()
            ->select($select)
            ->from($table);
        
        if (!empty($where)) {
            $command->where($where);
        }
        
        if ($orderBy) {
            $command->orderBy($orderBy);
        }
        
        if ($limit) {
            $command->limit($limit);
        }
        
        return $command->queryAll();
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        $errors = [];
        
        if (empty($this->config['table']) && empty($this->config['query'])) {
            $errors[] = 'Wymagane pole: "table" lub "query"';
        }
        
        if (!empty($this->config['query'])) {
            $query = trim($this->config['query']);
            if (!preg_match('/^SELECT\s/i', $query)) {
                $errors[] = 'Tylko zapytania SELECT są dozwolone';
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
            'table' => [
                'type' => 'text',
                'label' => 'Tabela',
                'placeholder' => 'tasks',
                'help' => 'Nazwa tabeli (bez prefiksu). Wymagane jeśli nie podano "query"',
            ],
            'select' => [
                'type' => 'text',
                'label' => 'Kolumny SELECT',
                'placeholder' => '*',
                'default' => '*',
                'help' => 'Kolumny do pobrania (używane z "table")',
            ],
            'where' => [
                'type' => 'textarea',
                'label' => 'Warunki WHERE (JSON)',
                'placeholder' => '{"status": "active", "category": "rachunki"}',
                'help' => 'Warunki WHERE jako JSON object (używane z "table")',
            ],
            'order_by' => [
                'type' => 'text',
                'label' => 'ORDER BY',
                'placeholder' => 'created_at DESC',
                'help' => 'Sortowanie (używane z "table")',
            ],
            'limit' => [
                'type' => 'number',
                'label' => 'LIMIT',
                'default' => 100,
                'help' => 'Maksymalna liczba rekordów (używane z "table")',
            ],
            'query' => [
                'type' => 'textarea',
                'label' => 'Surowe zapytanie SQL',
                'placeholder' => 'SELECT * FROM {{%tasks}} WHERE status = \'active\'',
                'help' => 'Pełne zapytanie SQL (tylko SELECT). Używaj {{%table}} dla prefiksów.',
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'Baza Danych (wewnętrzna)';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Pobiera dane z wewnętrznej bazy danych - używaj do raportów, agregacji, analiz.';
    }
}
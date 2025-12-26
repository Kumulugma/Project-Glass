<?php

namespace app\components\parsers;

/**
 * Parser dla JSON API endpoints
 * Wyciąga konkretne wartości z JSON i ewaluuje warunki
 */
class JsonEndpointParser extends AbstractParser
{
    /**
     * @inheritdoc
     */
    public function parse($rawData)
    {
        if (!$rawData['success']) {
            throw new \Exception('Nie udało się pobrać danych: ' . ($rawData['error'] ?? 'unknown error'));
        }
        
        $jsonString = $rawData['response'] ?? '';
        $jsonData = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Błąd parsowania JSON: ' . json_last_error_msg());
        }
        
        // Wyciągnij wartości według konfiguracji
        $fieldsToExtract = $this->config['fields_to_extract'] ?? [];
        $result = [
            'raw_json' => $jsonData,
            'extracted' => [],
            'timestamp' => time(),
        ];
        
        foreach ($fieldsToExtract as $fieldPath => $fieldConfig) {
            $value = $this->extractFromJson($jsonData, $fieldPath);
            
            // Transformacja wartości jeśli podana
            if (isset($fieldConfig['transform'])) {
                $value = $this->transformValue($value, $fieldConfig['transform']);
            }
            
            $result['extracted'][$fieldPath] = $value;
        }
        
        return $result;
    }
    
    /**
     * @inheritdoc
     */
    public function evaluate($parsedData)
    {
        $notifications = [];
        $conditions = $this->config['conditions'] ?? [];
        
        foreach ($conditions as $conditionConfig) {
            if ($this->evaluateCondition($parsedData['extracted'], $conditionConfig)) {
                $notifications[] = [
                    'type' => $conditionConfig['type'] ?? 'alert',
                    'subject' => $this->renderTemplate($conditionConfig['subject'] ?? 'Alert z API', $parsedData['extracted']),
                    'message' => $this->renderTemplate($conditionConfig['message'], $parsedData['extracted']),
                    'priority' => $conditionConfig['priority'] ?? 5,
                    'data' => $parsedData,
                ];
            }
        }
        
        return $notifications;
    }
    
    /**
     * Wyciąga wartość z JSON używając dot notation
     * np. "data.user.name" -> $json['data']['user']['name']
     */
    private function extractFromJson($data, $path)
    {
        $parts = explode('.', $path);
        $value = $data;
        
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    /**
     * Transformuje wartość według typu
     */
    private function transformValue($value, $transform)
    {
        switch ($transform) {
            case 'int':
            case 'integer':
                return (int)$value;
            
            case 'float':
            case 'number':
                return (float)$value;
            
            case 'bool':
            case 'boolean':
                return (bool)$value;
            
            case 'string':
                return (string)$value;
            
            case 'lowercase':
                return strtolower((string)$value);
            
            case 'uppercase':
                return strtoupper((string)$value);
            
            case 'trim':
                return trim((string)$value);
            
            default:
                return $value;
        }
    }
    
    /**
     * Ewaluuje warunek
     * Obsługuje proste porównania: ==, !=, >, <, >=, <=, contains
     */
    private function evaluateCondition($data, $condition)
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '==';
        $compareValue = $condition['value'] ?? null;
        
        if (!$field || !isset($data[$field])) {
            return false;
        }
        
        $actualValue = $data[$field];
        
        switch ($operator) {
            case '==':
            case 'equals':
                return $actualValue == $compareValue;
            
            case '!=':
            case 'not_equals':
                return $actualValue != $compareValue;
            
            case '>':
            case 'greater':
                return $actualValue > $compareValue;
            
            case '<':
            case 'less':
                return $actualValue < $compareValue;
            
            case '>=':
            case 'greater_or_equal':
                return $actualValue >= $compareValue;
            
            case '<=':
            case 'less_or_equal':
                return $actualValue <= $compareValue;
            
            case 'contains':
                return (stripos((string)$actualValue, (string)$compareValue) !== false);
            
            case 'not_contains':
                return (stripos((string)$actualValue, (string)$compareValue) === false);
            
            case 'is_null':
                return is_null($actualValue);
            
            case 'is_not_null':
                return !is_null($actualValue);
            
            default:
                return false;
        }
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        $errors = [];
        
        if (empty($this->config['fields_to_extract'])) {
            $errors[] = 'Brak definicji pól do wyciągnięcia (fields_to_extract)';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * @inheritdoc
     */
    public static function getConfigFields()
    {
        return [
            'fields_to_extract' => [
                'type' => 'json',
                'label' => 'Pola do wyciągnięcia (JSON)',
                'placeholder' => '{"data.stats.users": {"transform": "int"}, "data.status": {}}',
                'required' => true,
                'help' => 'Format: {"ścieżka.do.pola": {"transform": "int|float|string"}}',
            ],
            'conditions' => [
                'type' => 'json',
                'label' => 'Warunki powiadomień (JSON)',
                'placeholder' => '[{"field": "data.stats.users", "operator": ">", "value": 1000, "message": "Mamy {{data.stats.users}} użytkowników!"}]',
                'help' => 'Tablica warunków do sprawdzenia',
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'JSON API Endpoint';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Pobiera dane z JSON API, wyciąga wybrane wartości i sprawdza warunki.';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDefaultFetcherClass()
    {
        return 'UrlFetcher';
    }
}

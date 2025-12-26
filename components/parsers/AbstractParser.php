<?php

namespace app\components\parsers;

use app\models\Task;
use app\models\TaskExecution;

/**
 * Abstrakcyjna klasa dla parserów
 * Parser odpowiada za:
 * 1. PRZETWORZENIE surowych danych z fetchera
 * 2. EWALUACJĘ warunków (czy wysłać powiadomienie)
 * 3. GENEROWANIE treści powiadomień
 */
abstract class AbstractParser
{
    /** @var Task */
    protected $task;
    
    /** @var array */
    protected $config;
    
    /** @var TaskExecution */
    protected $execution;
    
    /**
     * @param Task $task
     * @param TaskExecution|null $execution
     */
    public function __construct(Task $task, TaskExecution $execution = null)
    {
        $this->task = $task;
        $this->execution = $execution;
        $this->config = $task->getConfigArray();
    }
    
    /**
     * Przetwarza surowe dane z fetchera
     * 
     * @param array $rawData Dane z fetchera
     * @return array Przetworzone dane
     * @throws \Exception jeśli parsowanie się nie powiodło
     */
    abstract public function parse($rawData);
    
    /**
     * Ewaluuje warunki i decyduje czy wysłać powiadomienia
     * 
     * @param array $parsedData Przetworzone dane z parse()
     * @return array Lista powiadomień do wysłania:
     *   [
     *     [
     *       'type' => 'alert|reminder|report',
     *       'message' => 'Treść powiadomienia',
     *       'subject' => 'Temat (opcjonalnie)',
     *       'priority' => 1-10,
     *       'data' => [...] // dodatkowe dane
     *     ],
     *     ...
     *   ]
     */
    abstract public function evaluate($parsedData);
    
    /**
     * Wykrywa czy stan się zmienił (do wykrywania state changes)
     * 
     * @param array $parsedData Aktualne dane
     * @param array|null $previousState Poprzedni stan
     * @return bool True jeśli stan się zmienił
     */
    public function hasStateChanged($parsedData, $previousState)
    {
        // Domyślnie porównuje całe tablice
        return json_encode($parsedData) !== json_encode($previousState);
    }
    
    /**
     * Walidacja konfiguracji parsera
     * 
     * @return array|true Tablica błędów lub true jeśli OK
     */
    abstract public function validateConfig();
    
    /**
     * Zwraca definicję pól konfiguracyjnych dla formularza
     * 
     * @return array
     */
    public static function getConfigFields()
    {
        return [];
    }
    
    /**
     * Zwraca nazwę wyświetlaną parsera
     * 
     * @return string
     */
    public static function getDisplayName()
    {
        return 'Parser';
    }
    
    /**
     * Zwraca opis co robi parser
     * 
     * @return string
     */
    public static function getDescription()
    {
        return '';
    }
    
    /**
     * Zwraca jaki fetcher powinien być użyty z tym parserem
     * 
     * @return string Klasa fetchera
     */
    public static function getDefaultFetcherClass()
    {
        return 'EmptyFetcher';
    }
    
    /**
     * Renderuje szablon wiadomości podstawiając zmienne
     * 
     * @param string $template Szablon z {{variable}}
     * @param array $data Dane do podstawienia
     * @return string
     */
    protected function renderTemplate($template, $data)
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($data) {
            $key = $matches[1];
            return $data[$key] ?? $matches[0];
        }, $template);
    }
}

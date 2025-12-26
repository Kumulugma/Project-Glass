<?php

namespace app\components\fetchers;

use app\models\Task;

/**
 * Abstrakcyjna klasa dla fetcherów
 * Fetcher odpowiada za POBRANIE danych z zewnętrznego źródła
 */
abstract class AbstractFetcher
{
    /** @var Task */
    protected $task;
    
    /** @var array */
    protected $config;
    
    /**
     * @param Task $task
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->config = $task->getConfigArray();
    }
    
    /**
     * Pobiera dane z zewnętrznego źródła
     * 
     * @return array Surowe dane do dalszego przetworzenia
     * @throws \Exception jeśli nie udało się pobrać danych
     */
    abstract public function fetch();
    
    /**
     * Walidacja konfiguracji fetchera
     * 
     * @return array|true Tablica błędów lub true jeśli OK
     */
    abstract public function validateConfig();
    
    /**
     * Zwraca unikalny identyfikator fetchera
     * 
     * @return string
     */
    public static function getIdentifier()
    {
        return basename(str_replace('\\', '/', static::class));
    }
    
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
     * Zwraca nazwę wyświetlaną fetchera
     * 
     * @return string
     */
    public static function getDisplayName()
    {
        return 'Fetcher';
    }
    
    /**
     * Zwraca opis co robi fetcher
     * 
     * @return string
     */
    public static function getDescription()
    {
        return '';
    }
}
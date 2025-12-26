<?php

namespace app\components\fetchers;

/**
 * Pusty fetcher dla tasków które nie potrzebują pobierania danych z zewnątrz
 * Używany dla: reminderów, list zakupów, kalendarza roślin itp.
 */
class EmptyFetcher extends AbstractFetcher
{
    /**
     * @inheritdoc
     */
    public function fetch()
    {
        // Nie fetchuje nic, zwraca puste dane
        return [
            'fetched' => false,
            'timestamp' => time(),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        // Brak wymagań konfiguracyjnych
        return true;
    }
    
    /**
     * @inheritdoc
     */
    public static function getConfigFields()
    {
        return []; // Brak pól
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'Brak (reminder)';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Nie pobiera danych z zewnątrz - używany dla przypomnień i zadań wewnętrznych';
    }
}

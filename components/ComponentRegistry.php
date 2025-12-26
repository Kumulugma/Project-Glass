<?php

namespace app\components;

use Yii;

/**
 * ComponentRegistry - skanuje i zarządza dostępnymi komponentami
 * Automatycznie wykrywa parsery, fetchery i channele w odpowiednich katalogach
 */
class ComponentRegistry
{
    /**
     * Pobiera wszystkie dostępne parsery
     * 
     * @return array Lista parserów z metadanymi
     */
    public static function getAvailableParsers()
    {
        $parsers = [];
        $path = Yii::getAlias('@app/components/parsers');
        
        if (!is_dir($path)) {
            Yii::warning("Parsers directory not found: {$path}", __METHOD__);
            return [];
        }
        
        $files = glob($path . '/*.php');
        
        foreach ($files as $file) {
            $className = basename($file, '.php');
            
            // Pomiń abstrakcyjne klasy
            if ($className === 'AbstractParser') {
                continue;
            }
            
            $fullClass = "\\app\\components\\parsers\\{$className}";
            
            if (!class_exists($fullClass)) {
                continue;
            }
            
            // Sprawdź czy to rzeczywiście parser (dziedziczy po AbstractParser)
            if (!is_subclass_of($fullClass, '\\app\\components\\parsers\\AbstractParser')) {
                continue;
            }
            
            try {
                $parsers[] = [
                    'class' => $className,
                    'identifier' => self::getIdentifier($fullClass),
                    'name' => $fullClass::getDisplayName(),
                    'description' => $fullClass::getDescription(),
                    'required_fetcher' => $fullClass::getDefaultFetcherClass(),
                ];
            } catch (\Exception $e) {
                Yii::warning("Failed to load parser {$className}: " . $e->getMessage(), __METHOD__);
            }
        }
        
        // Sortuj alfabetycznie po nazwie
        usort($parsers, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $parsers;
    }
    
    /**
     * Pobiera wszystkie dostępne fetchery
     * 
     * @return array Lista fetcherów z metadanymi
     */
    public static function getAvailableFetchers()
    {
        $fetchers = [];
        $path = Yii::getAlias('@app/components/fetchers');
        
        if (!is_dir($path)) {
            Yii::warning("Fetchers directory not found: {$path}", __METHOD__);
            return [];
        }
        
        $files = glob($path . '/*.php');
        
        foreach ($files as $file) {
            $className = basename($file, '.php');
            
            // Pomiń abstrakcyjne klasy
            if ($className === 'AbstractFetcher') {
                continue;
            }
            
            $fullClass = "\\app\\components\\fetchers\\{$className}";
            
            if (!class_exists($fullClass)) {
                continue;
            }
            
            // Sprawdź czy to rzeczywiście fetcher
            if (!is_subclass_of($fullClass, '\\app\\components\\fetchers\\AbstractFetcher')) {
                continue;
            }
            
            try {
                $fetchers[] = [
                    'class' => $className,
                    'identifier' => self::getIdentifier($fullClass),
                    'name' => $fullClass::getDisplayName(),
                    'description' => $fullClass::getDescription(),
                ];
            } catch (\Exception $e) {
                Yii::warning("Failed to load fetcher {$className}: " . $e->getMessage(), __METHOD__);
            }
        }
        
        // Sortuj alfabetycznie po nazwie
        usort($fetchers, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $fetchers;
    }
    
    /**
     * Pobiera wszystkie dostępne channele
     * 
     * @return array Lista channeli z metadanymi
     */
    public static function getAvailableChannels()
    {
        $channels = [];
        $path = Yii::getAlias('@app/components/channels');
        
        if (!is_dir($path)) {
            Yii::warning("Channels directory not found: {$path}", __METHOD__);
            return [];
        }
        
        $files = glob($path . '/*.php');
        
        foreach ($files as $file) {
            $className = basename($file, '.php');
            
            // Pomiń abstrakcyjne klasy i interfejsy
            if (in_array($className, ['AbstractChannel', 'NotificationChannel'])) {
                continue;
            }
            
            $fullClass = "\\app\\components\\channels\\{$className}";
            
            if (!class_exists($fullClass)) {
                continue;
            }
            
            // Sprawdź czy implementuje NotificationChannel
            if (!in_array('app\\components\\channels\\NotificationChannel', class_implements($fullClass))) {
                continue;
            }
            
            try {
                $channels[] = [
                    'class' => $className,
                    'identifier' => self::getIdentifier($fullClass),
                    'name' => $fullClass::getDisplayName(),
                    'description' => $fullClass::getDescription(),
                ];
            } catch (\Exception $e) {
                Yii::warning("Failed to load channel {$className}: " . $e->getMessage(), __METHOD__);
            }
        }
        
        // Sortuj alfabetycznie po nazwie
        usort($channels, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $channels;
    }
    
    /**
     * Generuje unikalny identyfikator z pełnej nazwy klasy
     * 
     * @param string $fullClass Pełna nazwa klasy
     * @return string Identyfikator (nazwa klasy bez namespace)
     */
    private static function getIdentifier($fullClass)
    {
        return basename(str_replace('\\', '/', $fullClass));
    }
    
    /**
     * Pobiera instancję parsera
     * 
     * @param string $className Nazwa klasy parsera
     * @param \app\models\Task $task
     * @param \app\models\TaskExecution|null $execution
     * @return \app\components\parsers\AbstractParser|null
     */
    public static function getParser($className, $task, $execution = null)
    {
        $fullClass = "\\app\\components\\parsers\\{$className}";
        
        if (!class_exists($fullClass)) {
            Yii::error("Parser class not found: {$fullClass}", __METHOD__);
            return null;
        }
        
        try {
            return new $fullClass($task, $execution);
        } catch (\Exception $e) {
            Yii::error("Failed to instantiate parser {$className}: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }
    
    /**
     * Pobiera instancję fetchera
     * 
     * @param string $className Nazwa klasy fetchera
     * @param \app\models\Task $task
     * @return \app\components\fetchers\AbstractFetcher|null
     */
    public static function getFetcher($className, $task)
    {
        $fullClass = "\\app\\components\\fetchers\\{$className}";
        
        if (!class_exists($fullClass)) {
            Yii::error("Fetcher class not found: {$fullClass}", __METHOD__);
            return null;
        }
        
        try {
            return new $fullClass($task);
        } catch (\Exception $e) {
            Yii::error("Failed to instantiate fetcher {$className}: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }
    
    /**
     * Pobiera instancję channela
     * 
     * @param string $identifier Identyfikator channela (np. 'EmailChannel', 'email')
     * @return \app\components\channels\NotificationChannel|null
     */
    public static function getChannel($identifier)
    {
        // Normalizuj identyfikator
        $className = ucfirst(strtolower($identifier));
        if (!str_ends_with($className, 'Channel')) {
            $className .= 'Channel';
        }
        
        $fullClass = "\\app\\components\\channels\\{$className}";
        
        if (!class_exists($fullClass)) {
            Yii::error("Channel class not found: {$fullClass}", __METHOD__);
            return null;
        }
        
        try {
            return new $fullClass();
        } catch (\Exception $e) {
            Yii::error("Failed to instantiate channel {$className}: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }
    
    /**
     * Sprawdza czy parser istnieje
     * 
     * @param string $className
     * @return bool
     */
    public static function parserExists($className)
    {
        $fullClass = "\\app\\components\\parsers\\{$className}";
        return class_exists($fullClass);
    }
    
    /**
     * Sprawdza czy fetcher istnieje
     * 
     * @param string $className
     * @return bool
     */
    public static function fetcherExists($className)
    {
        $fullClass = "\\app\\components\\fetchers\\{$className}";
        return class_exists($fullClass);
    }
    
    /**
     * Sprawdza czy channel istnieje
     * 
     * @param string $identifier
     * @return bool
     */
    public static function channelExists($identifier)
    {
        $className = ucfirst(strtolower($identifier));
        if (!str_ends_with($className, 'Channel')) {
            $className .= 'Channel';
        }
        
        $fullClass = "\\app\\components\\channels\\{$className}";
        return class_exists($fullClass);
    }
}

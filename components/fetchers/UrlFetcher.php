<?php

namespace app\components\fetchers;

/**
 * Fetcher pobierający dane z URL przez HTTP/HTTPS
 */
class UrlFetcher extends AbstractFetcher
{
    /**
     * @inheritdoc
     */
    public function fetch()
    {
        $url = $this->config['url'] ?? null;
        if (!$url) {
            throw new \Exception('URL nie został podany w konfiguracji');
        }
        
        $timeout = $this->config['timeout'] ?? 30;
        $method = $this->config['method'] ?? 'GET';
        $followRedirects = $this->config['follow_redirects'] ?? true;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        // User Agent
        curl_setopt($ch, CURLOPT_USERAGENT, 'TaskReminderBot/1.0');
        
        // Headers
        if (!empty($this->config['headers'])) {
            $headers = [];
            foreach ($this->config['headers'] as $key => $value) {
                $headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // Method
        if ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        } elseif ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($this->config['post_data'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->config['post_data']);
            }
        }
        
        // Wykonaj request
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $duration = round((microtime(true) - $startTime) * 1000); // ms
        
        // Pobierz info
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);
        $errorNo = curl_errno($ch);
        
        curl_close($ch);
        
        return [
            'url' => $url,
            'http_code' => $httpCode,
            'content_type' => $contentType,
            'response' => $response,
            'response_size' => strlen($response),
            'duration_ms' => $duration,
            'total_time' => $totalTime,
            'error' => $error,
            'error_no' => $errorNo,
            'timestamp' => time(),
            'success' => ($httpCode >= 200 && $httpCode < 300 && !$errorNo),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        $errors = [];
        
        if (empty($this->config['url'])) {
            $errors[] = 'URL jest wymagany';
        } elseif (!filter_var($this->config['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Nieprawidłowy format URL';
        }
        
        if (isset($this->config['timeout']) && (!is_numeric($this->config['timeout']) || $this->config['timeout'] <= 0)) {
            $errors[] = 'Timeout musi być liczbą dodatnią';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * @inheritdoc
     */
    public static function getConfigFields()
    {
        return [
            'url' => [
                'type' => 'text',
                'label' => 'URL',
                'required' => true,
                'placeholder' => 'https://example.com',
            ],
            'method' => [
                'type' => 'dropdown',
                'label' => 'Metoda HTTP',
                'options' => ['GET' => 'GET', 'POST' => 'POST', 'HEAD' => 'HEAD'],
                'default' => 'GET',
            ],
            'timeout' => [
                'type' => 'number',
                'label' => 'Timeout (sekundy)',
                'default' => 30,
            ],
            'follow_redirects' => [
                'type' => 'checkbox',
                'label' => 'Podążaj za przekierowaniami',
                'default' => true,
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function getDisplayName()
    {
        return 'HTTP/HTTPS Request';
    }
    
    /**
     * @inheritdoc
     */
    public static function getDescription()
    {
        return 'Pobiera zawartość strony WWW lub API przez HTTP/HTTPS';
    }
}

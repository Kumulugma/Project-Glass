<?php
// generate-vapid-keys.php

require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

try {
    $keys = VAPID::createVapidKeys();
    
    echo "=================================================\n";
    echo "       VAPID Keys Successfully Generated!       \n";
    echo "=================================================\n\n";
    
    echo "Public Key:\n";
    echo $keys['publicKey'] . "\n\n";
    
    echo "Private Key:\n";
    echo $keys['privateKey'] . "\n\n";
    
    echo "=================================================\n";
    echo "IMPORTANT: Save these keys in a secure place!\n";
    echo "Add them to Settings -> Push Channel\n";
    echo "=================================================\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nMake sure you installed the library:\n";
    echo "composer require minishlink/web-push\n";
}
<?php

return [
    'adminEmail' => 'admin@example.com',
    
    // SMS API (SMSAPI.pl)
    'smsapi' => [
        'token' => 'your-smsapi-token',
        'sender' => 'TaskReminder',
    ],
    
    // Telegram Bot
    'telegram' => [
        'token' => 'your-telegram-bot-token',
        'chat_id' => 'your-chat-id',
    ],
    
    // Web Push (VAPID keys)
    'webpush' => [
        'subject' => 'mailto:admin@example.com',
        'publicKey' => 'your-vapid-public-key',
        'privateKey' => 'your-vapid-private-key',
    ],
];


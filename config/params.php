<?php

/**
 * Application parameters
 * Używa zmiennych środowiskowych z pliku .env
 */

return [
    'adminEmail' => getenv('ADMIN_EMAIL') ?: 'admin@example.com',
    'supportEmail' => getenv('SUPPORT_EMAIL') ?: 'support@example.com',
    'senderEmail' => getenv('SENDER_EMAIL') ?: 'noreply@example.com',
    'senderName' => getenv('SENDER_NAME') ?: 'Task Reminder',
    
    // SMS API (SMSAPI.pl)
    'smsapi' => [
        'token' => getenv('SMSAPI_TOKEN') ?: '',
        'sender' => getenv('SMSAPI_SENDER') ?: 'TaskReminder',
    ],
    
    // Telegram Bot
    'telegram' => [
        'token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
        'chat_id' => getenv('TELEGRAM_CHAT_ID') ?: '',
    ],
    
    // Web Push (VAPID keys)
    'webpush' => [
        'subject' => getenv('WEBPUSH_SUBJECT') ?: 'mailto:admin@example.com',
        'publicKey' => getenv('WEBPUSH_PUBLIC_KEY') ?: '',
        'privateKey' => getenv('WEBPUSH_PRIVATE_KEY') ?: '',
    ],
];
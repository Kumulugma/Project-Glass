<?php

namespace app\components\notifications;

use Yii;
use app\models\NotificationQueue;

/**
 * Kanał powiadomień przez SMS (SMSAPI.pl)
 */
class SmsChannel implements NotificationChannel
{
    /**
     * @inheritdoc
     */
    public function send(NotificationQueue $notification)
    {
        try {
            $token = Yii::$app->params['smsapi']['token'] ?? null;
            $sender = Yii::$app->params['smsapi']['sender'] ?? 'Info';
            
            if (!$token) {
                throw new \Exception('Brak tokena SMSAPI w konfiguracji');
            }
            
            // Konfiguracja SMSAPI client
            $service = new \Smsapi\Client\Service\SmsapiComService($token);
            $smsapiClient = new \Smsapi\Client\SmsapiClient($service);
            
            // Normalizuj numer telefonu (usuń spacje, myślniki)
            $to = preg_replace('/[^0-9+]/', '', $notification->recipient);
            
            // Wyślij SMS
            $sms = $smsapiClient->smsFactory()->actionSend()
                ->setText($notification->message)
                ->setTo($to)
                ->setFrom($sender)
                ->execute();
            
            return [
                'success' => true,
                'response' => 'SMS wysłany, ID: ' . $sms->id,
                'error' => null,
            ];
            
        } catch (\Smsapi\Client\Exception\SmsapiException $e) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'SMSAPI Error: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * @inheritdoc
     */
    public function validateConfig()
    {
        $errors = [];
        
        if (!isset(Yii::$app->params['smsapi']['token'])) {
            $errors[] = 'Brak smsapi.token w params';
        }
        
        if (!class_exists('\Smsapi\Client\SmsapiClient')) {
            $errors[] = 'Brak biblioteki smsapi/php-client (composer require smsapi/php-client)';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * @inheritdoc
     */
    public function isAvailable()
    {
        return isset(Yii::$app->params['smsapi']['token']) 
            && class_exists('\Smsapi\Client\SmsapiClient');
    }
}

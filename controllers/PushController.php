<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\PushSubscription;

/**
 * PushController - obsługa Web Push subscriptions
 */
class PushController extends Controller
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false; // Dla API endpoints
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'subscribe' => ['POST'],
                    'unsubscribe' => ['POST'],
                ],
            ],
        ];
    }
    
    /**
     * Zwraca VAPID public key
     * 
     * GET /push/public-key
     */
    public function actionPublicKey()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $publicKey = Yii::$app->params['webpush']['publicKey'] ?? null;
        
        if (!$publicKey) {
            Yii::$app->response->statusCode = 500;
            return [
                'error' => 'Web Push not configured',
            ];
        }
        
        return [
            'publicKey' => $publicKey,
        ];
    }
    
    /**
     * Rejestruje nową subskrypcję
     * 
     * POST /push/subscribe
     * Body: {endpoint, keys: {p256dh, auth}}
     */
    public function actionSubscribe()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $data = Yii::$app->request->post();
        
        if (empty($data['endpoint'])) {
            Yii::$app->response->statusCode = 400;
            return [
                'error' => 'Missing endpoint',
            ];
        }
        
        try {
            $subscription = PushSubscription::createOrUpdate($data);
            
            return [
                'success' => true,
                'message' => 'Subscribed successfully',
                'subscription_id' => $subscription->id,
            ];
            
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Usuwa subskrypcję
     * 
     * POST /push/unsubscribe
     * Body: {endpoint}
     */
    public function actionUnsubscribe()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $data = Yii::$app->request->post();
        $endpoint = $data['endpoint'] ?? null;
        
        if (!$endpoint) {
            Yii::$app->response->statusCode = 400;
            return [
                'error' => 'Missing endpoint',
            ];
        }
        
        $subscription = PushSubscription::findOne(['endpoint' => $endpoint]);
        
        if ($subscription) {
            $subscription->markAsInactive('User unsubscribed');
            
            return [
                'success' => true,
                'message' => 'Unsubscribed successfully',
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Subscription not found (already unsubscribed)',
        ];
    }
    
    /**
     * Test endpoint - wysyła testowe powiadomienie
     * 
     * POST /push/test
     */
    public function actionTest()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $subscriptions = PushSubscription::findActive();
        
        if (empty($subscriptions)) {
            return [
                'success' => false,
                'error' => 'No active subscriptions',
            ];
        }
        
        // Użyj PushChannel do wysłania testowego powiadomienia
        $channel = new \app\components\notifications\PushChannel();
        
        // Stwórz dummy notification
        $notification = new \app\models\NotificationQueue();
        $notification->subject = 'Test Web Push';
        $notification->message = 'To jest testowe powiadomienie z Task Reminder App!';
        $notification->task_id = 0;
        $notification->recipient = 'test';
        
        $result = $channel->send($notification);
        
        return $result;
    }
}

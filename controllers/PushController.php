<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
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
            'access' => [
                'class' => AccessControl::class,
                'except' => ['public-key', 'subscribe', 'unsubscribe', 'subscribe-page', 'test'], // Publiczne endpointy
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Zalogowani użytkownicy
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'subscribe' => ['POST'],
                    'unsubscribe' => ['POST'],
                    'test' => ['POST'],
                ],
            ],
        ];
    }
    
    /**
     * Publiczny widok do zarządzania subskrypcjami
     * Dostępny bez logowania
     * 
     * GET /push/subscribe-page
     */
    public function actionSubscribePage()
    {
        $this->layout = 'main'; // Użyj głównego layoutu
        
        return $this->render('subscribe');
    }
    
    /**
     * Zwraca VAPID public key
     * 
     * GET /push/public-key
     */
    public function actionPublicKey()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        // Czytaj z Setting (tabela settings)
        $publicKey = \app\models\Setting::get('channel_push_vapid_public_key');
        
        // Fallback na params.php (jeśli ktoś używa .env)
        if (!$publicKey) {
            $publicKey = Yii::$app->params['webpush']['publicKey'] ?? null;
        }
        
        if (!$publicKey) {
            Yii::$app->response->statusCode = 500;
            return [
                'error' => 'Web Push not configured. Please add VAPID keys in Settings -> Channels -> Push',
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
        
        // Yii2 nie parsuje JSON automatycznie, musimy to zrobić ręcznie
        $rawBody = Yii::$app->request->getRawBody();
        $data = json_decode($rawBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
            ];
        }
        
        if (empty($data['endpoint'])) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => 'Missing endpoint',
            ];
        }
        
        try {
            $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
            
            // Dla niezalogowanych: zapisz email w device_name (jeśli podany)
            $deviceName = null;
            if (isset($data['user_email']) && filter_var($data['user_email'], FILTER_VALIDATE_EMAIL)) {
                $deviceName = $data['user_email'];
            }
            
            $subscription = PushSubscription::createOrUpdate($data, $userId, $deviceName);
            
            if ($subscription) {
                return [
                    'success' => true,
                    'message' => 'Subscribed successfully',
                    'subscription_id' => $subscription->id,
                ];
            } else {
                throw new \Exception('Failed to save subscription');
            }
            
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            return [
                'success' => false,
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
        
        // Parsuj JSON
        $rawBody = Yii::$app->request->getRawBody();
        $data = json_decode($rawBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
            ];
        }
        
        $endpoint = $data['endpoint'] ?? null;
        
        if (!$endpoint) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
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
     * Test endpoint - wysyła do konkretnego urządzenia lub wszystkich
     */
    public function actionTest()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        // Parsuj JSON z body
        $rawBody = Yii::$app->request->getRawBody();
        $data = json_decode($rawBody, true);
        
        $targetEndpoint = $data['endpoint'] ?? null;
        
        // Jeśli podano endpoint - wyślij tylko do tego urządzenia
        if ($targetEndpoint) {
            $subscriptions = PushSubscription::find()
                ->where(['endpoint' => $targetEndpoint, 'is_active' => true])
                ->all();
            
            if (empty($subscriptions)) {
                return [
                    'success' => false,
                    'error' => 'Nie znaleziono aktywnej subskrypcji dla tego urządzenia',
                ];
            }
        } else {
            // Wyślij do wszystkich
            $subscriptions = PushSubscription::findActive();
            
            if (empty($subscriptions)) {
                return [
                    'success' => false,
                    'error' => 'No active subscriptions',
                ];
            }
        }
        
        // Wyślij bezpośrednio do subskrypcji
        $publicKey = \app\models\Setting::get('channel_push_vapid_public_key');
        $privateKey = \app\models\Setting::get('channel_push_vapid_private_key');
        $subject = \app\models\Setting::get('channel_push_vapid_subject', 'mailto:admin@example.com');
        
        if (!$publicKey || !$privateKey) {
            return [
                'success' => false,
                'error' => 'Brak kluczy VAPID',
            ];
        }
        
        $auth = [
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];
        
        $webPush = new \Minishlink\WebPush\WebPush($auth);
        
        $payload = json_encode([
            'title' => 'Test Web Push 🔔',
            'body' => 'To jest testowe powiadomienie z GlassSystem! Jeśli widzisz tę wiadomość, wszystko działa poprawnie. 🎉',
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'test-notification',
            'data' => [
                'url' => '/',
                'timestamp' => time(),
            ],
        ]);
        
        $successCount = 0;
        $failedCount = 0;
        
        foreach ($subscriptions as $subscription) {
            $pushSubscription = \Minishlink\WebPush\Subscription::create($subscription->toWebPushFormat());
            
            try {
                $report = $webPush->sendOneNotification($pushSubscription, $payload);
                
                if ($report->isSuccess()) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }
        
        return [
            'success' => $successCount > 0,
            'message' => $targetEndpoint 
                ? "Wysłano testowe powiadomienie do tego urządzenia" 
                : "Wysłano do {$successCount} urządzeń" . ($failedCount > 0 ? ", {$failedCount} błędów" : ''),
            'total_subscriptions' => count($subscriptions),
            'successful' => $successCount,
            'failed' => $failedCount,
        ];
    }
    
    /**
     * Lista subskrypcji (tylko dla zalogowanych)
     * 
     * GET /push/index
     */
    public function actionIndex()
    {
        $subscriptions = PushSubscription::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
        
        return $this->render('index', [
            'subscriptions' => $subscriptions,
        ]);
    }
    
    /**
     * Usuwa subskrypcję (admin)
     * 
     * POST /push/delete-subscription
     */
    public function actionDeleteSubscription($id)
    {
        $subscription = PushSubscription::findOne($id);
        
        if (!$subscription) {
            throw new \yii\web\NotFoundHttpException('Subskrypcja nie została znaleziona.');
        }
        
        $subscription->delete();
        
        Yii::$app->session->setFlash('success', 'Subskrypcja została usunięta.');
        
        return $this->redirect(['index']);
    }
}
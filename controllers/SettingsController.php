<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\Setting;
use app\components\ComponentRegistry;

/**
 * SettingsController - zarządzanie ustawieniami aplikacji i channeli
 */
class SettingsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Główna strona ustawień
     */
    public function actionIndex()
    {
        // Pobierz listę dostępnych channeli
        $channels = ComponentRegistry::getAvailableChannels();
        
        // Pobierz status każdego channela
        $channelStatuses = [];
        foreach ($channels as $channel) {
            $enabled = Setting::get("channel_{$channel['identifier']}_enabled", false);
            $cooldown = Setting::get("channel_{$channel['identifier']}_cooldown", 60);
            
            $channelStatuses[$channel['identifier']] = [
                'enabled' => $enabled,
                'cooldown' => $cooldown,
            ];
        }
        
        return $this->render('index', [
            'channels' => $channels,
            'channelStatuses' => $channelStatuses,
        ]);
    }
    
    /**
     * Edycja ustawień konkretnego channela
     */
    public function actionChannel($id)
    {
        // Sprawdź czy channel istnieje
        if (!ComponentRegistry::channelExists($id)) {
            throw new \yii\web\NotFoundHttpException("Channel nie został znaleziony: {$id}");
        }
        
        // Pobierz channel
        $channel = ComponentRegistry::getChannel($id);
        if (!$channel) {
            throw new \yii\web\NotFoundHttpException("Nie można załadować channela: {$id}");
        }
        
        // Pobierz aktualne ustawienia
        $settings = Setting::getChannelSettings($id);
        
        // Pobierz definicję pól z channela
        $channelClass = get_class($channel);
        $configFields = $channelClass::getConfigFields();
        
        // Obsługa POST
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post('Settings', []);
            
            // Walidacja i zapis
            if (Setting::setChannelSettings($id, $post)) {
                Yii::$app->session->setFlash('success', 'Ustawienia channela zapisane pomyślnie.');
                return $this->redirect(['channel', 'id' => $id]);
            } else {
                Yii::$app->session->setFlash('error', 'Nie udało się zapisać ustawień.');
            }
        }
        
        return $this->render('channel', [
            'channel' => $channel,
            'channelId' => $id,
            'channelName' => $channelClass::getDisplayName(),
            'channelDescription' => $channelClass::getDescription(),
            'settings' => $settings,
            'configFields' => $configFields,
        ]);
    }
    
    /**
     * Test wysyłki dla channela
     */
    public function actionTest($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $channel = ComponentRegistry::getChannel($id);
        if (!$channel) {
            return [
                'success' => false,
                'error' => "Channel nie został znaleziony: {$id}",
            ];
        }
        
        if (!$channel->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Channel nie jest skonfigurowany lub wyłączony',
            ];
        }
        
        // Pobierz odbiorcę z POST lub użyj domyślnego
        $recipient = Yii::$app->request->post('recipient', Yii::$app->params['adminEmail'] ?? 'test@example.com');
        
        // Utwórz testowe powiadomienie z przykładowymi danymi
        $notification = new \app\models\NotificationQueue();
        $notification->task_id = null; // Brak powiązanego taska dla testu
        $notification->channel = $id;
        $notification->recipient = $recipient;
        $notification->subject = 'Test powiadomienia - GlassSystem';
        $notification->message = "To jest testowe powiadomienie z systemu GlassSystem.\n\n"
            . "Jeśli widzisz tę wiadomość, oznacza to że kanał {$id} działa poprawnie!\n\n"
            . "Informacje testowe:\n"
            . "- Data wysłania: " . date('Y-m-d H:i:s') . "\n"
            . "- Kanał: {$id}\n"
            . "- Odbiorca: {$recipient}\n\n"
            . "Pozdrawiamy,\nZespół GlassSystem";
        $notification->type = 'alert';
        
        // Wyślij
        try {
            $result = $channel->send($notification);
            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Włącz/wyłącz channel
     */
    public function actionToggle($id)
    {
        if (!ComponentRegistry::channelExists($id)) {
            throw new \yii\web\NotFoundHttpException("Channel nie został znaleziony: {$id}");
        }
        
        $currentStatus = Setting::get("channel_{$id}_enabled", false);
        $newStatus = !$currentStatus;
        
        Setting::set("channel_{$id}_enabled", $newStatus, 'boolean');
        
        $message = $newStatus ? 'Channel został włączony.' : 'Channel został wyłączony.';
        Yii::$app->session->setFlash('success', $message);
        
        return $this->redirect(['index']);
    }
}
<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\Setting;
use app\components\StatsReporter;

/**
 * MonitoringController - zarządzanie konfiguracją monitoringu
 * 
 * Dostęp: System -> Konfiguracja monitoringu
 */
class MonitoringController extends Controller
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
     * Główna strona konfiguracji monitoringu
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        
        if ($request->isPost) {
            // Zapisz ustawienia
            $this->saveSettings($request->post());
            Yii::$app->session->setFlash('success', 'Ustawienia monitoringu zostały zapisane.');
            return $this->redirect(['index']);
        }
        
        // Pobierz aktualne ustawienia
        $settings = $this->getCurrentSettings();
        $stats = StatsReporter::getDetailedStats();
        
        return $this->render('index', [
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Test połączenia z API
     */
    public function actionTestConnection()
    {
        $result = StatsReporter::sendStats();
        
        if ($result['success']) {
            Yii::$app->session->setFlash('success', 'Test zakończony sukcesem! Odpowiedź API: ' . $result['response']);
        } else {
            Yii::$app->session->setFlash('error', 'Test nie powiódł się: ' . $result['error']);
        }
        
        return $this->redirect(['index']);
    }
    
    /**
     * Reset liczników (tylko dla adminów)
     */
    public function actionResetCounters()
    {
        if (!Yii::$app->user->identity->isAdmin) {
            Yii::$app->session->setFlash('error', 'Brak uprawnień.');
            return $this->redirect(['index']);
        }
        
        Setting::set('stats_total_executions', 0, 'number');
        Setting::set('stats_total_notifications', 0, 'number');
        Setting::set('stats_last_execution_date', null, 'string');
        
        Yii::$app->session->setFlash('success', 'Liczniki zostały zresetowane.');
        return $this->redirect(['index']);
    }
    
    /**
     * Pobiera aktualne ustawienia
     */
    private function getCurrentSettings()
    {
        return [
            'monitoring_enabled' => Setting::get('monitoring_enabled', true),
            'monitoring_api_url' => Setting::get('monitoring_api_url', ''),
            'monitoring_api_token' => Setting::get('monitoring_api_token', ''),
            'monitoring_interval' => Setting::get('monitoring_interval', 10),
        ];
    }
    
    /**
     * Zapisuje ustawienia
     */
    private function saveSettings($post)
    {
        if (isset($post['monitoring_enabled'])) {
            Setting::set('monitoring_enabled', $post['monitoring_enabled'] ? 1 : 0, 'boolean');
        }
        
        if (isset($post['monitoring_api_url'])) {
            Setting::set('monitoring_api_url', $post['monitoring_api_url'], 'string');
        }
        
        if (isset($post['monitoring_api_token'])) {
            Setting::set('monitoring_api_token', $post['monitoring_api_token'], 'password');
        }
        
        if (isset($post['monitoring_interval'])) {
            $interval = (int)$post['monitoring_interval'];
            if ($interval >= 1 && $interval <= 60) {
                Setting::set('monitoring_interval', $interval, 'number');
            }
        }
    }
}
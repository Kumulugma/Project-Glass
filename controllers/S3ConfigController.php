<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use app\models\Setting;

/**
 * S3ConfigController - konfiguracja połączenia z AWS S3
 */
class S3ConfigController extends Controller
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
     * Strona konfiguracji S3
     */
    public function actionIndex()
    {
        $settings = [
            's3_enabled' => Setting::get('s3_enabled', false),
            's3_access_key' => Setting::get('s3_access_key', ''),
            's3_secret_key' => Setting::get('s3_secret_key', ''),
            's3_region' => Setting::get('s3_region', 'eu-central-1'),
            's3_bucket' => Setting::get('s3_bucket', ''),
            's3_prefix' => Setting::get('s3_prefix', 'archives'),
        ];
        
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post('S3Config', []);
            
            // Zapisz ustawienia
            foreach ($post as $key => $value) {
                if ($key === 's3_enabled') {
                    Setting::set($key, (bool)$value);
                } else {
                    Setting::set($key, $value);
                }
            }
            
            Yii::$app->session->setFlash('success', 'Konfiguracja S3 została zapisana.');
            return $this->refresh();
        }
        
        return $this->render('index', [
            'settings' => $settings,
        ]);
    }
    
    /**
     * Test połączenia z S3 (AJAX)
     */
    public function actionTestConnection()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $uploader = Yii::$app->s3Uploader;
            $result = $uploader->testConnection();
            
            return [
                'success' => $result['success'],
                'message' => $result['message'],
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Lista bucketów (AJAX) - do wyboru bucketu
     */
    public function actionListBuckets()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $uploader = Yii::$app->s3Uploader;
            $uploader->connect();
            
            $s3Client = $uploader->s3Client;
            $result = $s3Client->listBuckets();
            
            $buckets = [];
            foreach ($result['Buckets'] as $bucket) {
                $buckets[] = [
                    'name' => $bucket['Name'],
                    'created' => $bucket['CreationDate']->format('Y-m-d H:i:s'),
                ];
            }
            
            return [
                'success' => true,
                'buckets' => $buckets,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
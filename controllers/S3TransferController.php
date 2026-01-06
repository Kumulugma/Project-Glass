<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use app\models\S3Transfer;

/**
 * S3TransferController - kontrola transferów na S3
 */
class S3TransferController extends Controller
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
     * Lista transferów
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => S3Transfer::find()->orderBy(['started_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 30],
        ]);
        
        // Statystyki
        $stats = [
            'total' => S3Transfer::find()->count(),
            'completed' => S3Transfer::find()->where(['status' => 'completed'])->count(),
            'failed' => S3Transfer::find()->where(['status' => 'failed'])->count(),
            'pending' => S3Transfer::find()->where(['status' => 'pending'])->count(),
        ];
        
        // Oblicz całkowity rozmiar przesłanych plików
        $totalSize = S3Transfer::find()
            ->where(['status' => 'completed'])
            ->sum('file_size');
        
        $stats['total_size_mb'] = round($totalSize / 1024 / 1024, 2);
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Manualne uruchomienie uploadu na S3 (AJAX)
     */
    public function actionRunUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $uploader = Yii::$app->s3Uploader;
            $stats = $uploader->uploadAllArchives();
            
            return [
                'success' => true,
                'stats' => $stats,
                'message' => 'Upload na S3 zakończony pomyślnie',
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Upload pojedynczego archiwum (AJAX)
     */
    public function actionUploadSingle()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $type = Yii::$app->request->post('type');
        $date = Yii::$app->request->post('date');
        
        try {
            $uploader = Yii::$app->s3Uploader;
            $result = $uploader->uploadArchive($type, $date);
            
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
     * Ponów nieudany transfer (AJAX)
     */
    public function actionRetry($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $transfer = S3Transfer::findOne($id);
        if (!$transfer) {
            return [
                'success' => false,
                'message' => 'Transfer nie został znaleziony',
            ];
        }
        
        try {
            $uploader = Yii::$app->s3Uploader;
            $result = $uploader->uploadArchive($transfer->archive_type, $transfer->archive_date);
            
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
     * Usuń transfer z historii (AJAX)
     */
    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $transfer = S3Transfer::findOne($id);
        if (!$transfer) {
            return [
                'success' => false,
                'message' => 'Transfer nie został znaleziony',
            ];
        }
        
        if ($transfer->delete()) {
            return [
                'success' => true,
                'message' => 'Transfer został usunięty z historii',
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Nie udało się usunąć transferu',
        ];
    }
    
    /**
     * Szczegóły transferu
     */
    public function actionView($id)
    {
        $transfer = S3Transfer::findOne($id);
        if (!$transfer) {
            throw new \yii\web\NotFoundHttpException('Transfer nie został znaleziony');
        }
        
        return $this->render('view', [
            'transfer' => $transfer,
        ]);
    }
}
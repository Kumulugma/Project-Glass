<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\data\ArrayDataProvider;
use app\models\ArchiveLog;

/**
 * ArchiveController - zarządzanie zarchiwizowanymi danymi
 */
class ArchiveController extends Controller
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
     * Strona główna zarządzania archiwami
     */
    public function actionIndex()
    {
        $archiver = Yii::$app->archiver;
        
        // Statystyki
        $stats = $archiver->getArchiveStats();
        
        // Listy archiwów
        $executionArchives = $archiver->listExecutionArchives();
        $fetchResultArchives = $archiver->listFetchResultArchives();
        
        // S3 archiwa (jeśli włączone)
        $s3Archives = [];
        $s3Stats = ['total' => 0, 'size_mb' => 0];
        
        $s3Enabled = \app\models\Setting::get('s3_enabled', false);
        if ($s3Enabled) {
            try {
                $uploader = Yii::$app->s3Uploader;
                $uploader->connect();
                $s3Archives = $uploader->listS3Archives();
                
                $totalSize = 0;
                foreach ($s3Archives as $archive) {
                    $totalSize += $archive['size'];
                }
                
                $s3Stats = [
                    'total' => count($s3Archives),
                    'size_mb' => round($totalSize / 1024 / 1024, 2),
                ];
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('warning', 'Nie udało się połączyć z S3: ' . $e->getMessage());
            }
        }
        
        return $this->render('index', [
            'stats' => $stats,
            'executionArchives' => $executionArchives,
            'fetchResultArchives' => $fetchResultArchives,
            's3Archives' => $s3Archives,
            's3Stats' => $s3Stats,
            's3Enabled' => $s3Enabled,
        ]);
    }
    
    /**
     * Podgląd archiwum z danego dnia
     */
    public function actionView($type, $date)
    {
        $archiver = Yii::$app->archiver;
        
        // Walidacja typu
        if (!in_array($type, ['task_executions', 'fetch_results'])) {
            throw new \yii\web\BadRequestHttpException('Invalid archive type');
        }
        
        // Walidacja daty
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \yii\web\BadRequestHttpException('Invalid date format');
        }
        
        // Wczytaj dane z archiwum
        $data = null;
        $source = 'local';
        
        if ($type === 'task_executions') {
            $data = $archiver->loadExecutionsFromArchive($date);
        } else {
            $data = $archiver->loadFetchResultsFromArchive($date);
        }
        
        // Jeśli nie ma lokalnie, spróbuj S3
        if ($data === null) {
            $s3Enabled = \app\models\Setting::get('s3_enabled', false);
            if ($s3Enabled) {
                try {
                    $uploader = Yii::$app->s3Uploader;
                    $result = $uploader->downloadArchive($type, $date);
                    
                    if ($result['success']) {
                        $data = $result['data'];
                        $source = 's3';
                    }
                } catch (\Exception $e) {
                    Yii::$app->session->setFlash('error', 'Nie udało się pobrać z S3: ' . $e->getMessage());
                }
            }
        }
        
        if ($data === null) {
            throw new \yii\web\NotFoundHttpException('Archive not found');
        }
        
        // Paginacja
        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'attributes' => ['id', 'task_id', 'status'],
            ],
        ]);
        
        return $this->render('view', [
            'type' => $type,
            'date' => $date,
            'source' => $source,
            'dataProvider' => $dataProvider,
            'totalRecords' => count($data),
        ]);
    }
    
    /**
     * Usuń lokalne archiwum (AJAX)
     */
    public function actionDeleteLocal()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $type = Yii::$app->request->post('type');
        $date = Yii::$app->request->post('date');
        
        $archiver = Yii::$app->archiver;
        $result = $archiver->deleteArchive($type, $date);
        
        if ($result) {
            ArchiveLog::log('delete', 'manual', ['type' => $type, 'date' => $date]);
            
            return [
                'success' => true,
                'message' => 'Archiwum zostało usunięte',
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Nie udało się usunąć archiwum',
        ];
    }
    
    /**
     * Manualne uruchomienie archiwizacji (AJAX)
     */
    public function actionRunArchive()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $archiver = Yii::$app->archiver;
            $stats = $archiver->archiveOldData();
            
            return [
                'success' => true,
                'stats' => $stats,
                'message' => 'Archiwizacja zakończona pomyślnie',
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Pobierz archiwum jako plik
     */
    public function actionDownload($type, $date)
    {
        $archiver = Yii::$app->archiver;
        
        // Walidacja
        if (!in_array($type, ['task_executions', 'fetch_results'])) {
            throw new \yii\web\BadRequestHttpException('Invalid archive type');
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \yii\web\BadRequestHttpException('Invalid date format');
        }
        
        $filename = $archiver->archiveDir . '/' . $type . '/' . $date . '.jsonl.gz';
        
        if (!file_exists($filename)) {
            throw new \yii\web\NotFoundHttpException('Archive not found');
        }
        
        return Yii::$app->response->sendFile($filename, $type . '_' . $date . '.jsonl.gz');
    }
    
    /**
     * Historia logów archiwizacji
     */
    public function actionLogs()
    {
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => ArchiveLog::find()->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 30],
        ]);
        
        return $this->render('logs', [
            'dataProvider' => $dataProvider,
        ]);
    }
}
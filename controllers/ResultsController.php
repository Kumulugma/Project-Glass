<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\models\FetchResult;
use app\models\Task;

/**
 * ResultsController - zarządzanie wynikami fetcherów
 */
class ResultsController extends Controller
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
     * Lista wyników fetch
     */
    public function actionIndex($task_id = null, $fetcher = null, $status = null)
    {
        $query = FetchResult::find()
            ->with(['task'])
            ->orderBy(['fetched_at' => SORT_DESC]);
        
        // Filtrowanie
        if ($task_id) {
            $query->andWhere(['task_id' => $task_id]);
        }
        
        if ($fetcher) {
            $query->andWhere(['fetcher_class' => $fetcher]);
        }
        
        if ($status) {
            $query->andWhere(['status' => $status]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);
        
        // Lista tasków do filtrowania
        $tasks = Task::find()
            ->select(['id', 'name'])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        
        // Lista fetcherów
        $fetchers = FetchResult::find()
            ->select('fetcher_class')
            ->distinct()
            ->column();
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'tasks' => $tasks,
            'fetchers' => $fetchers,
            'selectedTaskId' => $task_id,
            'selectedFetcher' => $fetcher,
            'selectedStatus' => $status,
        ]);
    }
    
    /**
     * Szczegóły wyniku fetch
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Parsuj dane jeśli to JSON
        $parsedData = null;
        $isJson = false;
        
        if ($model->raw_data) {
            $decoded = json_decode($model->raw_data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $parsedData = $decoded;
                $isJson = true;
            }
        }
        
        return $this->render('view', [
            'model' => $model,
            'parsedData' => $parsedData,
            'isJson' => $isJson,
        ]);
    }
    
    /**
     * Czyszczenie starych wyników
     */
    public function actionCleanup($days = 30)
    {
        if (Yii::$app->request->isPost) {
            $deleted = FetchResult::cleanup($days);
            
            Yii::$app->session->setFlash('success', "Usunięto {$deleted} starych wyników (starszych niż {$days} dni).");
            return $this->redirect(['index']);
        }
        
        return $this->render('cleanup', [
            'days' => $days,
        ]);
    }
    
    /**
     * Export danych do JSON
     */
    public function actionExport($id)
    {
        $model = $this->findModel($id);
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        return [
            'id' => $model->id,
            'task_id' => $model->task_id,
            'task_name' => $model->task ? $model->task->name : null,
            'fetcher_class' => $model->fetcher_class,
            'source_info' => $model->getSourceInfoArray(),
            'raw_data' => $model->getRawDataArray(),
            'rows_count' => $model->rows_count,
            'data_size' => $model->data_size,
            'status' => $model->status,
            'fetched_at' => $model->fetched_at,
            'fetched_at_formatted' => date('Y-m-d H:i:s', $model->fetched_at),
        ];
    }
    
    /**
     * Statystyki fetchowania
     */
    public function actionStats()
    {
        // Statystyki per fetcher
        $fetcherStats = FetchResult::find()
            ->select([
                'fetcher_class',
                'COUNT(*) as count',
                'SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count',
                'SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count',
                'AVG(data_size) as avg_size',
            ])
            ->groupBy('fetcher_class')
            ->asArray()
            ->all();
        
        // Statystyki per task
        $taskStats = FetchResult::find()
            ->select([
                'task_id',
                'COUNT(*) as count',
                'MAX(fetched_at) as last_fetch',
            ])
            ->groupBy('task_id')
            ->with(['task'])
            ->asArray()
            ->all();
        
        // Ogólne statystyki
        $totalFetches = FetchResult::find()->count();
        $successRate = FetchResult::find()->where(['status' => 'success'])->count();
        $failedRate = FetchResult::find()->where(['status' => 'failed'])->count();
        
        return $this->render('stats', [
            'fetcherStats' => $fetcherStats,
            'taskStats' => $taskStats,
            'totalFetches' => $totalFetches,
            'successCount' => $successRate,
            'failedCount' => $failedRate,
            'successRate' => $totalFetches > 0 ? round(($successRate / $totalFetches) * 100, 2) : 0,
        ]);
    }
    
    /**
     * Znajduje model
     */
    private function findModel($id)
    {
        if (($model = FetchResult::findOne($id)) !== null) {
            return $model;
        }
        
        throw new \yii\web\NotFoundHttpException('Wynik nie został znaleziony.');
    }
}
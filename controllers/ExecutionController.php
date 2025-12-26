<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\models\TaskExecution;
use app\models\Task;

/**
 * ExecutionController - historia wykonań zadań
 */
class ExecutionController extends Controller
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
                        'roles' => ['@'], // Tylko zalogowani
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Historia wykonań
     */
    public function actionIndex($taskId = null, $status = null)
    {
        $query = TaskExecution::find()->with('task');
        
        if ($taskId) {
            $query->andWhere(['task_id' => $taskId]);
        }
        
        if ($status) {
            $query->andWhere(['status' => $status]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query->orderBy(['started_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 50],
        ]);
        
        // Statystyki
        $stats = [
            'all' => TaskExecution::find()->count(),
            'success' => TaskExecution::find()->where(['status' => 'success'])->count(),
            'failed' => TaskExecution::find()->where(['status' => 'failed'])->count(),
            'running' => TaskExecution::find()->where(['status' => 'running'])->count(),
        ];
        
        // Ostatnie 7 dni
        $sevenDaysAgo = strtotime('-7 days');
        $recentStats = [
            'executions' => TaskExecution::find()->where(['>=', 'started_at', $sevenDaysAgo])->count(),
            'success' => TaskExecution::find()->where(['and', ['status' => 'success'], ['>=', 'started_at', $sevenDaysAgo]])->count(),
            'failed' => TaskExecution::find()->where(['and', ['status' => 'failed'], ['>=', 'started_at', $sevenDaysAgo]])->count(),
        ];
        
        // Lista tasków do filtrowania
        $tasks = Task::find()->select(['id', 'name'])->orderBy(['name' => SORT_ASC])->all();
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
            'recentStats' => $recentStats,
            'tasks' => $tasks,
            'selectedTaskId' => $taskId,
            'selectedStatus' => $status,
        ]);
    }
    
    /**
     * Szczegóły wykonania
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }
    
    /**
     * Usunięcie wykonania
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        
        Yii::$app->session->setFlash('success', 'Wykonanie zostało usunięte.');
        return $this->redirect(['index']);
    }
    
    /**
     * Czyszczenie starych wykonań
     */
    public function actionCleanup($days = 30)
    {
        $timestamp = strtotime("-{$days} days");
        
        $count = TaskExecution::deleteAll(['<', 'started_at', $timestamp]);
        
        Yii::$app->session->setFlash('success', "Usunięto {$count} starych wykonań.");
        return $this->redirect(['index']);
    }
    
    /**
     * Znajduje model
     */
    protected function findModel($id)
    {
        if (($model = TaskExecution::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Wykonanie nie istnieje.');
    }
}
<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\models\Task;
use app\models\TaskExecution;
use app\models\TaskHistory;
use app\components\TaskRunner;
use app\components\ComponentRegistry;

/**
 * TaskController - ZAKTUALIZOWANY bez kategorii, z ComponentRegistry
 */
class TaskController extends Controller
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
     * Lista zadań - USUNIĘTO filtrowanie po category
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Task::find()->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }
    
    /**
     * Szczegóły zadania
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Historia wykonań
        $executionsProvider = new ActiveDataProvider([
            'query' => TaskExecution::find()
                ->where(['task_id' => $id])
                ->orderBy(['started_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
        
        // Historia zmian
        $historyProvider = new ActiveDataProvider([
            'query' => TaskHistory::find()
                ->where(['task_id' => $id])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
        
        return $this->render('view', [
            'model' => $model,
            'executionsProvider' => $executionsProvider,
            'historyProvider' => $historyProvider,
        ]);
    }
    
    /**
     * Tworzenie nowego zadania - UŻYWA ComponentRegistry
     */
    public function actionCreate()
    {
        $model = new Task();
        
        // Pobierz dostępne komponenty przez ComponentRegistry
        $parsers = ComponentRegistry::getAvailableParsers();
        $fetchers = ComponentRegistry::getAvailableFetchers();
        $channels = ComponentRegistry::getAvailableChannels();
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Zadanie utworzone pomyślnie.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        return $this->render('create', [
            'model' => $model,
            'parsers' => $parsers,
            'fetchers' => $fetchers,
            'channels' => $channels,
        ]);
    }
    
    /**
     * Edycja zadania - UŻYWA ComponentRegistry
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        // Pobierz dostępne komponenty
        $parsers = ComponentRegistry::getAvailableParsers();
        $fetchers = ComponentRegistry::getAvailableFetchers();
        $channels = ComponentRegistry::getAvailableChannels();
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Zadanie zaktualizowane pomyślnie.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        return $this->render('update', [
            'model' => $model,
            'parsers' => $parsers,
            'fetchers' => $fetchers,
            'channels' => $channels,
        ]);
    }
    
    /**
     * Usuń zadanie
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        
        Yii::$app->session->setFlash('success', 'Zadanie usunięte.');
        return $this->redirect(['index']);
    }
    
    /**
     * Ręczne uruchomienie zadania
     */
    public function actionRun($id)
    {
        $model = $this->findModel($id);
        
        try {
            $runner = new TaskRunner($model);
            $execution = $runner->run();
            
            if ($execution->isSuccess()) {
                Yii::$app->session->setFlash('success', 'Zadanie wykonane pomyślnie!');
            } else {
                Yii::$app->session->setFlash('warning', 'Zadanie zakończone z błędem: ' . $execution->error_message);
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Błąd wykonania: ' . $e->getMessage());
        }
        
        return $this->redirect(['view', 'id' => $model->id]);
    }
    
    /**
     * Oznacz jako wykonane
     */
    public function actionComplete($id)
    {
        $model = $this->findModel($id);
        
        if ($model->markAsCompleted()) {
            TaskHistory::recordChange($model, 'completed');
            Yii::$app->session->setFlash('success', 'Zadanie oznaczone jako wykonane.');
        }
        
        return $this->redirect(['view', 'id' => $model->id]);
    }
    
    /**
     * Cofnij oznaczenie jako wykonane
     */
    public function actionUncomplete($id)
    {
        $model = $this->findModel($id);
        
        if ($model->markAsUncompleted()) {
            Yii::$app->session->setFlash('success', 'Zadanie ponownie aktywne.');
        }
        
        return $this->redirect(['view', 'id' => $model->id]);
    }
    
    /**
     * Wstrzymaj zadanie
     */
    public function actionPause($id)
    {
        $model = $this->findModel($id);
        $model->status = 'paused';
        
        if ($model->save()) {
            TaskHistory::recordChange($model, 'paused');
            Yii::$app->session->setFlash('success', 'Zadanie wstrzymane.');
        }
        
        return $this->redirect(['view', 'id' => $model->id]);
    }
    
    /**
     * Wznów zadanie
     */
    public function actionResume($id)
    {
        $model = $this->findModel($id);
        $model->status = 'active';
        
        if ($model->save()) {
            TaskHistory::recordChange($model, 'resumed');
            Yii::$app->session->setFlash('success', 'Zadanie wznowione.');
        }
        
        return $this->redirect(['view', 'id' => $model->id]);
    }
    
    /**
     * Znajduje model
     */
    private function findModel($id)
    {
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        }
        
        throw new \yii\web\NotFoundHttpException('Zadanie nie zostało znalezione.');
    }
}
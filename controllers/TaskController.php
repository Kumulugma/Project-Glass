<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\models\Task;
use app\models\TaskExecution;
use app\models\TaskHistory;
use app\components\TaskRunner;

/**
 * TaskController dla web interface
 */
class TaskController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'run' => ['POST'],
                    'complete' => ['POST'],
                    'pause' => ['POST'],
                    'resume' => ['POST'],
                ],
            ],
        ];
    }
    
    /**
     * Lista tasków
     */
    public function actionIndex($category = null, $status = null)
    {
        $query = Task::find();
        
        if ($category) {
            $query->andWhere(['category' => $category]);
        }
        
        if ($status) {
            $query->andWhere(['status' => $status]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query->orderBy(['id' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);
        
        // Pobierz kategorie dla filtrowania
        $categories = Task::find()
            ->select('category')
            ->distinct()
            ->where(['not', ['category' => null]])
            ->column();
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'categories' => $categories,
            'selectedCategory' => $category,
            'selectedStatus' => $status,
        ]);
    }
    
    /**
     * Szczegóły taska
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
     * Tworzenie nowego taska
     */
    public function actionCreate($parser = null, $category = null)
    {
        $model = new Task();
        
        // Ustaw domyślne wartości z parametrów
        if ($parser) {
            $model->parser_class = $parser;
            
            // Ustaw domyślny fetcher
            $parserClass = '\\app\\components\\parsers\\' . $parser;
            if (class_exists($parserClass)) {
                $model->fetcher_class = $parserClass::getDefaultFetcherClass();
            }
        }
        
        if ($category) {
            $model->category = $category;
        }
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Task utworzony pomyślnie.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        // Lista dostępnych parserów
        $parsers = $this->getAvailableParsers();
        
        return $this->render('create', [
            'model' => $model,
            'parsers' => $parsers,
        ]);
    }
    
    /**
     * Edycja taska
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Task zaktualizowany pomyślnie.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        $parsers = $this->getAvailableParsers();
        
        return $this->render('update', [
            'model' => $model,
            'parsers' => $parsers,
        ]);
    }
    
    /**
     * Usuń task
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        
        Yii::$app->session->setFlash('success', 'Task usunięty.');
        return $this->redirect(['index']);
    }
    
    /**
     * Ręczne uruchomienie taska
     */
    public function actionRun($id)
    {
        $model = $this->findModel($id);
        
        try {
            $runner = new TaskRunner($model);
            $execution = $runner->run();
            
            if ($execution->isSuccess()) {
                Yii::$app->session->setFlash('success', 'Task wykonany pomyślnie!');
            } else {
                Yii::$app->session->setFlash('error', 'Błąd wykonania: ' . $execution->error_message);
            }
            
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Wyjątek: ' . $e->getMessage());
        }
        
        return $this->redirect(['view', 'id' => $id]);
    }
    
    /**
     * Oznacz jako wykonane
     */
    public function actionComplete($id)
    {
        $model = $this->findModel($id);
        $model->markAsCompleted();
        
        Yii::$app->session->setFlash('success', 'Task oznaczony jako wykonany.');
        return $this->redirect(['view', 'id' => $id]);
    }
    
    /**
     * Anuluj wykonanie
     */
    public function actionUncomplete($id)
    {
        $model = $this->findModel($id);
        $model->markAsUncompleted();
        
        Yii::$app->session->setFlash('success', 'Przywrócono status aktywny.');
        return $this->redirect(['view', 'id' => $id]);
    }
    
    /**
     * Wstrzymaj task
     */
    public function actionPause($id)
    {
        $model = $this->findModel($id);
        $model->status = 'paused';
        $model->save();
        
        Yii::$app->session->setFlash('success', 'Task wstrzymany.');
        return $this->redirect(['view', 'id' => $id]);
    }
    
    /**
     * Wznów task
     */
    public function actionResume($id)
    {
        $model = $this->findModel($id);
        $model->status = 'active';
        $model->save();
        
        Yii::$app->session->setFlash('success', 'Task wznowiony.');
        return $this->redirect(['view', 'id' => $id]);
    }
    
    /**
     * Znajdź model
     */
    protected function findModel($id)
    {
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        }
        
        throw new NotFoundHttpException('Task nie znaleziony.');
    }
    
    /**
     * Zwraca listę dostępnych parserów
     */
    private function getAvailableParsers()
    {
        return [
            'UrlHealthCheckParser' => 'Sprawdzenie dostępności URL',
            'JsonEndpointParser' => 'JSON API Endpoint',
            'ReminderParser' => 'Przypomnienie',
            'ShoppingItemParser' => 'Lista zakupów',
            'PlantReminderParser' => 'Kalendarz roślin',
            'AggregateParser' => 'Raport agregujący',
        ];
    }
}

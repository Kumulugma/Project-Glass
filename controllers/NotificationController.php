<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\models\NotificationQueue;
use app\models\Task;

/**
 * NotificationController - zarządzanie powiadomieniami
 */
class NotificationController extends Controller
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
     * Lista powiadomień
     */
    public function actionIndex($status = null)
    {
        $query = NotificationQueue::find()->with('task');
        
        if ($status) {
            $query->andWhere(['status' => $status]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 50],
        ]);
        
        // Statystyki
        $stats = [
            'all' => NotificationQueue::find()->count(),
            'pending' => NotificationQueue::find()->where(['status' => 'pending'])->count(),
            'sent' => NotificationQueue::find()->where(['status' => 'sent'])->count(),
            'failed' => NotificationQueue::find()->where(['status' => 'failed'])->count(),
        ];
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
            'selectedStatus' => $status,
        ]);
    }
    
    /**
     * Szczegóły powiadomienia
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }
    
    /**
     * Ponowne wysłanie powiadomienia
     */
    public function actionResend($id)
    {
        $model = $this->findModel($id);
        
        $model->status = 'pending';
        $model->sent_at = null;
        $model->error_message = null;
        $model->attempts = 0;
        
        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Powiadomienie zostało dodane do kolejki.');
        } else {
            Yii::$app->session->setFlash('error', 'Nie udało się dodać powiadomienia do kolejki.');
        }
        
        return $this->redirect(['index']);
    }
    
    /**
     * Usunięcie powiadomienia
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        
        Yii::$app->session->setFlash('success', 'Powiadomienie zostało usunięte.');
        return $this->redirect(['index']);
    }
    
    /**
     * Czyszczenie starych powiadomień
     */
    public function actionCleanup($days = 30)
    {
        $timestamp = time() - ($days * 24 * 60 * 60);
        
        $count = NotificationQueue::deleteAll([
            'and',
            ['status' => 'sent'],
            ['<', 'created_at', $timestamp]
        ]);
        
        Yii::$app->session->setFlash('success', "Usunięto {$count} starych powiadomień.");
        return $this->redirect(['index']);
    }
    
    /**
     * Znajduje model
     */
    protected function findModel($id)
    {
        if (($model = NotificationQueue::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Powiadomienie nie istnieje.');
    }
}
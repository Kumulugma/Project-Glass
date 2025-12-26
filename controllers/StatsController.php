<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\Task;
use app\models\TaskExecution;
use app\models\NotificationQueue;
use app\models\UserLog;

/**
 * StatsController - statystyki systemu
 */
class StatsController extends Controller
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
     * Strona główna statystyk
     */
    public function actionIndex()
    {
        // Statystyki tasków
        $taskStats = [
            'total' => Task::find()->count(),
            'active' => Task::find()->where(['status' => 'active'])->count(),
            'completed' => Task::find()->where(['status' => 'completed'])->count(),
            'paused' => Task::find()->where(['status' => 'paused'])->count(),
        ];
        
        // Taski wg kategorii
        $tasksByCategory = Task::find()
            ->select(['category', 'COUNT(*) as count'])
            ->where(['not', ['category' => null]])
            ->groupBy('category')
            ->asArray()
            ->all();
        
        // Wykonania w ostatnim miesiącu
        $lastMonthExecutions = TaskExecution::find()
            ->where(['>=', 'started_at', strtotime('-30 days')])
            ->count();
        
        $successfulExecutions = TaskExecution::find()
            ->where(['>=', 'started_at', strtotime('-30 days')])
            ->andWhere(['status' => 'success'])
            ->count();
        
        $failedExecutions = TaskExecution::find()
            ->where(['>=', 'started_at', strtotime('-30 days')])
            ->andWhere(['status' => 'failed'])
            ->count();
        
        // Powiadomienia w ostatnim miesiącu
        $lastMonthNotifications = NotificationQueue::find()
            ->where(['>=', 'created_at', strtotime('-30 days')])
            ->count();
        
        $sentNotifications = NotificationQueue::find()
            ->where(['>=', 'created_at', strtotime('-30 days')])
            ->andWhere(['status' => 'sent'])
            ->count();
        
        // Wykonania wg dni (ostatnie 30 dni)
        $executionsByDay = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = TaskExecution::find()
                ->where(['>=', 'started_at', strtotime($date . ' 00:00:00')])
                ->andWhere(['<', 'started_at', strtotime($date . ' 23:59:59')])
                ->count();
            
            $executionsByDay[] = [
                'date' => $date,
                'count' => $count,
                'label' => date('d.m', strtotime($date)),
            ];
        }
        
        // Rachunki w tym miesiącu
        $billsThisMonth = Task::find()
            ->where(['category' => 'rachunki'])
            ->andWhere(['>=', 'due_date', date('Y-m-01')])
            ->andWhere(['<=', 'due_date', date('Y-m-t')])
            ->sum('amount') ?: 0;
        
        $billsPaid = Task::find()
            ->where(['category' => 'rachunki', 'status' => 'completed'])
            ->andWhere(['>=', 'due_date', date('Y-m-01')])
            ->andWhere(['<=', 'due_date', date('Y-m-t')])
            ->sum('amount') ?: 0;
        
        // Najczęściej uruchamiane taski
        $topTasks = TaskExecution::find()
            ->select(['task_id', 'COUNT(*) as execution_count'])
            ->where(['>=', 'started_at', strtotime('-30 days')])
            ->groupBy('task_id')
            ->orderBy(['execution_count' => SORT_DESC])
            ->limit(10)
            ->asArray()
            ->all();
        
        // Pobierz nazwy tasków
        foreach ($topTasks as &$taskData) {
            $task = Task::findOne($taskData['task_id']);
            $taskData['task_name'] = $task ? $task->name : 'Usunięty task';
            $taskData['category'] = $task ? $task->category : null;
        }
        
        // Aktywność użytkowników (ostatnie 30 dni)
        $userActivity = UserLog::find()
            ->select(['action', 'COUNT(*) as count'])
            ->where(['>=', 'created_at', strtotime('-30 days')])
            ->groupBy('action')
            ->orderBy(['count' => SORT_DESC])
            ->limit(10)
            ->asArray()
            ->all();
        
        return $this->render('index', [
            'taskStats' => $taskStats,
            'tasksByCategory' => $tasksByCategory,
            'lastMonthExecutions' => $lastMonthExecutions,
            'successfulExecutions' => $successfulExecutions,
            'failedExecutions' => $failedExecutions,
            'lastMonthNotifications' => $lastMonthNotifications,
            'sentNotifications' => $sentNotifications,
            'executionsByDay' => $executionsByDay,
            'billsThisMonth' => $billsThisMonth,
            'billsPaid' => $billsPaid,
            'topTasks' => $topTasks,
            'userActivity' => $userActivity,
        ]);
    }
}
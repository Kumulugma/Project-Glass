<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\models\Task;
use app\models\TaskExecution;
use app\models\NotificationQueue;

/**
 * DashboardController - główny widok aplikacji
 */
class DashboardController extends Controller
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
     * Dashboard - strona główna
     */
    public function actionIndex()
    {
        // Statystyki tasków
        $taskStats = [
            'total' => Task::find()->count(),
            'active' => Task::find()->where(['status' => 'active'])->count(),
            'paused' => Task::find()->where(['status' => 'paused'])->count(),
            'completed' => Task::find()->where(['status' => 'completed'])->count(),
        ];
        
        // Taski wg kategorii
        $tasksByCategory = Task::find()
            ->select(['category', 'COUNT(*) as count'])
            ->where(['not', ['category' => null]])
            ->groupBy('category')
            ->asArray()
            ->all();
        
        // Nadchodzące przypomnienia (najbliższe 10)
        $upcomingTasks = Task::find()
            ->where(['status' => 'active'])
            ->andWhere(['not', ['due_date' => null]])
            ->andWhere(['>=', 'due_date', date('Y-m-d')])
            ->orderBy(['due_date' => SORT_ASC])
            ->limit(10)
            ->all();
        
        // Przeterminowane taski
        $overdueTasks = Task::find()
            ->where(['status' => 'active'])
            ->andWhere(['not', ['due_date' => null]])
            ->andWhere(['<', 'due_date', date('Y-m-d')])
            ->orderBy(['due_date' => SORT_ASC])
            ->all();
        
        // Ostatnie wykonania (10)
        $recentExecutions = TaskExecution::find()
            ->with('task')
            ->orderBy(['started_at' => SORT_DESC])
            ->limit(10)
            ->all();
        
        // Statystyki powiadomień
        $notificationStats = [
            'pending' => NotificationQueue::find()->where(['status' => 'pending'])->count(),
            'sent_today' => NotificationQueue::find()
                ->where(['status' => 'sent'])
                ->andWhere(['>=', 'FROM_UNIXTIME(sent_at)', date('Y-m-d 00:00:00')])
                ->count(),
            'failed' => NotificationQueue::find()->where(['status' => 'failed'])->count(),
        ];
        
        // Suma rachunków w bieżącym miesiącu
        $billsSum = Task::find()
            ->where(['category' => 'rachunki', 'status' => 'active'])
            ->andWhere(['>=', 'due_date', date('Y-m-01')])
            ->andWhere(['<=', 'due_date', date('Y-m-t')])
            ->sum('amount') ?: 0;
        
        // Lista zakupów
        $shoppingItems = Task::find()
            ->where(['category' => 'zakupy', 'status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
        
        $shoppingSum = array_sum(array_column($shoppingItems, 'amount'));
        
        return $this->render('index', [
            'taskStats' => $taskStats,
            'tasksByCategory' => $tasksByCategory,
            'upcomingTasks' => $upcomingTasks,
            'overdueTasks' => $overdueTasks,
            'recentExecutions' => $recentExecutions,
            'notificationStats' => $notificationStats,
            'billsSum' => $billsSum,
            'shoppingItems' => $shoppingItems,
            'shoppingSum' => $shoppingSum,
        ]);
    }
    
    /**
     * Dashboard mobilny - uproszczony widok
     */
    public function actionMobile()
    {
        // To dzisiaj do zrobienia
        $today = date('Y-m-d');
        
        $todayTasks = Task::find()
            ->where(['status' => 'active'])
            ->andWhere([
                'or',
                ['due_date' => $today],
                ['<', 'due_date', $today], // Przeterminowane też pokazujemy
            ])
            ->orderBy(['due_date' => SORT_ASC])
            ->all();
        
        // Lista zakupów
        $shoppingItems = Task::find()
            ->where(['category' => 'zakupy', 'status' => 'active'])
            ->all();
        
        // Grupuj zakupy po kategorii
        $shoppingNormal = [];
        $shoppingSpecial = [];
        
        foreach ($shoppingItems as $item) {
            $config = $item->getConfigArray();
            $category = $config['shopping_category'] ?? 'normal';
            
            if ($category === 'special') {
                $shoppingSpecial[] = $item;
            } else {
                $shoppingNormal[] = $item;
            }
        }
        
        return $this->render('mobile', [
            'todayTasks' => $todayTasks,
            'shoppingNormal' => $shoppingNormal,
            'shoppingSpecial' => $shoppingSpecial,
        ]);
    }
}
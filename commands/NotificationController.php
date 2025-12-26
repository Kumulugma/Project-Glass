<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\NotificationQueue;
use app\components\ComponentRegistry;

/**
 * Console controller dla przetwarzania powiadomień
 */
class NotificationController extends Controller
{
    /**
     * Przetwarza kolejkę powiadomień (główna metoda)
     * 
     * Użycie: php yii notification/process
     */
    public function actionProcess()
    {
        $this->stdout("Processing notification queue...\n", \yii\helpers\Console::FG_CYAN);
        
        // Pobierz oczekujące powiadomienia
        $notifications = NotificationQueue::find()
            ->where(['status' => 'pending'])
            ->orderBy(['priority' => SORT_DESC, 'created_at' => SORT_ASC])
            ->limit(100)
            ->all();
        
        if (empty($notifications)) {
            $this->stdout("No pending notifications.\n", \yii\helpers\Console::FG_YELLOW);
            return ExitCode::OK;
        }
        
        $this->stdout("Found " . count($notifications) . " notification(s) to send:\n\n");
        
        $sent = 0;
        $failed = 0;
        
        foreach ($notifications as $notification) {
            $this->stdout("  [{$notification->id}] {$notification->channel} to {$notification->recipient}... ");
            
            // Wyślij powiadomienie
            $result = $this->sendNotification($notification);
            
            if ($result['success']) {
                $this->stdout("✓ Sent\n", \yii\helpers\Console::FG_GREEN);
                $notification->markAsSent($result['response']);
                $sent++;
            } else {
                $this->stdout("✗ Failed: {$result['error']}\n", \yii\helpers\Console::FG_RED);
                $notification->markAsFailed($result['error']);
                $failed++;
            }
        }
        
        // Podsumowanie
        $this->stdout("\nSummary:\n", \yii\helpers\Console::BOLD);
        $this->stdout("  Sent: {$sent}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("  Failed: {$failed}\n", \yii\helpers\Console::FG_RED);
        $this->stdout("  Total: " . count($notifications) . "\n\n");
        
        return ExitCode::OK;
    }
    
    /**
     * Wysyła powiadomienie przez odpowiedni kanał
     *
     * @param NotificationQueue $notification
     * @return array
     */
    private function sendNotification($notification)
    {
        // Pobierz channel przez ComponentRegistry
        $channel = ComponentRegistry::getChannel($notification->channel);
        
        if (!$channel) {
            return [
                'success' => false,
                'response' => null,
                'error' => "Channel nie został znaleziony: {$notification->channel}",
            ];
        }
        
        // Sprawdź czy channel jest skonfigurowany i włączony
        if (!$channel->isAvailable()) {
            return [
                'success' => false,
                'response' => null,
                'error' => "Channel '{$notification->channel}' nie jest dostępny lub wyłączony. Sprawdź /settings/channel?id={$notification->channel}",
            ];
        }
        
        // Wyślij powiadomienie
        try {
            return $channel->send($notification);
        } catch (\Exception $e) {
            Yii::error([
                'message' => 'Channel send exception',
                'channel' => $notification->channel,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], __METHOD__);
            
            return [
                'success' => false,
                'response' => null,
                'error' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Listuje powiadomienia w kolejce
     * 
     * Użycie: php yii notification/list [status]
     * 
     * @param string|null $status Status do filtrowania (pending, sent, failed)
     */
    public function actionList($status = null)
    {
        $query = NotificationQueue::find()->orderBy(['created_at' => SORT_DESC]);
        
        if ($status) {
            $query->where(['status' => $status]);
        }
        
        $notifications = $query->limit(50)->all();
        
        if (empty($notifications)) {
            $this->stdout("No notifications found.\n", \yii\helpers\Console::FG_YELLOW);
            return ExitCode::OK;
        }
        
        $this->stdout(sprintf("%-5s %-10s %-30s %-10s %-10s %-20s\n",
            'ID', 'Channel', 'Recipient', 'Status', 'Attempts', 'Created'
        ), \yii\helpers\Console::BOLD);
        
        $this->stdout(str_repeat('-', 100) . "\n");
        
        foreach ($notifications as $notification) {
            $color = match($notification->status) {
                'sent' => \yii\helpers\Console::FG_GREEN,
                'failed' => \yii\helpers\Console::FG_RED,
                'pending' => \yii\helpers\Console::FG_YELLOW,
                default => \yii\helpers\Console::FG_GREY,
            };
            
            $this->stdout(sprintf("%-5s %-10s %-30s %-10s %-10s %-20s\n",
                $notification->id,
                substr($notification->channel, 0, 10),
                substr($notification->recipient, 0, 30),
                $notification->status,
                $notification->attempts,
                date('Y-m-d H:i:s', $notification->created_at)
            ), $color);
        }
        
        $this->stdout("\nTotal: " . count($notifications) . "\n");
        
        return ExitCode::OK;
    }
    
    /**
     * Retry failed notifications
     * 
     * Użycie: php yii notification/retry [limit]
     * 
     * @param int $limit Maksymalna liczba powiadomień do ponowienia (domyślnie 10)
     */
    public function actionRetry($limit = 10)
    {
        $this->stdout("Retrying failed notifications...\n", \yii\helpers\Console::FG_CYAN);
        
        // Pobierz nieudane powiadomienia
        $notifications = NotificationQueue::find()
            ->where(['status' => 'failed'])
            ->andWhere(['<', 'attempts', 3]) // Maksymalnie 3 próby
            ->orderBy(['created_at' => SORT_ASC])
            ->limit($limit)
            ->all();
        
        if (empty($notifications)) {
            $this->stdout("No failed notifications to retry.\n", \yii\helpers\Console::FG_YELLOW);
            return ExitCode::OK;
        }
        
        $this->stdout("Found " . count($notifications) . " notification(s) to retry:\n\n");
        
        $success = 0;
        $failed = 0;
        
        foreach ($notifications as $notification) {
            $this->stdout("  [{$notification->id}] {$notification->channel} to {$notification->recipient}... ");
            
            // Zresetuj status na pending
            $notification->status = 'pending';
            $notification->save(false, ['status']);
            
            // Wyślij ponownie
            $result = $this->sendNotification($notification);
            
            if ($result['success']) {
                $this->stdout("✓ Sent\n", \yii\helpers\Console::FG_GREEN);
                $notification->markAsSent($result['response']);
                $success++;
            } else {
                $this->stdout("✗ Failed again: {$result['error']}\n", \yii\helpers\Console::FG_RED);
                $notification->markAsFailed($result['error']);
                $failed++;
            }
        }
        
        // Podsumowanie
        $this->stdout("\nRetry Summary:\n", \yii\helpers\Console::BOLD);
        $this->stdout("  Success: {$success}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("  Failed: {$failed}\n", \yii\helpers\Console::FG_RED);
        $this->stdout("  Total: " . count($notifications) . "\n\n");
        
        return ExitCode::OK;
    }
    
    /**
     * Czyści stare powiadomienia
     * 
     * Użycie: php yii notification/cleanup [days]
     * 
     * @param int $days Usuń powiadomienia starsze niż X dni (domyślnie 30)
     */
    public function actionCleanup($days = 30)
    {
        $this->stdout("Cleaning up old notifications (older than {$days} days)...\n", \yii\helpers\Console::FG_CYAN);
        
        $cutoffDate = time() - ($days * 86400);
        
        $count = NotificationQueue::deleteAll(['<', 'created_at', $cutoffDate]);
        
        if ($count > 0) {
            $this->stdout("Deleted {$count} old notification(s).\n", \yii\helpers\Console::FG_GREEN);
        } else {
            $this->stdout("No old notifications to delete.\n", \yii\helpers\Console::FG_YELLOW);
        }
        
        return ExitCode::OK;
    }
    
    /**
     * Alias dla actionProcess (dla kompatybilności wstecznej)
     * 
     * Użycie: php yii notification/send-pending
     */
    public function actionSendPending()
    {
        return $this->actionProcess();
    }
    
    /**
     * Pokazuje statystyki powiadomień
     * 
     * Użycie: php yii notification/stats
     */
    public function actionStats()
    {
        $this->stdout("=== Notification Queue Statistics ===\n\n", \yii\helpers\Console::BOLD);
        
        // Statystyki według statusu
        $pending = NotificationQueue::find()->where(['status' => 'pending'])->count();
        $sent = NotificationQueue::find()->where(['status' => 'sent'])->count();
        $failed = NotificationQueue::find()->where(['status' => 'failed'])->count();
        
        $this->stdout("By Status:\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout("  Pending: {$pending}\n", \yii\helpers\Console::FG_YELLOW);
        $this->stdout("  Sent: {$sent}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("  Failed: {$failed}\n", \yii\helpers\Console::FG_RED);
        $this->stdout("\n");
        
        // Statystyki według kanału
        $this->stdout("By Channel:\n", \yii\helpers\Console::FG_CYAN);
        
        $channels = NotificationQueue::find()
            ->select(['channel', 'COUNT(*) as count'])
            ->groupBy('channel')
            ->asArray()
            ->all();
        
        foreach ($channels as $channel) {
            $this->stdout("  {$channel['channel']}: {$channel['count']}\n");
        }
        
        $this->stdout("\n");
        
        // Ostatnie powiadomienia
        $this->stdout("Recent Activity (last 24h):\n", \yii\helpers\Console::FG_CYAN);
        
        $last24h = time() - 86400;
        $recentCount = NotificationQueue::find()
            ->where(['>', 'created_at', $last24h])
            ->count();
        
        $this->stdout("  Total: {$recentCount}\n");
        
        return ExitCode::OK;
    }
}
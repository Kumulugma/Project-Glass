<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\NotificationQueue;
use app\components\notifications\EmailChannel;
use app\components\notifications\SmsChannel;
use app\components\notifications\PushChannel;
use app\components\notifications\TelegramChannel;

/**
 * Console controller dla przetwarzania powiadomień
 */
class NotificationController extends Controller
{
    /**
     * Przetwarza kolejkę powiadomień
     * 
     * Użycie: php yii notification/process
     * 
     * @param int $limit Ile powiadomień przetworzyć na raz (domyślnie 50)
     */
    public function actionProcess($limit = 50)
    {
        $this->stdout("=== Notification Queue Processor ===\n");
        $this->stdout("Time: " . date('Y-m-d H:i:s') . "\n\n");
        
        // Pobierz gotowe do wysłania powiadomienia
        $notifications = NotificationQueue::findReadyToSend($limit);
        
        if (empty($notifications)) {
            $this->stdout("No notifications to send.\n");
            return ExitCode::OK;
        }
        
        $this->stdout("Found " . count($notifications) . " notification(s) to send:\n\n");
        
        $sentCount = 0;
        $failedCount = 0;
        
        foreach ($notifications as $notification) {
            $this->stdout("  [{$notification->id}] {$notification->channel} to {$notification->recipient}... ");
            
            // Oznacz jako przetwarzane
            $notification->markAsProcessing();
            
            try {
                $result = $this->sendNotification($notification);
                
                if ($result['success']) {
                    $notification->markAsSent($result['response']);
                    $this->stdout("✓ SENT\n", \yii\helpers\Console::FG_GREEN);
                    $sentCount++;
                } else {
                    $notification->markAsFailed($result['error']);
                    
                    if ($notification->canRetry()) {
                        $this->stdout("✗ FAILED (will retry): {$result['error']}\n", \yii\helpers\Console::FG_YELLOW);
                        // Przywróć status pending dla retry
                        $notification->status = 'pending';
                        $notification->save(false, ['status']);
                    } else {
                        $this->stdout("✗ FAILED (max attempts): {$result['error']}\n", \yii\helpers\Console::FG_RED);
                    }
                    
                    $failedCount++;
                }
                
            } catch (\Exception $e) {
                $notification->markAsFailed($e);
                $this->stdout("✗ EXCEPTION: {$e->getMessage()}\n", \yii\helpers\Console::FG_RED);
                $failedCount++;
            }
        }
        
        $this->stdout("\n=== Summary ===\n");
        $this->stdout("Sent: {$sentCount}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("Failed: {$failedCount}\n", $failedCount > 0 ? \yii\helpers\Console::FG_RED : \yii\helpers\Console::FG_GREEN);
        $this->stdout("Total: " . count($notifications) . "\n\n");
        
        return ExitCode::OK;
    }
    
    /**
     * Wysyła powiadomienie przez odpowiedni kanał
     */
    private function sendNotification(NotificationQueue $notification)
    {
        $channel = $this->getChannel($notification->channel);
        
        if (!$channel) {
            return [
                'success' => false,
                'response' => null,
                'error' => "Unknown channel: {$notification->channel}",
            ];
        }
        
        if (!$channel->isAvailable()) {
            return [
                'success' => false,
                'response' => null,
                'error' => "Channel {$notification->channel} is not available (check config)",
            ];
        }
        
        return $channel->send($notification);
    }
    
    /**
     * Zwraca instancję kanału powiadomień
     */
    private function getChannel($channelName)
    {
        switch ($channelName) {
            case 'email':
                return new EmailChannel();
            
            case 'sms':
                return new SmsChannel();
            
            case 'push':
                return new PushChannel();
            
            case 'telegram':
                return new TelegramChannel();
            
            default:
                return null;
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
            $this->stdout("No notifications found.\n");
            return ExitCode::OK;
        }
        
        $this->stdout(sprintf("%-5s %-10s %-20s %-10s %-15s %-20s\n",
            'ID', 'Channel', 'Recipient', 'Status', 'Attempts', 'Created'
        ));
        $this->stdout(str_repeat('-', 100) . "\n");
        
        foreach ($notifications as $notification) {
            $created = date('Y-m-d H:i', $notification->created_at);
            $recipient = mb_substr($notification->recipient, 0, 20);
            
            $color = match($notification->status) {
                'sent' => \yii\helpers\Console::FG_GREEN,
                'failed' => \yii\helpers\Console::FG_RED,
                'processing' => \yii\helpers\Console::FG_YELLOW,
                default => \yii\helpers\Console::FG_GREY,
            };
            
            $this->stdout(sprintf("%-5s %-10s %-20s ",
                $notification->id,
                $notification->channel,
                $recipient
            ));
            $this->stdout(sprintf("%-10s ", $notification->status), $color);
            $this->stdout(sprintf("%-15s %-20s\n",
                "{$notification->attempts}/{$notification->max_attempts}",
                $created
            ));
        }
        
        $this->stdout("\nShowing last 50 notification(s)\n");
        
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
        $this->stdout("Cleaning up notifications older than {$days} days...\n");
        
        $threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = NotificationQueue::deleteAll([
            'and',
            ['in', 'status', ['sent', 'failed']],
            ['<', 'FROM_UNIXTIME(created_at)', $threshold]
        ]);
        
        $this->stdout("Deleted {$deleted} old notification(s)\n");
        
        return ExitCode::OK;
    }
    
    /**
     * Retry failed notifications
     * 
     * Użycie: php yii notification/retry [id]
     * 
     * @param int|null $id ID konkretnego powiadomienia lub null dla wszystkich failed
     */
    public function actionRetry($id = null)
    {
        if ($id) {
            $notification = NotificationQueue::findOne($id);
            
            if (!$notification) {
                $this->stderr("Notification #{$id} not found.\n");
                return ExitCode::DATAERR;
            }
            
            if (!in_array($notification->status, ['failed', 'cancelled'])) {
                $this->stderr("Notification #{$id} cannot be retried (status: {$notification->status}).\n");
                return ExitCode::DATAERR;
            }
            
            $notification->status = 'pending';
            $notification->attempts = 0;
            $notification->error_message = null;
            $notification->save();
            
            $this->stdout("Notification #{$id} marked for retry.\n");
            
        } else {
            $count = NotificationQueue::updateAll(
                ['status' => 'pending', 'attempts' => 0, 'error_message' => null],
                ['status' => 'failed']
            );
            
            $this->stdout("Marked {$count} failed notification(s) for retry.\n");
        }
        
        return ExitCode::OK;
    }
}

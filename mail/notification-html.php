<?php

use yii\helpers\Html;

/** @var \yii\web\View $this */
/** @var string $subject */
/** @var string $message */
/** @var \app\models\Task $task */

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .content p {
            margin: 0 0 15px 0;
        }
        .message-box {
            background: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .task-details {
            background: #f8fafc;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .task-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .task-details td {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .task-details td:first-child {
            font-weight: 600;
            width: 40%;
            color: #64748b;
        }
        .task-details td:last-child {
            color: #1e293b;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        .footer {
            background: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📬 <?= Html::encode($subject) ?></h1>
        </div>
        
        <div class="content">
            
            <?php if (isset($task)): ?>
            <div class="task-details">
                <table>
                    <tr>
                        <td>Zadanie:</td>
                        <td><strong><?= Html::encode($task->name) ?></strong></td>
                    </tr>
                    <?php if ($task->category): ?>
                    <tr>
                        <td>Kategoria:</td>
                        <td>
                            <?php
                            $badges = [
                                'rachunki' => 'warning',
                                'zakupy' => 'info',
                                'rośliny' => 'success',
                                'monitoring' => 'info',
                            ];
                            $badgeClass = $badges[$task->category] ?? 'info';
                            ?>
                            <span class="badge badge-<?= $badgeClass ?>"><?= Html::encode($task->category) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($task->due_date): ?>
                    <tr>
                        <td>Termin:</td>
                        <td><?= Yii::$app->formatter->asDate($task->due_date) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($task->amount): ?>
                    <tr>
                        <td>Kwota:</td>
                        <td><strong><?= Yii::$app->formatter->asCurrency($task->amount, $task->currency) ?></strong></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td>Status:</td>
                        <td>
                            <?php
                            $statusBadges = [
                                'active' => ['success', 'Aktywne'],
                                'paused' => ['warning', 'Wstrzymane'],
                                'completed' => ['info', 'Wykonane'],
                            ];
                            $statusInfo = $statusBadges[$task->status] ?? ['info', ucfirst($task->status)];
                            ?>
                            <span class="badge badge-<?= $statusInfo[0] ?>"><?= $statusInfo[1] ?></span>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="message-box">
                <?= Html::encode($message) ?>
            </div>
            
            <div class="info-box">
                <strong>ℹ️ Informacja</strong>
                <p style="margin: 10px 0 0 0;">
                    To powiadomienie zostało wygenerowane automatycznie przez system GlassSystem.
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p style="margin: 0 0 10px 0;">© <?= date('Y') ?> GlassSystem. Wszystkie prawa zastrzeżone.</p>
            <p style="margin: 0;">
                Wspierane przez 
                <a href="https://k3e.pl" target="_blank">K3e.pl</a>
            </p>
        </div>
    </div>
</body>
</html>
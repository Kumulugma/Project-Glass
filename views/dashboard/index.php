<?php

/** @var yii\web\View $this */
/** @var array $taskStats */
/** @var app\models\Task[] $upcomingTasks */
/** @var app\models\Task[] $overdueTasks */
/** @var app\models\TaskExecution[] $recentExecutions */
/** @var array $notificationStats */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard - ' . Yii::$app->name;
?>

<div class="dashboard-index">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>📊 Dashboard</h1>
        <div>
            <?= Html::a('📱 Widok mobilny', ['/dashboard/mobile'], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a('➕ Nowe zadanie', ['/task/create'], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <!-- Statystyki zadań -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Wszystkie zadania</h5>
                    <p class="card-text display-4"><?= $taskStats['total'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Aktywne</h5>
                    <p class="card-text display-4"><?= $taskStats['active'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Wstrzymane</h5>
                    <p class="card-text display-4"><?= $taskStats['paused'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Wykonane</h5>
                    <p class="card-text display-4"><?= $taskStats['completed'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Lewa kolumna -->
        <div class="col-lg-8">
            
            <!-- Przeterminowane zadania -->
            <?php if (!empty($overdueTasks)): ?>
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">⚠️ Przeterminowane zadania (<?= count($overdueTasks) ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($overdueTasks as $task): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?= Html::a(Html::encode($task->name), ['/task/view', 'id' => $task->id]) ?>
                                            <?php if ($task->category): ?>
                                                <span class="badge bg-secondary"><?= $task->category ?></span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted">
                                            Termin: <?= Yii::$app->formatter->asDate($task->due_date) ?>
                                            <?php if ($task->amount): ?>
                                                | Kwota: <?= Yii::$app->formatter->asCurrency($task->amount, 'PLN') ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?= Html::a('Zobacz', ['/task/view', 'id' => $task->id], ['class' => 'btn btn-sm btn-outline-danger']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Nadchodzące zadania -->
            <?php if (!empty($upcomingTasks)): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📅 Nadchodzące zadania (<?= count($upcomingTasks) ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingTasks as $task): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?= Html::a(Html::encode($task->name), ['/task/view', 'id' => $task->id]) ?>
                                            <?php if ($task->category): ?>
                                                <span class="badge bg-secondary"><?= $task->category ?></span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted">
                                            Termin: <?= Yii::$app->formatter->asDate($task->due_date) ?>
                                            <?php if ($task->amount): ?>
                                                | Kwota: <?= Yii::$app->formatter->asCurrency($task->amount, 'PLN') ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?= Html::a('Zobacz', ['/task/view', 'id' => $task->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ostatnie wykonania -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">⚡ Ostatnie wykonania</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentExecutions)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentExecutions as $execution): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <?= Html::encode($execution->task ? $execution->task->name : 'Usunięte zadanie') ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?= Yii::$app->formatter->asDatetime($execution->started_at) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php if ($execution->status === 'success'): ?>
                                                <span class="badge bg-success">✓ Sukces</span>
                                            <?php elseif ($execution->status === 'failed'): ?>
                                                <span class="badge bg-danger">✗ Błąd</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">⏳ W trakcie</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Brak wykonań</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Prawa kolumna -->
        <div class="col-lg-4">
            
            <!-- Statystyki powiadomień -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🔔 Powiadomienia</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            Oczekujące: <span class="badge bg-warning"><?= $notificationStats['pending'] ?></span>
                        </li>
                        <li class="mb-2">
                            Wysłane dzisiaj: <span class="badge bg-success"><?= $notificationStats['sent_today'] ?></span>
                        </li>
                        <li>
                            Błędy: <span class="badge bg-danger"><?= $notificationStats['failed'] ?></span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

</div>
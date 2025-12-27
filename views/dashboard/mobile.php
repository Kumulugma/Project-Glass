<?php

/** @var yii\web\View $this */
/** @var app\models\Task[] $todayTasks */

use yii\helpers\Html;

$this->title = 'Dashboard Mobile - ' . Yii::$app->name;
?>

<div class="dashboard-mobile">

    <div class="text-center mb-4">
        <h1>📱 Dzisiejsze zadania</h1>
        <p class="text-muted"><?= date('d.m.Y') ?></p>
    </div>

    <!-- Zadania na dziś -->
    <?php if (!empty($todayTasks)): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">✓ Do zrobienia (<?= count($todayTasks) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($todayTasks as $task): ?>
                    <?php
                    $isOverdue = $task->due_date && $task->due_date < date('Y-m-d');
                    $itemClass = $isOverdue ? 'list-group-item-danger' : '';
                    ?>
                    <div class="list-group-item <?= $itemClass ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="task-<?= $task->id ?>">
                                    <label class="form-check-label" for="task-<?= $task->id ?>">
                                        <strong><?= Html::encode($task->name) ?></strong>
                                        <?php if ($isOverdue): ?>
                                            <span class="badge bg-danger ms-2">PRZETERMINOWANE</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                <div class="mt-2">
                                    <?php if ($task->category): ?>
                                        <span class="badge bg-secondary"><?= $task->category ?></span>
                                    <?php endif; ?>
                                    <?php if ($task->amount): ?>
                                        <span class="badge bg-success"><?= Yii::$app->formatter->asCurrency($task->amount, 'PLN') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ms-2">
                                <?= Html::a('▶', ['/task/run', 'id' => $task->id], [
                                    'class' => 'btn btn-sm btn-outline-primary',
                                    'data-method' => 'post',
                                    'title' => 'Uruchom teraz'
                                ]) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card mb-4">
        <div class="card-body p-4 text-center text-muted">
            <p class="mb-0">🎉 Brak zadań na dziś!</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Przycisk do pełnego dashboardu -->
    <div class="text-center mb-4">
        <?= Html::a('💻 Pełny dashboard', ['/dashboard/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

</div>

<style>
/* Mobilne style */
@media (max-width: 768px) {
    .dashboard-mobile .card {
        font-size: 1.1rem;
    }
    
    .dashboard-mobile .btn-lg {
        padding: 1rem;
        font-size: 1.2rem;
    }
    
    .dashboard-mobile .form-check-input {
        width: 1.5rem;
        height: 1.5rem;
    }
    
    .dashboard-mobile .form-check-label {
        padding-left: 0.5rem;
    }
}
</style>
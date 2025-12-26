<?php

/** @var yii\web\View $this */
/** @var app\models\Task[] $todayTasks */
/** @var array $shoppingNormal */
/** @var array $shoppingSpecial */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard Mobilny - ' . Yii::$app->name;
?>

<div class="dashboard-mobile">
    
    <div class="text-center mb-4">
        <h1>📱 <?= date('d.m.Y') ?></h1>
        <p class="text-muted"><?= strftime('%A') ?></p>
    </div>

    <!-- Szybkie akcje -->
    <div class="row mb-4">
        <div class="col-6 mb-2">
            <?= Html::a('➕ Nowe zadanie', ['/task/create'], ['class' => 'btn btn-primary w-100 btn-lg']) ?>
        </div>
        <div class="col-6 mb-2">
            <?= Html::a('📋 Wszystkie', ['/task/index'], ['class' => 'btn btn-outline-primary w-100 btn-lg']) ?>
        </div>
    </div>

    <!-- Do zrobienia dzisiaj -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">✅ Do zrobienia dzisiaj (<?= count($todayTasks) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($todayTasks)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($todayTasks as $task): ?>
                        <?php
                        $isOverdue = $task->due_date < date('Y-m-d');
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
            <?php else: ?>
                <div class="p-4 text-center text-muted">
                    <p class="mb-0">🎉 Brak zadań na dziś!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista zakupów -->
    <?php if (!empty($shoppingNormal) || !empty($shoppingSpecial)): ?>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">🛒 Lista zakupów</h5>
        </div>
        <div class="card-body p-0">
            
            <?php if (!empty($shoppingNormal)): ?>
                <div class="p-3 border-bottom">
                    <h6 class="text-muted mb-3">Zwykłe zakupy</h6>
                    <div class="list-group list-group-flush">
                        <?php foreach ($shoppingNormal as $item): ?>
                            <div class="list-group-item px-0">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="shop-<?= $item->id ?>">
                                    <label class="form-check-label" for="shop-<?= $item->id ?>">
                                        <?= Html::encode($item->name) ?>
                                        <?php if ($item->amount): ?>
                                            <span class="text-muted ms-2">(<?= Yii::$app->formatter->asCurrency($item->amount, 'PLN') ?>)</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($shoppingSpecial)): ?>
                <div class="p-3">
                    <h6 class="text-muted mb-3">Specjalne / sklepy specjalistyczne</h6>
                    <div class="list-group list-group-flush">
                        <?php foreach ($shoppingSpecial as $item): ?>
                            <div class="list-group-item px-0">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="shop-special-<?= $item->id ?>">
                                    <label class="form-check-label" for="shop-special-<?= $item->id ?>">
                                        <?= Html::encode($item->name) ?>
                                        <?php if ($item->amount): ?>
                                            <span class="text-muted ms-2">(<?= Yii::$app->formatter->asCurrency($item->amount, 'PLN') ?>)</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

    <!-- Szybki dostęp do kategorii -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">📂 Kategorie</h5>
        </div>
        <div class="card-body">
            <div class="d-grid gap-2">
                <?= Html::a('💰 Rachunki', ['/task/index', 'category' => 'rachunki'], ['class' => 'btn btn-outline-warning']) ?>
                <?= Html::a('🛒 Zakupy', ['/task/index', 'category' => 'zakupy'], ['class' => 'btn btn-outline-info']) ?>
                <?= Html::a('🌱 Rośliny', ['/task/index', 'category' => 'rośliny'], ['class' => 'btn btn-outline-success']) ?>
                <?= Html::a('📊 Monitoring', ['/task/index', 'category' => 'monitoring'], ['class' => 'btn btn-outline-primary']) ?>
            </div>
        </div>
    </div>

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
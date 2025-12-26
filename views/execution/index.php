<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */
/** @var array $recentStats */
/** @var array $tasks */
/** @var int|null $selectedTaskId */
/** @var string|null $selectedStatus */

use yii\bootstrap5\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;

$this->title = 'Historia wykonań';
?>

<div class="execution-index">

    <h1><i class="fas fa-history me-2"></i> <?= Html::encode($this->title) ?></h1>

    <!-- Statystyki -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-primary"><?= $stats['all'] ?></h3>
                    <p class="mb-0 text-muted">Wszystkie</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success"><?= $stats['success'] ?></h3>
                    <p class="mb-0 text-muted">Sukces</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-danger"><?= $stats['failed'] ?></h3>
                    <p class="mb-0 text-muted">Błędy</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-warning"><?= $stats['running'] ?></h3>
                    <p class="mb-0 text-muted">W trakcie</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statystyki 7 dni -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Ostatnie 7 dni</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Wszystkie:</strong> <?= $recentStats['executions'] ?>
                </div>
                <div class="col-md-4">
                    <strong class="text-success">Sukces:</strong> <?= $recentStats['success'] ?>
                    <?php if ($recentStats['executions'] > 0): ?>
                        (<?= round($recentStats['success'] / $recentStats['executions'] * 100, 1) ?>%)
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <strong class="text-danger">Błędy:</strong> <?= $recentStats['failed'] ?>
                    <?php if ($recentStats['executions'] > 0): ?>
                        (<?= round($recentStats['failed'] / $recentStats['executions'] * 100, 1) ?>%)
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtry -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Zadanie</label>
                    <?= Html::dropDownList('taskId', $selectedTaskId, 
                        ArrayHelper::map($tasks, 'id', 'name'), 
                        ['class' => 'form-select', 'prompt' => 'Wszystkie zadania']
                    ) ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <?= Html::dropDownList('status', $selectedStatus, [
                        'success' => 'Sukces',
                        'failed' => 'Błąd',
                        'running' => 'W trakcie',
                        'skipped' => 'Pominięte',
                    ], ['class' => 'form-select', 'prompt' => 'Wszystkie']) ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label><br>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i> Filtruj
                    </button>
                    <?= Html::a('Wyczyść', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
                <div class="col-md-2 text-end">
                    <label class="form-label">&nbsp;</label><br>
                    <?= Html::a('<i class="fas fa-trash-alt"></i>', ['cleanup'], [
                        'class' => 'btn btn-outline-danger',
                        'title' => 'Wyczyść stare (30 dni)',
                        'data-method' => 'post',
                        'data-confirm' => 'Czy na pewno usunąć stare wykonania?',
                    ]) ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista wykonań -->
    <div class="card">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'layout' => "{items}\n{pager}",
                'columns' => [
                    [
                        'attribute' => 'started_at',
                        'format' => 'datetime',
                        'headerOptions' => ['style' => 'width: 180px'],
                    ],
                    [
                        'attribute' => 'task_id',
                        'label' => 'Zadanie',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->task ? Html::a(Html::encode($model->task->name), ['/task/view', 'id' => $model->task_id]) : '-';
                        },
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) {
                            $badges = [
                                'success' => '<span class="badge bg-success">✓ Sukces</span>',
                                'failed' => '<span class="badge bg-danger">✗ Błąd</span>',
                                'running' => '<span class="badge bg-warning">⏳ W trakcie</span>',
                                'skipped' => '<span class="badge bg-secondary">⊘ Pominięte</span>',
                            ];
                            return $badges[$model->status] ?? $model->status;
                        },
                        'headerOptions' => ['style' => 'width: 120px'],
                    ],
                    [
                        'attribute' => 'stage',
                        'headerOptions' => ['style' => 'width: 120px'],
                    ],
                    [
                        'attribute' => 'duration_ms',
                        'label' => 'Czas',
                        'value' => function($model) {
                            return $model->duration_ms ? round($model->duration_ms / 1000, 2) . 's' : '-';
                        },
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],
                    [
                        'attribute' => 'error_message',
                        'format' => 'raw',
                        'value' => function($model) {
                            if (!$model->error_message) return '-';
                            $short = mb_substr($model->error_message, 0, 50);
                            return '<span class="text-danger small" title="' . Html::encode($model->error_message) . '">' 
                                . Html::encode($short) 
                                . (mb_strlen($model->error_message) > 50 ? '...' : '')
                                . '</span>';
                        },
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="fas fa-eye"></i>', ['view', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-info',
                                    'title' => 'Szczegóły',
                                ]);
                            },
                            'delete' => function ($url, $model) {
                                return Html::a('<i class="fas fa-trash"></i>', ['delete', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'title' => 'Usuń',
                                    'data-method' => 'post',
                                    'data-confirm' => 'Czy na pewno usunąć to wykonanie?',
                                ]);
                            },
                        ],
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>
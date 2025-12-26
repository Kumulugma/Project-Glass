<?php

/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var yii\data\ActiveDataProvider $executionsProvider */
/** @var yii\data\ActiveDataProvider $historyProvider */

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Zadania', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('▶ Uruchom teraz', ['run', 'id' => $model->id], [
                'class' => 'btn btn-primary',
                'data-method' => 'post',
                'data-confirm' => 'Uruchomić zadanie teraz?'
            ]) ?>
            <?= Html::a('✏ Edytuj', ['update', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
            <?= Html::a('🗑 Usuń', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data-method' => 'post',
                'data-confirm' => 'Na pewno usunąć to zadanie?'
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">📋 Informacje podstawowe</h5>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'name',
                            [
                                'attribute' => 'category',
                                'format' => 'raw',
                                'value' => function($model) {
                                    if (!$model->category) return '-';
                                    
                                    $badges = [
                                        'rachunki' => 'warning',
                                        'zakupy' => 'info',
                                        'rośliny' => 'success',
                                        'monitoring' => 'secondary',
                                    ];
                                    
                                    $class = $badges[$model->category] ?? 'secondary';
                                    return '<span class="badge bg-' . $class . '">' . Html::encode($model->category) . '</span>';
                                },
                            ],
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function($model) {
                                    $badges = [
                                        'active' => 'success',
                                        'paused' => 'warning',
                                        'completed' => 'info',
                                        'archived' => 'secondary',
                                    ];
                                    
                                    $labels = [
                                        'active' => 'Aktywne',
                                        'paused' => 'Wstrzymane',
                                        'completed' => 'Wykonane',
                                        'archived' => 'Archiwum',
                                    ];
                                    
                                    $class = $badges[$model->status] ?? 'secondary';
                                    $label = $labels[$model->status] ?? $model->status;
                                    
                                    return '<span class="badge bg-' . $class . '">' . $label . '</span>';
                                },
                            ],
                            'parser_class',
                            'fetcher_class',
                            'schedule',
                            [
                                'attribute' => 'amount',
                                'format' => ['currency', 'PLN'],
                            ],
                            'currency',
                            [
                                'attribute' => 'due_date',
                                'format' => 'date',
                            ],
                            [
                                'attribute' => 'completed_at',
                                'format' => 'datetime',
                            ],
                            [
                                'attribute' => 'last_run_at',
                                'format' => 'datetime',
                            ],
                            [
                                'attribute' => 'next_run_at',
                                'format' => 'datetime',
                            ],
                            'cooldown_minutes',
                            [
                                'attribute' => 'last_notification_at',
                                'format' => 'datetime',
                            ],
                            [
                                'attribute' => 'created_at',
                                'format' => 'datetime',
                            ],
                            [
                                'attribute' => 'updated_at',
                                'format' => 'datetime',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>

            <?php if ($model->config): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">⚙️ Konfiguracja</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0"><code><?= Html::encode(json_encode($model->getConfigArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($model->last_state): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">💾 Ostatni stan</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0"><code><?= Html::encode(json_encode($model->getLastState(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <div class="col-md-4">
            
            <?php if ($model->notification_channels): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">🔔 Powiadomienia</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Kanały:</strong></p>
                    <pre class="mb-3 small"><code><?= Html::encode($model->notification_channels) ?></code></pre>
                    
                    <?php if ($model->notification_recipients): ?>
                        <p class="mb-2"><strong>Odbiorcy:</strong></p>
                        <pre class="mb-0 small"><code><?= Html::encode($model->notification_recipients) ?></code></pre>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">⚡ Szybkie akcje</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($model->status === 'active'): ?>
                            <?= Html::a('⏸ Wstrzymaj', ['pause', 'id' => $model->id], [
                                'class' => 'btn btn-warning',
                                'data-method' => 'post'
                            ]) ?>
                        <?php elseif ($model->status === 'paused'): ?>
                            <?= Html::a('▶ Wznów', ['resume', 'id' => $model->id], [
                                'class' => 'btn btn-success',
                                'data-method' => 'post'
                            ]) ?>
                        <?php endif; ?>
                        
                        <?php if ($model->status !== 'completed'): ?>
                            <?= Html::a('✓ Oznacz jako wykonane', ['complete', 'id' => $model->id], [
                                'class' => 'btn btn-info',
                                'data-method' => 'post'
                            ]) ?>
                        <?php else: ?>
                            <?= Html::a('↶ Cofnij wykonanie', ['uncomplete', 'id' => $model->id], [
                                'class' => 'btn btn-outline-info',
                                'data-method' => 'post'
                            ]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Historia wykonań -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">🔄 Historia wykonań</h5>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $executionsProvider,
                'tableOptions' => ['class' => 'table table-sm table-hover mb-0'],
                'layout' => "{items}\n{pager}",
                'columns' => [
                    [
                        'attribute' => 'started_at',
                        'format' => 'datetime',
                        'headerOptions' => ['style' => 'width: 180px'],
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) {
                            if ($model->status === 'success') {
                                return '<span class="badge bg-success">✓ Sukces</span>';
                            } elseif ($model->status === 'failed') {
                                return '<span class="badge bg-danger">✗ Błąd</span>';
                            } else {
                                return '<span class="badge bg-warning">⏳ ' . $model->status . '</span>';
                            }
                        },
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],
                    [
                        'attribute' => 'stage',
                        'headerOptions' => ['style' => 'width: 120px'],
                    ],
                    [
                        'attribute' => 'duration_ms',
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
                            return '<span class="text-danger small">' . Html::encode($model->error_message) . '</span>';
                        },
                    ],
                ],
            ]); ?>
        </div>
    </div>

    <!-- Historia zmian -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">📜 Historia zmian</h5>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $historyProvider,
                'tableOptions' => ['class' => 'table table-sm table-hover mb-0'],
                'layout' => "{items}\n{pager}",
                'columns' => [
                    [
                        'attribute' => 'created_at',
                        'format' => 'datetime',
                        'headerOptions' => ['style' => 'width: 180px'],
                    ],
                    [
                        'attribute' => 'action',
                        'value' => function($model) {
                            return $model->getDescription();
                        },
                    ],
                    [
                        'attribute' => 'user_ip',
                        'headerOptions' => ['style' => 'width: 150px'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>
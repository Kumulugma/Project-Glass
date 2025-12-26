<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */
/** @var string|null $selectedStatus */

use yii\bootstrap5\Html;
use yii\grid\GridView;
use yii\helpers\Url;

$this->title = 'Powiadomienia';
?>

<div class="notification-index">

    <h1><i class="fas fa-bell me-2"></i> <?= Html::encode($this->title) ?></h1>

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
                    <h3 class="mb-0 text-warning"><?= $stats['pending'] ?></h3>
                    <p class="mb-0 text-muted">Oczekujące</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success"><?= $stats['sent'] ?></h3>
                    <p class="mb-0 text-muted">Wysłane</p>
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
    </div>

    <!-- Filtry -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="btn-group" role="group">
                <?= Html::a('Wszystkie', ['index'], ['class' => 'btn ' . ($selectedStatus === null ? 'btn-primary' : 'btn-outline-primary')]) ?>
                <?= Html::a('Oczekujące', ['index', 'status' => 'pending'], ['class' => 'btn ' . ($selectedStatus === 'pending' ? 'btn-warning' : 'btn-outline-warning')]) ?>
                <?= Html::a('Wysłane', ['index', 'status' => 'sent'], ['class' => 'btn ' . ($selectedStatus === 'sent' ? 'btn-success' : 'btn-outline-success')]) ?>
                <?= Html::a('Błędy', ['index', 'status' => 'failed'], ['class' => 'btn ' . ($selectedStatus === 'failed' ? 'btn-danger' : 'btn-outline-danger')]) ?>
            </div>
            
            <div class="float-end">
                <?= Html::a('<i class="fas fa-trash-alt me-2"></i> Wyczyść stare (30 dni)', ['cleanup'], [
                    'class' => 'btn btn-outline-secondary',
                    'data-method' => 'post',
                    'data-confirm' => 'Czy na pewno usunąć stare powiadomienia?',
                ]) ?>
            </div>
        </div>
    </div>

    <!-- Lista powiadomień -->
    <div class="card">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'layout' => "{items}\n{pager}",
                'columns' => [
                    [
                        'attribute' => 'created_at',
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
                        'attribute' => 'channel',
                        'format' => 'raw',
                        'value' => function($model) {
                            $icons = [
                                'email' => '<i class="fas fa-envelope"></i>',
                                'sms' => '<i class="fas fa-sms"></i>',
                                'telegram' => '<i class="fab fa-telegram"></i>',
                                'webhook' => '<i class="fas fa-globe"></i>',
                            ];
                            $icon = $icons[$model->channel] ?? '<i class="fas fa-bell"></i>';
                            return $icon . ' ' . Html::encode($model->channel);
                        },
                        'headerOptions' => ['style' => 'width: 120px'],
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) {
                            $badges = [
                                'pending' => '<span class="badge bg-warning">⏳ Oczekujące</span>',
                                'sent' => '<span class="badge bg-success">✓ Wysłane</span>',
                                'failed' => '<span class="badge bg-danger">✗ Błąd</span>',
                            ];
                            return $badges[$model->status] ?? $model->status;
                        },
                        'headerOptions' => ['style' => 'width: 120px'],
                    ],
                    [
                        'attribute' => 'attempts',
                        'headerOptions' => ['style' => 'width: 80px'],
                    ],
                    [
                        'attribute' => 'sent_at',
                        'format' => 'datetime',
                        'value' => function($model) {
                            return $model->sent_at ? Yii::$app->formatter->asDatetime($model->sent_at) : '-';
                        },
                        'headerOptions' => ['style' => 'width: 180px'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {resend} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="fas fa-eye"></i>', ['view', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-info',
                                    'title' => 'Szczegóły',
                                ]);
                            },
                            'resend' => function ($url, $model) {
                                if ($model->status === 'failed') {
                                    return Html::a('<i class="fas fa-redo"></i>', ['resend', 'id' => $model->id], [
                                        'class' => 'btn btn-sm btn-outline-warning',
                                        'title' => 'Wyślij ponownie',
                                        'data-method' => 'post',
                                    ]);
                                }
                                return '';
                            },
                            'delete' => function ($url, $model) {
                                return Html::a('<i class="fas fa-trash"></i>', ['delete', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'title' => 'Usuń',
                                    'data-method' => 'post',
                                    'data-confirm' => 'Czy na pewno usunąć to powiadomienie?',
                                ]);
                            },
                        ],
                        'headerOptions' => ['style' => 'width: 140px'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>
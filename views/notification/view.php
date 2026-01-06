<?php

/** @var yii\web\View $this */
/** @var app\models\NotificationQueue $model */

use yii\bootstrap5\Html;
use yii\widgets\DetailView;

$this->title = 'Powiadomienie #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Powiadomienia', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="notification-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('<i class="fas fa-arrow-left me-2"></i> Powrót', ['index'], ['class' => 'btn btn-secondary']) ?>
        
        <?php if ($model->status === 'failed'): ?>
            <?= Html::a('<i class="fas fa-redo me-2"></i> Wyślij ponownie', ['resend', 'id' => $model->id], [
                'class' => 'btn btn-warning',
                'data-method' => 'post',
            ]) ?>
        <?php endif; ?>
        
        <?= Html::a('<i class="fas fa-trash me-2"></i> Usuń', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data-method' => 'post',
            'data-confirm' => 'Czy na pewno usunąć to powiadomienie?',
        ]) ?>
    </p>

    <div class="row">
        <div class="col-md-8">
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Szczegóły powiadomienia</h5>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            [
                                'attribute' => 'task_id',
                                'format' => 'raw',
                                'value' => $model->task ? Html::a(Html::encode($model->task->name), ['/task/view', 'id' => $model->task_id]) : '-',
                            ],
                            'channel',
                            'recipient',
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function($model) {
                                    $badges = [
                                        'pending' => '<span class="badge bg-warning">Oczekujące</span>',
                                        'sent' => '<span class="badge bg-success">Wysłane</span>',
                                        'failed' => '<span class="badge bg-danger">Błąd</span>',
                                    ];
                                    return $badges[$model->status] ?? $model->status;
                                },
                            ],
                            'attempts',
                            [
                                'attribute' => 'created_at',
                                'format' => 'datetime',
                            ],
                            [
                                'attribute' => 'sent_at',
                                'format' => 'raw',
                                'value' => $model->sent_at ? Yii::$app->formatter->asDatetime($model->sent_at) : '-',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>

            <?php if ($model->message): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Wiadomość</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0" style="white-space: pre-wrap;"><?= Html::encode($model->message) ?></pre>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($model->error_message): ?>
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Błąd</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-danger"><?= Html::encode($model->error_message) ?></p>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <div class="col-md-4">
            
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Status</h6>
                </div>
                <div class="card-body">
                    <?php if ($model->status === 'sent'): ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Powiadomienie wysłane pomyślnie
                        </div>
                    <?php elseif ($model->status === 'pending'): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Oczekuje na wysłanie
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Błąd wysyłki
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>
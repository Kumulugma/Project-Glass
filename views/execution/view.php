<?php

/** @var yii\web\View $this */
/** @var app\models\TaskExecution $model */

use yii\bootstrap5\Html;
use yii\widgets\DetailView;

$this->title = 'Wykonanie #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Historia wykonań', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="execution-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('<i class="fas fa-arrow-left me-2"></i> Powrót', ['index'], ['class' => 'btn btn-secondary']) ?>
        <?= Html::a('<i class="fas fa-trash me-2"></i> Usuń', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data-method' => 'post',
            'data-confirm' => 'Czy na pewno usunąć to wykonanie?',
        ]) ?>
    </p>

    <div class="row">
        <div class="col-md-8">
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Szczegóły wykonania</h5>
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
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function($model) {
                                    $badges = [
                                        'success' => '<span class="badge bg-success">Sukces</span>',
                                        'failed' => '<span class="badge bg-danger">Błąd</span>',
                                        'running' => '<span class="badge bg-warning">W trakcie</span>',
                                        'skipped' => '<span class="badge bg-secondary">Pominięte</span>',
                                    ];
                                    return $badges[$model->status] ?? $model->status;
                                },
                            ],
                            'stage',
                            [
                                'attribute' => 'started_at',
                                'format' => 'datetime',
                            ],
                            [
                                'attribute' => 'finished_at',
                                'format' => 'datetime',
                                'value' => $model->finished_at ? Yii::$app->formatter->asDatetime($model->finished_at) : '-',
                            ],
                            [
                                'attribute' => 'duration_ms',
                                'label' => 'Czas wykonania',
                                'value' => $model->duration_ms ? round($model->duration_ms / 1000, 2) . ' sekund' : '-',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>

            <?php if ($model->raw_data): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Surowe dane (fetch)</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0"><code><?= Html::encode(json_encode(json_decode($model->raw_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($model->parsed_data): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Przetworzone dane (parse)</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0"><code><?= Html::encode(json_encode(json_decode($model->parsed_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($model->evaluation_result): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Wynik ewaluacji</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0"><code><?= Html::encode(json_encode(json_decode($model->evaluation_result), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($model->error_message): ?>
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Komunikat błędu</h5>
                </div>
                <div class="card-body">
                    <p class="text-danger mb-0"><?= Html::encode($model->error_message) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($model->error_trace): ?>
            <div class="card border-danger">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Stack trace</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0 small"><code><?= Html::encode($model->error_trace) ?></code></pre>
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
                    <?php if ($model->status === 'success'): ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Wykonanie zakończone sukcesem
                        </div>
                    <?php elseif ($model->status === 'running'): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Wykonanie w trakcie
                        </div>
                    <?php elseif ($model->status === 'skipped'): ?>
                        <div class="alert alert-secondary mb-0">
                            <i class="fas fa-forward me-2"></i>
                            Wykonanie pominięte
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Błąd wykonania
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Etapy wykonania</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="<?= $model->stage === 'fetch' ? 'text-primary fw-bold' : 'text-muted' ?>">
                            <?= $model->stage === 'fetch' ? '▶' : '✓' ?> Fetch
                        </li>
                        <li class="<?= $model->stage === 'parse' ? 'text-primary fw-bold' : ($model->stage === 'fetch' ? 'text-muted' : 'text-muted') ?>">
                            <?= $model->stage === 'parse' ? '▶' : ($model->stage === 'fetch' ? '⊙' : '✓') ?> Parse
                        </li>
                        <li class="<?= $model->stage === 'evaluate' ? 'text-primary fw-bold' : ($model->stage === 'parse' || $model->stage === 'fetch' ? 'text-muted' : 'text-muted') ?>">
                            <?= $model->stage === 'evaluate' ? '▶' : ($model->stage === 'parse' || $model->stage === 'fetch' ? '⊙' : '✓') ?> Evaluate
                        </li>
                        <li class="<?= $model->stage === 'notify' ? 'text-primary fw-bold' : ($model->stage !== 'completed' ? 'text-muted' : 'text-muted') ?>">
                            <?= $model->stage === 'notify' ? '▶' : ($model->stage !== 'completed' ? '⊙' : '✓') ?> Notify
                        </li>
                        <li class="<?= $model->stage === 'completed' ? 'text-success fw-bold' : 'text-muted' ?>">
                            <?= $model->stage === 'completed' ? '✓' : '⊙' ?> Completed
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

</div>
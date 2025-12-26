<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $tasks */
/** @var array $fetchers */
/** @var int|null $selectedTaskId */
/** @var string|null $selectedFetcher */
/** @var string|null $selectedStatus */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\grid\ActionColumn;

$this->title = 'Wyniki Fetcherów';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="results-index">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="fas fa-chart-bar me-2"></i> Statystyki', ['stats'], ['class' => 'btn btn-info']) ?>
            <?= Html::a('<i class="fas fa-trash-alt me-2"></i> Wyczyść stare', ['cleanup'], [
                'class' => 'btn btn-outline-danger',
                'data-method' => 'post',
                'data-confirm' => 'Czy na pewno usunąć wyniki starsze niż 30 dni?',
            ]) ?>
        </div>
    </div>

    <!-- Filtry -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Task</label>
                    <?= Html::dropDownList('task_id', $selectedTaskId, \yii\helpers\ArrayHelper::map($tasks, 'id', 'name'), [
                        'class' => 'form-select',
                        'prompt' => '-- Wszystkie --',
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fetcher</label>
                    <?= Html::dropDownList('fetcher', $selectedFetcher, array_combine($fetchers, $fetchers), [
                        'class' => 'form-select',
                        'prompt' => '-- Wszystkie --',
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <?= Html::dropDownList('status', $selectedStatus, [
                        'success' => 'Sukces',
                        'failed' => 'Błąd',
                        'partial' => 'Częściowy',
                    ], [
                        'class' => 'form-select',
                        'prompt' => '-- Wszystkie --',
                    ]) ?>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <?= Html::submitButton('<i class="fas fa-filter me-2"></i> Filtruj', ['class' => 'btn btn-primary w-100']) ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista wyników -->
    <div class="card">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'layout' => "{items}\n{pager}",
                'columns' => [
                    [
                        'attribute' => 'id',
                        'headerOptions' => ['style' => 'width: 60px'],
                    ],
                    [
                        'attribute' => 'fetched_at',
                        'format' => 'datetime',
                        'headerOptions' => ['style' => 'width: 180px'],
                    ],
                    [
                        'attribute' => 'task_id',
                        'label' => 'Zadanie',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->task 
                                ? Html::a(Html::encode($model->task->name), ['/task/view', 'id' => $model->task_id])
                                : '-';
                        },
                    ],
                    [
                        'attribute' => 'fetcher_class',
                        'label' => 'Fetcher',
                        'format' => 'raw',
                        'value' => function($model) {
                            return '<span class="badge bg-info">' . Html::encode($model->fetcher_class) . '</span>';
                        },
                        'headerOptions' => ['style' => 'width: 200px'],
                    ],
                    [
                        'attribute' => 'rows_count',
                        'label' => 'Wiersze',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->rows_count !== null 
                                ? '<span class="badge bg-secondary">' . number_format($model->rows_count) . '</span>'
                                : '-';
                        },
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],
                    [
                        'attribute' => 'data_size',
                        'label' => 'Rozmiar',
                        'format' => 'raw',
                        'value' => function($model) {
                            if (!$model->data_size) return '-';
                            return '<span class="badge bg-secondary">' . Yii::$app->formatter->asShortSize($model->data_size) . '</span>';
                        },
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) {
                            $badges = [
                                'success' => 'success',
                                'failed' => 'danger',
                                'partial' => 'warning',
                            ];
                            $class = $badges[$model->status] ?? 'secondary';
                            $labels = [
                                'success' => 'Sukces',
                                'failed' => 'Błąd',
                                'partial' => 'Częściowy',
                            ];
                            $label = $labels[$model->status] ?? $model->status;
                            return '<span class="badge bg-' . $class . '">' . $label . '</span>';
                        },
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],
                    [
                        'class' => ActionColumn::class,
                        'template' => '{view} {export}',
                        'buttons' => [
                            'view' => function($url, $model) {
                                return Html::a('<i class="fas fa-eye"></i>', ['view', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-primary',
                                    'title' => 'Szczegóły',
                                ]);
                            },
                            'export' => function($url, $model) {
                                return Html::a('<i class="fas fa-download"></i>', ['export', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-secondary',
                                    'title' => 'Export JSON',
                                    'target' => '_blank',
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
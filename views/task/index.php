<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $category */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\grid\ActionColumn;

$this->title = 'Zadania' . ($category ? ' - ' . ucfirst($category) : '');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('➕ Nowe zadanie', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <!-- Filtry kategorii -->
    <div class="btn-group mb-3" role="group">
        <?= Html::a('Wszystkie', ['index'], ['class' => 'btn ' . (empty($category) ? 'btn-primary' : 'btn-outline-primary')]) ?>
        <?= Html::a('💰 Rachunki', ['index', 'category' => 'rachunki'], ['class' => 'btn ' . ($category === 'rachunki' ? 'btn-warning' : 'btn-outline-warning')]) ?>
        <?= Html::a('🛒 Zakupy', ['index', 'category' => 'zakupy'], ['class' => 'btn ' . ($category === 'zakupy' ? 'btn-info' : 'btn-outline-info')]) ?>
        <?= Html::a('🌱 Rośliny', ['index', 'category' => 'rośliny'], ['class' => 'btn ' . ($category === 'rośliny' ? 'btn-success' : 'btn-outline-success')]) ?>
        <?= Html::a('📊 Monitoring', ['index', 'category' => 'monitoring'], ['class' => 'btn ' . ($category === 'monitoring' ? 'btn-secondary' : 'btn-outline-secondary')]) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-hover'],
        'columns' => [
            [
                'attribute' => 'id',
                'headerOptions' => ['style' => 'width: 50px'],
            ],
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => function($model) {
                    return Html::a(Html::encode($model->name), ['view', 'id' => $model->id]);
                },
            ],
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
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'due_date',
                'format' => 'date',
                'value' => function($model) {
                    return $model->due_date;
                },
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'amount',
                'format' => ['currency', 'PLN'],
                'headerOptions' => ['style' => 'width: 120px'],
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
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'schedule',
                'value' => function($model) {
                    return $model->schedule === 'manual' ? 'Ręcznie' : $model->schedule;
                },
                'headerOptions' => ['style' => 'width: 150px'],
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{view} {run} {update} {delete}',
                'buttons' => [
                    'run' => function ($url, $model) {
                        return Html::a('▶', ['run', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-primary',
                            'title' => 'Uruchom',
                            'data-method' => 'post',
                            'data-confirm' => 'Uruchomić zadanie teraz?',
                        ]);
                    },
                    'view' => function ($url, $model) {
                        return Html::a('👁', ['view', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-info',
                            'title' => 'Podgląd',
                        ]);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('✏', ['update', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-warning',
                            'title' => 'Edytuj',
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('🗑', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Usuń',
                            'data-method' => 'post',
                            'data-confirm' => 'Na pewno usunąć to zadanie?',
                        ]);
                    },
                ],
                'headerOptions' => ['style' => 'width: 180px'],
            ],
        ],
    ]); ?>

</div>

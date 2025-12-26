<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\grid\ActionColumn;

$this->title = 'Zadania';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('➕ Nowe zadanie', ['create'], ['class' => 'btn btn-success btn-lg']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-hover'],
        'columns' => [
            [
                'attribute' => 'id',
                'headerOptions' => ['style' => 'width: 60px'],
            ],
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => function($model) {
                    return Html::a(Html::encode($model->name), ['view', 'id' => $model->id], [
                        'class' => 'fw-bold text-decoration-none'
                    ]);
                },
            ],
            [
                'label' => 'Komponenty',
                'format' => 'raw',
                'value' => function($model) {
                    $badges = [];
                    
                    // Parser
                    $badges[] = '<span class="badge bg-primary" title="Parser">' 
                        . Html::encode($model->parser_class) 
                        . '</span>';
                    
                    // Fetcher
                    if ($model->fetcher_class) {
                        $badges[] = '<span class="badge bg-info" title="Fetcher">' 
                            . Html::encode($model->fetcher_class) 
                            . '</span>';
                    }
                    
                    // Channels
                    $channels = json_decode($model->notification_channels, true) ?: [];
                    foreach ($channels as $channel) {
                        $badges[] = '<span class="badge bg-success" title="Channel">' 
                            . Html::encode($channel) 
                            . '</span>';
                    }
                    
                    return implode(' ', $badges);
                },
                'headerOptions' => ['style' => 'min-width: 300px'],
            ],
            [
                'attribute' => 'schedule',
                'format' => 'raw',
                'value' => function($model) {
                    if ($model->schedule === 'manual') {
                        return '<span class="badge bg-secondary">Ręcznie</span>';
                    }
                    return '<code class="small">' . Html::encode($model->schedule) . '</code>';
                },
                'headerOptions' => ['style' => 'width: 150px'],
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function($model) {
                    $badges = [
                        'active' => ['class' => 'success', 'icon' => 'check-circle', 'label' => 'Aktywne'],
                        'paused' => ['class' => 'warning', 'icon' => 'pause-circle', 'label' => 'Wstrzymane'],
                        'completed' => ['class' => 'info', 'icon' => 'check', 'label' => 'Wykonane'],
                        'archived' => ['class' => 'secondary', 'icon' => 'archive', 'label' => 'Archiwum'],
                    ];
                    $badge = $badges[$model->status] ?? ['class' => 'secondary', 'icon' => 'question', 'label' => $model->status];
                    
                    return '<span class="badge bg-' . $badge['class'] . '">'
                        . '<i class="fas fa-' . $badge['icon'] . ' me-1"></i>'
                        . $badge['label']
                        . '</span>';
                },
                'headerOptions' => ['style' => 'width: 130px'],
            ],
            [
                'attribute' => 'next_run_at',
                'format' => 'datetime',
                'value' => function($model) {
                    if ($model->schedule === 'manual') {
                        return null;
                    }
                    return $model->next_run_at;
                },
                'headerOptions' => ['style' => 'width: 180px'],
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{view} {run} {update} {delete}',
                'buttons' => [
                    'view' => function($url, $model) {
                        return Html::a('<i class="fas fa-eye"></i>', ['view', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-primary',
                            'title' => 'Szczegóły',
                        ]);
                    },
                    'run' => function($url, $model) {
                        if ($model->status !== 'active') {
                            return '';
                        }
                        return Html::a('<i class="fas fa-play"></i>', ['run', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-success',
                            'title' => 'Uruchom teraz',
                            'data-method' => 'post',
                            'data-confirm' => 'Czy na pewno uruchomić to zadanie?',
                        ]);
                    },
                    'update' => function($url, $model) {
                        return Html::a('<i class="fas fa-edit"></i>', ['update', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-warning',
                            'title' => 'Edytuj',
                        ]);
                    },
                    'delete' => function($url, $model) {
                        return Html::a('<i class="fas fa-trash"></i>', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-danger',
                            'title' => 'Usuń',
                            'data-method' => 'post',
                            'data-confirm' => 'Czy na pewno usunąć to zadanie?',
                        ]);
                    },
                ],
                'headerOptions' => ['style' => 'width: 180px'],
            ],
        ],
    ]); ?>

    <!-- Info box -->
    <div class="alert alert-info mt-4">
        <h6><i class="fas fa-info-circle me-2"></i> Informacje</h6>
        <ul class="mb-0">
            <li><strong>Parser</strong> - określa typ zadania i sposób przetwarzania danych</li>
            <li><strong>Fetcher</strong> - pobiera dane z zewnętrznego źródła (URL, baza danych, itp.)</li>
            <li><strong>Channel</strong> - kanał wysyłki powiadomień (email, SMS, push)</li>
            <li>Zadania z schedule = "manual" są wykonywane tylko ręcznie</li>
        </ul>
    </div>

</div>
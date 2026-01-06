<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $stats array */

$this->title = 'Historia transferów S3';
$this->params['breadcrumbs'][] = ['label' => 'System', 'url' => ['#']];
$this->params['breadcrumbs'][] = ['label' => 'Zarządzanie archiwami', 'url' => ['archive/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="s3-transfer-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <button id="run-upload-btn" class="btn btn-success">
                <i class="fas fa-cloud-upload-alt me-2"></i>Uruchom upload
            </button>
            <?= Html::a('<i class="fas fa-arrow-left me-2"></i>Powrót', ['archive/index'], [
                'class' => 'btn btn-secondary',
            ]) ?>
        </div>
    </div>

    <!-- Statystyki -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Wszystkie transfery</h5>
                    <h2 class="mb-0"><?= $stats['total'] ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Zakończone</h5>
                    <h2 class="mb-0"><?= $stats['completed'] ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Błędy</h5>
                    <h2 class="mb-0"><?= $stats['failed'] ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Przesłano</h5>
                    <h2 class="mb-0"><?= $stats['total_size_mb'] ?> MB</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    'id',
                    [
                        'attribute' => 'archive_type',
                        'format' => 'raw',
                        'value' => function($model) {
                            if ($model->archive_type === 'task_executions') {
                                return '<span class="badge bg-info">Task Executions</span>';
                            }
                            return '<span class="badge bg-success">Fetch Results</span>';
                        },
                    ],
                    'archive_date',
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) {
                            $statusColors = [
                                'pending' => 'secondary',
                                'uploading' => 'primary',
                                'completed' => 'success',
                                'failed' => 'danger',
                            ];
                            $color = $statusColors[$model->status] ?? 'secondary';
                            return '<span class="badge bg-' . $color . '">' . $model->status . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'file_size',
                        'label' => 'Rozmiar',
                        'value' => function($model) {
                            return $model->getFileSizeMb() . ' MB';
                        },
                    ],
                    [
                        'attribute' => 'started_at',
                        'format' => 'raw',
                        'value' => function($model) {
                            return date('Y-m-d H:i:s', $model->started_at);
                        },
                    ],
                    [
                        'attribute' => 'duration_ms',
                        'label' => 'Czas',
                        'value' => function($model) {
                            if (!$model->duration_ms) return '-';
                            return round($model->duration_ms / 1000, 2) . 's';
                        },
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{retry} {delete}',
                        'buttons' => [
                            'retry' => function($url, $model) {
                                if ($model->status === 'failed') {
                                    return Html::button('<i class="fas fa-redo"></i>', [
                                        'class' => 'btn btn-sm btn-warning retry-btn',
                                        'data-id' => $model->id,
                                        'title' => 'Ponów',
                                    ]);
                                }
                                return '';
                            },
                            'delete' => function($url, $model) {
                                return Html::button('<i class="fas fa-trash"></i>', [
                                    'class' => 'btn btn-sm btn-danger delete-btn',
                                    'data-id' => $model->id,
                                    'title' => 'Usuń z historii',
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<JS
// Uruchom upload
$('#run-upload-btn').on('click', function() {
    if (!confirm('Czy na pewno chcesz uruchomić upload wszystkich archiwów na S3?')) {
        return;
    }
    
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Przesyłam...');
    
    $.ajax({
        url: '/s3-transfer/run-upload',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('✓ Upload zakończony!\\n\\n' +
                      'Przesłano: ' + response.stats.uploaded + '\\n' +
                      'Pominięto: ' + response.stats.skipped + '\\n' +
                      'Błędy: ' + response.stats.failed);
                location.reload();
            } else {
                alert('✗ Błąd: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Wystąpił błąd podczas uploadu');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-2"></i>Uruchom upload');
        }
    });
});

// Ponów transfer
$('.retry-btn').on('click', function() {
    var btn = $(this);
    var id = btn.data('id');
    
    btn.prop('disabled', true);
    
    $.ajax({
        url: '/s3-transfer/retry?id=' + id,
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('✓ Transfer ponowiony');
                location.reload();
            } else {
                alert('✗ Błąd: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Wystąpił błąd');
        },
        complete: function() {
            btn.prop('disabled', false);
        }
    });
});

// Usuń transfer
$('.delete-btn').on('click', function() {
    if (!confirm('Czy na pewno chcesz usunąć ten wpis z historii?')) {
        return;
    }
    
    var btn = $(this);
    var id = btn.data('id');
    
    btn.prop('disabled', true);
    
    $.ajax({
        url: '/s3-transfer/delete?id=' + id,
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('✓ Wpis usunięty');
                location.reload();
            } else {
                alert('✗ Błąd: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Wystąpił błąd');
        },
        complete: function() {
            btn.prop('disabled', false);
        }
    });
});
JS
);
?>
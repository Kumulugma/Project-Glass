<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $type string */
/* @var $date string */
/* @var $source string */
/* @var $dataProvider yii\data\ArrayDataProvider */
/* @var $totalRecords int */

$typeLabel = $type === 'task_executions' ? 'Task Executions' : 'Fetch Results';

$this->title = "Archiwum: {$typeLabel} - {$date}";
$this->params['breadcrumbs'][] = ['label' => 'Zarządzanie archiwami', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="archive-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="fas fa-arrow-left me-2"></i>Powrót', ['index'], [
            'class' => 'btn btn-secondary',
        ]) ?>
    </div>

    <div class="alert alert-info">
        <strong>Źródło:</strong> <?= $source === 'local' ? 'Lokalny plik' : 'AWS S3' ?>
        <span class="ms-3"><strong>Całkowita liczba rekordów:</strong> <?= number_format($totalRecords) ?></span>
        <span class="ms-3"><strong>Data:</strong> <?= $date ?></span>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($type === 'task_executions'): ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        'id',
                        'task_id',
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'value' => function($data) {
                                $statusColors = [
                                    'running' => 'primary',
                                    'success' => 'success',
                                    'failed' => 'danger',
                                    'skipped' => 'secondary',
                                ];
                                $color = $statusColors[$data['status']] ?? 'secondary';
                                return '<span class="badge bg-' . $color . '">' . $data['status'] . '</span>';
                            },
                        ],
                        'stage',
                        [
                            'attribute' => 'started_at',
                            'format' => 'raw',
                            'value' => function($data) {
                                return date('Y-m-d H:i:s', $data['started_at']);
                            },
                        ],
                        [
                            'attribute' => 'duration_ms',
                            'label' => 'Czas (s)',
                            'value' => function($data) {
                                return $data['duration_ms'] ? round($data['duration_ms'] / 1000, 2) . 's' : '-';
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{details}',
                            'buttons' => [
                                'details' => function($url, $model) {
                                    return Html::button('<i class="fas fa-eye"></i>', [
                                        'class' => 'btn btn-sm btn-info view-details-btn',
                                        'data-details' => json_encode($model),
                                        'title' => 'Szczegóły',
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
            <?php else: ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        'id',
                        'task_id',
                        'execution_id',
                        [
                            'attribute' => 'fetcher_class',
                            'format' => 'raw',
                            'value' => function($data) {
                                $parts = explode('\\', $data['fetcher_class']);
                                return '<code>' . end($parts) . '</code>';
                            },
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'value' => function($data) {
                                $statusColors = [
                                    'success' => 'success',
                                    'failed' => 'danger',
                                    'partial' => 'warning',
                                ];
                                $color = $statusColors[$data['status']] ?? 'secondary';
                                return '<span class="badge bg-' . $color . '">' . $data['status'] . '</span>';
                            },
                        ],
                        [
                            'attribute' => 'data_size',
                            'label' => 'Rozmiar',
                            'value' => function($data) {
                                return $data['data_size'] ? round($data['data_size'] / 1024, 2) . ' KB' : '-';
                            },
                        ],
                        'rows_count',
                        [
                            'attribute' => 'fetched_at',
                            'format' => 'raw',
                            'value' => function($data) {
                                return date('Y-m-d H:i:s', $data['fetched_at']);
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{details}',
                            'buttons' => [
                                'details' => function($url, $model) {
                                    return Html::button('<i class="fas fa-eye"></i>', [
                                        'class' => 'btn btn-sm btn-info view-details-btn',
                                        'data-details' => json_encode($model),
                                        'title' => 'Szczegóły',
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal szczegółów -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Szczegóły rekordu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="details-content" style="max-height: 500px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<JS
$('.view-details-btn').on('click', function() {
    var details = $(this).data('details');
    $('#details-content').text(JSON.stringify(details, null, 2));
    var modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
});
JS
);
?>
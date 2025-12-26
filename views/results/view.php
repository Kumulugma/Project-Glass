<?php

/** @var yii\web\View $this */
/** @var app\models\FetchResult $model */
/** @var array|null $parsedData */
/** @var bool $isJson */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\DetailView;

$this->title = 'Wynik Fetch #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Wyniki', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->id;
?>

<div class="results-view">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="fas fa-download me-2"></i> Export JSON', ['export', 'id' => $model->id], [
                'class' => 'btn btn-secondary',
                'target' => '_blank',
            ]) ?>
            <?= Html::a('<i class="fas fa-arrow-left me-2"></i> Powrót', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="row">
        <!-- Metadane -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Metadane</h5>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            [
                                'attribute' => 'task_id',
                                'format' => 'raw',
                                'value' => $model->task 
                                    ? Html::a(Html::encode($model->task->name), ['/task/view', 'id' => $model->task_id])
                                    : '-',
                            ],
                            [
                                'attribute' => 'execution_id',
                                'format' => 'raw',
                                'value' => $model->execution_id 
                                    ? Html::a('#' . $model->execution_id, ['/task/execution', 'id' => $model->execution_id])
                                    : '-',
                            ],
                            'fetcher_class:text:Fetcher',
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
                                    return '<span class="badge bg-' . $class . '">' . ucfirst($model->status) . '</span>';
                                },
                            ],
                            'rows_count:integer:Liczba wierszy',
                            [
                                'attribute' => 'data_size',
                                'format' => 'shortSize',
                                'label' => 'Rozmiar danych',
                            ],
                            'fetched_at:datetime:Pobrano',
                        ],
                    ]) ?>
                </div>
            </div>

            <!-- Source Info -->
            <?php if ($model->source_info): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Źródło</h6>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0 small"><code><?= Html::encode(Json::encode($model->getSourceInfoArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($model->error_message): ?>
                <div class="card border-danger mb-3">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Błąd</h6>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0 small text-danger"><?= Html::encode($model->error_message) ?></pre>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Raw Data -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dane</h5>
                    <?php if ($isJson): ?>
                        <span class="badge bg-success">JSON</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">TEXT</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!$model->raw_data): ?>
                        <p class="text-muted">Brak danych.</p>
                    <?php elseif ($isJson && $parsedData !== null): ?>
                        <!-- JSON z syntax highlighting -->
                        <div style="max-height: 600px; overflow-y: auto;">
                            <pre class="mb-0"><code class="language-json"><?= Html::encode(Json::encode($parsedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></code></pre>
                        </div>
                        
                        <?php if (is_array($parsedData) && !empty($parsedData)): ?>
                            <div class="mt-3">
                                <p class="small mb-2"><strong>Podgląd struktury:</strong></p>
                                <ul class="small mb-0">
                                    <?php if (isset($parsedData['data']) && is_array($parsedData['data'])): ?>
                                        <li>Liczba rekordów: <?= count($parsedData['data']) ?></li>
                                        <?php if (!empty($parsedData['data'])): ?>
                                            <li>Przykładowy rekord zawiera: <?= implode(', ', array_keys($parsedData['data'][0] ?? [])) ?></li>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <li>Kluczy głównych: <?= count($parsedData) ?></li>
                                        <li>Klucze: <?= implode(', ', array_keys($parsedData)) ?></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Plain text -->
                        <div style="max-height: 600px; overflow-y: auto;">
                            <pre class="mb-0"><code><?= Html::encode($model->raw_data) ?></code></pre>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
// Dodaj syntax highlighting dla JSON jeśli dostępne
if ($isJson) {
    $this->registerCss('
        pre code.language-json {
            display: block;
            padding: 1em;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 0.85em;
            line-height: 1.5;
        }
    ');
}
?>
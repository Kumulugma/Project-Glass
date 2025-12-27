<?php

/** @var yii\web\View $this */
/** @var array $fetcherStats */
/** @var array $taskStats */
/** @var int $totalFetches */
/** @var int $successCount */
/** @var int $failedCount */
/** @var float $successRate */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Statystyki Fetcherów';
$this->params['breadcrumbs'][] = ['label' => 'Wyniki', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="results-stats">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chart-bar me-2"></i> <?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="fas fa-arrow-left me-2"></i> Powrót', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <!-- Ogólne statystyki -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-primary"><?= $totalFetches ?></h3>
                    <p class="mb-0 text-muted">Wszystkie fetchowania</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success"><?= $successCount ?></h3>
                    <p class="mb-0 text-muted">Sukces</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-danger"><?= $failedCount ?></h3>
                    <p class="mb-0 text-muted">Błędy</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-info"><?= $successRate ?>%</h3>
                    <p class="mb-0 text-muted">Wskaźnik sukcesu</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statystyki per Fetcher -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-download me-2"></i> Statystyki wg Fetchera</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fetcher</th>
                            <th style="width: 120px;">Łącznie</th>
                            <th style="width: 120px;">Sukces</th>
                            <th style="width: 120px;">Błędy</th>
                            <th style="width: 150px;">Średni rozmiar</th>
                            <th style="width: 120px;">Sukces %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($fetcherStats)): ?>
                            <?php foreach ($fetcherStats as $stat): ?>
                                <?php
                                $successRate = $stat['count'] > 0 
                                    ? round(($stat['success_count'] / $stat['count']) * 100, 1) 
                                    : 0;
                                $avgSize = $stat['avg_size'] ? round($stat['avg_size'] / 1024, 2) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-info"><?= Html::encode($stat['fetcher_class']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <strong><?= $stat['count'] ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-success"><?= $stat['success_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-danger"><?= $stat['failed_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?= $avgSize > 0 ? $avgSize . ' KB' : '-' ?>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?= $successRate ?>%"
                                                 aria-valuenow="<?= $successRate ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?= $successRate ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Brak danych</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Statystyki per Task -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i> Statystyki wg Zadania</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Zadanie</th>
                            <th style="width: 150px;">Liczba fetchów</th>
                            <th style="width: 200px;">Ostatnie pobieranie</th>
                            <th style="width: 100px;">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($taskStats)): ?>
                            <?php foreach ($taskStats as $stat): ?>
                                <tr>
                                    <td>
                                        <?php if (isset($stat['task'])): ?>
                                            <?= Html::a(
                                                Html::encode($stat['task']['name']), 
                                                ['/task/view', 'id' => $stat['task_id']],
                                                ['class' => 'text-decoration-none']
                                            ) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Usunięte zadanie (ID: <?= $stat['task_id'] ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $stat['count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?= date('Y-m-d H:i:s', $stat['last_fetch']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= Html::a(
                                            '<i class="fas fa-list"></i> Zobacz', 
                                            ['index', 'task_id' => $stat['task_id']], 
                                            ['class' => 'btn btn-sm btn-outline-primary']
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Brak danych</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Wykres sukcesu (jeśli są dane) -->
    <?php if ($totalFetches > 0): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Proporcja sukces/błędy</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="statusChart" style="max-height: 300px;"></canvas>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <h2 class="display-4 mb-3"><?= $successRate ?>%</h2>
                        <p class="text-muted mb-0">Średni wskaźnik sukcesu</p>
                        <hr class="my-3">
                        <div class="d-flex justify-content-center gap-4">
                            <div>
                                <div class="text-success h4"><?= $successCount ?></div>
                                <small class="text-muted">Sukces</small>
                            </div>
                            <div>
                                <div class="text-danger h4"><?= $failedCount ?></div>
                                <small class="text-muted">Błędy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Chart.js
    $this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', ['position' => \yii\web\View::POS_HEAD]);

    $this->registerJs(<<<JS
const ctx = document.getElementById('statusChart');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Sukces', 'Błędy'],
        datasets: [{
            data: [{$successCount}, {$failedCount}],
            backgroundColor: ['#10b981', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 14
                    }
                }
            }
        }
    }
});
JS
    );
    ?>
    <?php endif; ?>

</div>
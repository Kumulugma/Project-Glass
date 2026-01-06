<?php

/** @var yii\web\View $this */
/** @var array $taskStats */
/** @var array $tasksByCategory */
/** @var int $lastMonthExecutions */
/** @var int $successfulExecutions */
/** @var int $failedExecutions */
/** @var int $lastMonthNotifications */
/** @var int $sentNotifications */
/** @var array $executionsByDay */
/** @var float $billsThisMonth */
/** @var float $billsPaid */
/** @var array $topTasks */
/** @var array $userActivity */

use yii\helpers\Html;

$this->title = 'Statystyki - ' . Yii::$app->name;
?>

<div class="stats-index">
    
    <h1><i class="fas fa-chart-bar me-2"></i> Statystyki systemu</h1>
    
    <!-- Główne statystyki -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Wszystkie zadania</h6>
                            <h2 class="mb-0"><?= $taskStats['total'] ?></h2>
                        </div>
                        <i class="fas fa-tasks fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Aktywne</h6>
                            <h2 class="mb-0"><?= $taskStats['active'] ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Wykonane</h6>
                            <h2 class="mb-0"><?= $taskStats['completed'] ?></h2>
                        </div>
                        <i class="fas fa-flag-checkered fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Wstrzymane</h6>
                            <h2 class="mb-0"><?= $taskStats['paused'] ?></h2>
                        </div>
                        <i class="fas fa-pause-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Lewa kolumna -->
        <div class="col-lg-8">
            
            <!-- Wykonania w czasie -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> Wykonania zadań (ostatnie 30 dni)</h5>
                </div>
                <div class="card-body">
                    <canvas id="executionsChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <!-- Najczęściej uruchamiane taski -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-fire me-2"></i> Najczęściej uruchamiane zadania (30 dni)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($topTasks)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nazwa zadania</th>
                                        <th>Kategoria</th>
                                        <th class="text-end">Wykonań</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topTasks as $task): ?>
                                        <tr>
                                            <td><?= Html::encode($task['task_name']) ?></td>
                                            <td>
                                                <?php if ($task['category']): ?>
                                                    <span class="badge bg-secondary"><?= Html::encode($task['category']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <strong><?= $task['execution_count'] ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Brak danych</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Aktywność użytkowników -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-clock me-2"></i> Aktywność użytkowników (30 dni)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($userActivity)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Akcja</th>
                                        <th class="text-end">Liczba wystąpień</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userActivity as $activity): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $labels = [
                                                    'login' => '🔓 Logowanie',
                                                    'logout' => '🔒 Wylogowanie',
                                                    'create_task' => '➕ Utworzenie zadania',
                                                    'update_task' => '✏️ Edycja zadania',
                                                    'delete_task' => '🗑️ Usunięcie zadania',
                                                    'run_task' => '▶️ Uruchomienie zadania',
                                                ];
                                                echo $labels[$activity['action']] ?? Html::encode($activity['action']);
                                                ?>
                                            </td>
                                            <td class="text-end">
                                                <strong><?= $activity['count'] ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Brak danych o aktywności</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Prawa kolumna -->
        <div class="col-lg-4">
            
            <!-- Wykonania w ostatnim miesiącu -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-play-circle me-2"></i> Wykonania (30 dni)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Wszystkie</span>
                            <strong><?= $lastMonthExecutions ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fas fa-check text-success me-1"></i> Sukces</span>
                            <strong><?= $successfulExecutions ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: <?= $lastMonthExecutions > 0 ? ($successfulExecutions / $lastMonthExecutions * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fas fa-times text-danger me-1"></i> Błędy</span>
                            <strong><?= $failedExecutions ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-danger" style="width: <?= $lastMonthExecutions > 0 ? ($failedExecutions / $lastMonthExecutions * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Powiadomienia -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Powiadomienia (30 dni)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Wszystkie</span>
                            <strong><?= $lastMonthNotifications ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fas fa-check text-success me-1"></i> Wysłane</span>
                            <strong><?= $sentNotifications ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: <?= $lastMonthNotifications > 0 ? ($sentNotifications / $lastMonthNotifications * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rachunki w tym miesiącu -->
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Rachunki (<?= date('m/Y') ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Wszystkie</span>
                            <strong><?= Yii::$app->formatter->asCurrency($billsThisMonth, 'PLN') ?></strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: 100%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fas fa-check text-success me-1"></i> Opłacone</span>
                            <strong><?= Yii::$app->formatter->asCurrency($billsPaid, 'PLN') ?></strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?= $billsThisMonth > 0 ? ($billsPaid / $billsThisMonth * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if ($billsThisMonth > $billsPaid): ?>
                        <div class="alert alert-warning mt-3 mb-0 small">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Do zapłaty: <strong><?= Yii::$app->formatter->asCurrency($billsThisMonth - $billsPaid, 'PLN') ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            

        </div>
    </div>

</div>

<?php
// Przygotuj dane dla wykresu wykonań
$executionDates = json_encode(array_column($executionsByDay, 'label'));
$executionCounts = json_encode(array_column($executionsByDay, 'count'));

// Przygotuj dane dla wykresu kategorii
$categoryNames = json_encode(array_column($tasksByCategory, 'category'));
$categoryCounts = json_encode(array_column($tasksByCategory, 'count'));

$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', ['position' => \yii\web\View::POS_HEAD]);

$this->registerJs(<<<JS
// Wykres wykonań
const executionsCtx = document.getElementById('executionsChart');
new Chart(executionsCtx, {
    type: 'line',
    data: {
        labels: $executionDates,
        datasets: [{
            label: 'Liczba wykonań',
            data: $executionCounts,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});

// Wykres kategorii (pie chart)
const categoryCtx = document.getElementById('categoryChart');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: $categoryNames,
        datasets: [{
            data: $categoryCounts,
            backgroundColor: [
                '#f59e0b',
                '#06b6d4',
                '#10b981',
                '#8b5cf6',
                '#f43f5e',
            ],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
JS
);
?>
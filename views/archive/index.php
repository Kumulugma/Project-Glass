<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $stats array */
/* @var $executionArchives array */
/* @var $fetchResultArchives array */
/* @var $s3Archives array */
/* @var $s3Stats array */
/* @var $s3Enabled bool */

$this->title = 'Zarządzanie archiwami';
$this->params['breadcrumbs'][] = ['label' => 'System', 'url' => ['#']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="archive-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <button id="run-archive-btn" class="btn btn-primary">
                <i class="fas fa-archive me-2"></i>Uruchom archiwizację
            </button>
            <?= Html::a('<i class="fas fa-cog me-2"></i>Konfiguracja S3', ['s3-config/index'], [
                'class' => 'btn btn-secondary',
            ]) ?>
        </div>
    </div>

    <!-- Statystyki -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Lokalne archiwa</h5>
                    <h2 class="mb-0"><?= $stats['total_archives'] ?></h2>
                    <small><?= $stats['total_size_mb'] ?> MB</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Task Executions</h5>
                    <h2 class="mb-0"><?= $stats['execution_archives_count'] ?></h2>
                    <small>archiwów</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Fetch Results</h5>
                    <h2 class="mb-0"><?= $stats['fetch_result_archives_count'] ?></h2>
                    <small>archiwów</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">S3 Backup</h5>
                    <h2 class="mb-0"><?= $s3Stats['total'] ?></h2>
                    <small><?= $s3Stats['size_mb'] ?> MB na S3</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#executions">
                <i class="fas fa-history me-2"></i>Task Executions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#fetch-results">
                <i class="fas fa-database me-2"></i>Fetch Results
            </a>
        </li>
        <?php if ($s3Enabled): ?>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#s3">
                <i class="fab fa-aws me-2"></i>Archiwa na S3
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">
        <!-- Task Executions -->
        <div id="executions" class="tab-pane fade show active">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lokalne archiwa Task Executions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($executionArchives)): ?>
                        <p class="text-muted">Brak archiwów</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Rozmiar</th>
                                        <th>Plik</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($executionArchives as $archive): ?>
                                    <tr>
                                        <td><?= $archive['date'] ?></td>
                                        <td><?= $archive['size_mb'] ?> MB</td>
                                        <td><code><?= Html::encode($archive['filename']) ?></code></td>
                                        <td>
                                            <?= Html::a('<i class="fas fa-eye"></i>', [
                                                'archive/view',
                                                'type' => 'task_executions',
                                                'date' => $archive['date']
                                            ], [
                                                'class' => 'btn btn-sm btn-info',
                                                'title' => 'Podgląd',
                                            ]) ?>
                                            
                                            <?= Html::a('<i class="fas fa-download"></i>', [
                                                'archive/download',
                                                'type' => 'task_executions',
                                                'date' => $archive['date']
                                            ], [
                                                'class' => 'btn btn-sm btn-success',
                                                'title' => 'Pobierz',
                                            ]) ?>
                                            
                                            <button class="btn btn-sm btn-danger delete-archive-btn" 
                                                    data-type="task_executions" 
                                                    data-date="<?= $archive['date'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Fetch Results -->
        <div id="fetch-results" class="tab-pane fade">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lokalne archiwa Fetch Results</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($fetchResultArchives)): ?>
                        <p class="text-muted">Brak archiwów</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Rozmiar</th>
                                        <th>Plik</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fetchResultArchives as $archive): ?>
                                    <tr>
                                        <td><?= $archive['date'] ?></td>
                                        <td><?= $archive['size_mb'] ?> MB</td>
                                        <td><code><?= Html::encode($archive['filename']) ?></code></td>
                                        <td>
                                            <?= Html::a('<i class="fas fa-eye"></i>', [
                                                'archive/view',
                                                'type' => 'fetch_results',
                                                'date' => $archive['date']
                                            ], [
                                                'class' => 'btn btn-sm btn-info',
                                                'title' => 'Podgląd',
                                            ]) ?>
                                            
                                            <?= Html::a('<i class="fas fa-download"></i>', [
                                                'archive/download',
                                                'type' => 'fetch_results',
                                                'date' => $archive['date']
                                            ], [
                                                'class' => 'btn btn-sm btn-success',
                                                'title' => 'Pobierz',
                                            ]) ?>
                                            
                                            <button class="btn btn-sm btn-danger delete-archive-btn" 
                                                    data-type="fetch_results" 
                                                    data-date="<?= $archive['date'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- S3 Archives -->
        <?php if ($s3Enabled): ?>
        <div id="s3" class="tab-pane fade">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Archiwa przechowywane na AWS S3</h5>
                    <?= Html::a('<i class="fas fa-history me-2"></i>Historia transferów', ['s3-transfer/index'], [
                        'class' => 'btn btn-sm btn-secondary',
                    ]) ?>
                </div>
                <div class="card-body">
                    <?php if (empty($s3Archives)): ?>
                        <p class="text-muted">Brak archiwów na S3</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Typ</th>
                                        <th>Data</th>
                                        <th>Rozmiar</th>
                                        <th>Ostatnia modyfikacja</th>
                                        <th>Klucz S3</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($s3Archives as $archive): ?>
                                    <tr>
                                        <td>
                                            <?php if ($archive['type'] === 'task_executions'): ?>
                                                <span class="badge bg-info">Task Executions</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Fetch Results</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $archive['date'] ?></td>
                                        <td><?= $archive['size_mb'] ?> MB</td>
                                        <td><?= $archive['last_modified'] ?></td>
                                        <td><small><code><?= Html::encode($archive['key']) ?></code></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$this->registerJs(<<<JS
// Uruchom archiwizację
$('#run-archive-btn').on('click', function() {
    if (!confirm('Czy na pewno chcesz uruchomić archiwizację? To może potrwać kilka minut.')) {
        return;
    }
    
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Archiwizuję...');
    
    $.ajax({
        url: '/archive/run-archive',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('✓ Archiwizacja zakończona pomyślnie!\\n\\n' +
                      'Task Executions: ' + response.stats.task_executions.archived + ' zarchiwizowanych\\n' +
                      'Fetch Results: ' + response.stats.fetch_results.archived + ' zarchiwizowanych');
                location.reload();
            } else {
                alert('✗ Błąd: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Wystąpił błąd podczas archiwizacji');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-archive me-2"></i>Uruchom archiwizację');
        }
    });
});

// Usuń archiwum
$('.delete-archive-btn').on('click', function() {
    if (!confirm('Czy na pewno chcesz usunąć to archiwum?')) {
        return;
    }
    
    var btn = $(this);
    var type = btn.data('type');
    var date = btn.data('date');
    
    btn.prop('disabled', true);
    
    $.ajax({
        url: '/archive/delete-local',
        method: 'POST',
        dataType: 'json',
        data: { type: type, date: date },
        success: function(response) {
            if (response.success) {
                alert('✓ Archiwum zostało usunięte');
                location.reload();
            } else {
                alert('✗ Błąd: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Wystąpił błąd podczas usuwania');
        },
        complete: function() {
            btn.prop('disabled', false);
        }
    });
});
JS
);
?>
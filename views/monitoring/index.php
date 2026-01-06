<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $settings array */
/* @var $stats array */

$this->title = 'Konfiguracja Monitoringu';
$this->params['breadcrumbs'][] = ['label' => 'System', 'url' => ['#']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="monitoring-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chart-line me-2"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="fas fa-plug me-1"></i> Test połączenia', 
                ['test-connection'], 
                ['class' => 'btn btn-info']) ?>
        </div>
    </div>

    <!-- Aktualne statystyki -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Aktualne statystyki</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-box">
                        <h3 class="text-primary"><?= number_format($stats['total_executions']) ?></h3>
                        <p class="text-muted mb-0">Wykonania tasków</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h3 class="text-success"><?= number_format($stats['total_notifications']) ?></h3>
                        <p class="text-muted mb-0">Powiadomienia</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h6 class="text-muted"><?= $stats['last_execution_date'] ?? 'Brak' ?></h6>
                        <p class="text-muted mb-0">Ostatnie wykonanie</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h6 class="text-muted"><?= $stats['last_sent_at'] ?? 'Nigdy' ?></h6>
                        <p class="text-muted mb-0">Ostatnie wysłanie</p>
                    </div>
                </div>
            </div>
            
            <?php if ($stats['last_send_status']): ?>
            <div class="alert <?= strpos($stats['last_send_status'], 'error') !== false ? 'alert-danger' : 'alert-success' ?> mt-3 mb-0">
                <strong>Status ostatniego wysłania:</strong> <?= Html::encode($stats['last_send_status']) ?>
            </div>
            <?php endif; ?>
            
            <?php if (Yii::$app->user->identity->isAdmin): ?>
            <div class="mt-3">
                <?= Html::a('<i class="fas fa-redo me-1"></i> Resetuj liczniki', 
                    ['reset-counters'], 
                    [
                        'class' => 'btn btn-sm btn-outline-danger',
                        'data' => [
                            'confirm' => 'Czy na pewno chcesz zresetować liczniki? Tej operacji nie można cofnąć.',
                            'method' => 'post',
                        ],
                    ]) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formularz konfiguracji -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i> Konfiguracja API</h5>
        </div>
        <div class="card-body">
            <?php $form = \yii\widgets\ActiveForm::begin([
                'id' => 'monitoring-form',
                'options' => ['class' => 'form-horizontal'],
            ]); ?>

            <!-- Włącz/wyłącz monitoring -->
            <div class="mb-3">
                <label class="form-label">Status monitoringu</label>
                <div class="form-check form-switch">
                    <?= Html::checkbox('monitoring_enabled', $settings['monitoring_enabled'], [
                        'class' => 'form-check-input',
                        'id' => 'monitoring-enabled',
                    ]) ?>
                    <label class="form-check-label" for="monitoring-enabled">
                        <?= $settings['monitoring_enabled'] ? 
                            '<span class="badge bg-success">Włączony</span>' : 
                            '<span class="badge bg-secondary">Wyłączony</span>' ?>
                    </label>
                </div>
                <small class="form-text text-muted">
                    Gdy wyłączone, statystyki nie będą wysyłane do zewnętrznego API
                </small>
            </div>

            <!-- URL API -->
            <div class="mb-3">
                <label class="form-label">URL Endpoint API</label>
                <?= Html::input('text', 'monitoring_api_url', $settings['monitoring_api_url'], [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com/api/stats',
                ]) ?>
                <small class="form-text text-muted">
                    Pełny adres URL endpoint API, do którego będą wysyłane statystyki
                </small>
            </div>

            <!-- Token API -->
            <div class="mb-3">
                <label class="form-label">Token autoryzacyjny</label>
                <div class="input-group">
                    <?= Html::input('password', 'monitoring_api_token', $settings['monitoring_api_token'], [
                        'class' => 'form-control',
                        'id' => 'api-token',
                        'placeholder' => 'Wprowadź token API',
                    ]) ?>
                    <button class="btn btn-outline-secondary" type="button" id="toggle-token">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="form-text text-muted">
                    Bearer token używany do autoryzacji w API
                </small>
            </div>

            <!-- Interwał -->
            <div class="mb-3">
                <label class="form-label">Interwał wysyłania (minuty)</label>
                <?= Html::input('number', 'monitoring_interval', $settings['monitoring_interval'], [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 60,
                ]) ?>
                <small class="form-text text-muted">
                    Jak często (w minutach) statystyki powinny być wysyłane. Zalecaną wartością jest 10 minut.
                    <br>
                    <strong>Pamiętaj:</strong> Musisz skonfigurować cron: 
                    <code>*/<?= $settings['monitoring_interval'] ?> * * * * php /path/to/yii stats/send</code>
                </small>
            </div>

            <!-- Przyciski -->
            <div class="form-group">
                <?= Html::submitButton('<i class="fas fa-save me-1"></i> Zapisz ustawienia', [
                    'class' => 'btn btn-primary',
                ]) ?>
                <?= Html::a('Anuluj', ['index'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php \yii\widgets\ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Informacje o cronie -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Konfiguracja Cron</h5>
        </div>
        <div class="card-body">
            <p>Aby automatycznie wysyłać statystyki co <?= $settings['monitoring_interval'] ?> minut, dodaj do crona:</p>
            <pre class="bg-dark text-white p-3 rounded"><code>*/<? = $settings['monitoring_interval'] ?> * * * * /usr/bin/php <?= Yii::getAlias('@app') ?>/yii stats/send >> /var/log/stats-sender.log 2>&1</code></pre>
            
            <p class="mb-0 mt-3">
                <strong>Lub wykonaj ręcznie:</strong><br>
                <code>php <?= Yii::getAlias('@app') ?>/yii stats/send</code>
            </p>
        </div>
    </div>
</div>

<style>
.stat-box {
    padding: 15px;
    border-left: 3px solid #007bff;
    background: #f8f9fa;
}
</style>

<script>
// Toggle token visibility
document.getElementById('toggle-token').addEventListener('click', function() {
    var input = document.getElementById('api-token');
    var icon = this.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>
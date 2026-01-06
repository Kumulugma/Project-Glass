<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $settings array */

$this->title = 'Konfiguracja AWS S3';
$this->params['breadcrumbs'][] = ['label' => 'System', 'url' => ['#']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="s3-config-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <button id="test-connection-btn" class="btn btn-info">
            <i class="fas fa-plug me-2"></i>Testuj połączenie
        </button>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Informacje:</strong> Skonfiguruj połączenie z AWS S3 do automatycznego przesyłania archiwów danych.
        Cotygodniowo (domyślnie w niedzielę) stare archiwa będą przesyłane na S3, a następnie usuwane lokalnie aby zwolnić miejsce.
    </div>

    <div class="card">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['id' => 's3-config-form']); ?>

            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Podstawowe ustawienia</h5>

                    <div class="form-group">
                        <label for="s3_enabled">
                            <input type="checkbox" id="s3_enabled" name="S3Config[s3_enabled]" value="1" 
                                   <?= $settings['s3_enabled'] ? 'checked' : '' ?>>
                            Włącz integrację z S3
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="s3_region">Region AWS</label>
                        <select id="s3_region" name="S3Config[s3_region]" class="form-control">
                            <option value="eu-north-1" <?= $settings['s3_region'] === 'eu-north-1' ? 'selected' : '' ?>>EU (Stockholm) - eu-north-1</option>
                            <option value="eu-central-1" <?= $settings['s3_region'] === 'eu-central-1' ? 'selected' : '' ?>>EU (Frankfurt) - eu-central-1</option>
                            <option value="eu-west-1" <?= $settings['s3_region'] === 'eu-west-1' ? 'selected' : '' ?>>EU (Ireland) - eu-west-1</option>
                            <option value="eu-west-2" <?= $settings['s3_region'] === 'eu-west-2' ? 'selected' : '' ?>>EU (London) - eu-west-2</option>
                            <option value="eu-west-3" <?= $settings['s3_region'] === 'eu-west-3' ? 'selected' : '' ?>>EU (Paris) - eu-west-3</option>
                            <option value="us-east-1" <?= $settings['s3_region'] === 'us-east-1' ? 'selected' : '' ?>>US East (N. Virginia) - us-east-1</option>
                            <option value="us-west-2" <?= $settings['s3_region'] === 'us-west-2' ? 'selected' : '' ?>>US West (Oregon) - us-west-2</option>
                            <option value="ap-southeast-1" <?= $settings['s3_region'] === 'ap-southeast-1' ? 'selected' : '' ?>>Asia Pacific (Singapore) - ap-southeast-1</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="s3_bucket">Nazwa bucketu</label>
                        <input type="text" id="s3_bucket" name="S3Config[s3_bucket]" 
                               class="form-control" value="<?= Html::encode($settings['s3_bucket']) ?>" 
                               placeholder="my-app-archives">
                    </div>

                    <div class="form-group">
                        <label for="s3_prefix">Prefix (folder na S3)</label>
                        <input type="text" id="s3_prefix" name="S3Config[s3_prefix]" 
                               class="form-control" value="<?= Html::encode($settings['s3_prefix']) ?>" 
                               placeholder="archives">
                        <small class="form-text text-muted">Pliki będą przesyłane do: bucket/prefix/task_executions/...</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="mb-3">Poświadczenia AWS</h5>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Bezpieczeństwo:</strong> Dane dostępowe są przechowywane w bazie danych. 
                        Upewnij się, że dostęp do aplikacji jest odpowiednio zabezpieczony.
                    </div>

                    <div class="form-group">
                        <label for="s3_access_key">Access Key ID</label>
                        <input type="text" id="s3_access_key" name="S3Config[s3_access_key]" 
                               class="form-control" value="<?= Html::encode($settings['s3_access_key']) ?>" 
                               placeholder="AKIAIOSFODNN7EXAMPLE">
                    </div>

                    <div class="form-group">
                        <label for="s3_secret_key">Secret Access Key</label>
                        <input type="password" id="s3_secret_key" name="S3Config[s3_secret_key]" 
                               class="form-control" value="<?= Html::encode($settings['s3_secret_key']) ?>" 
                               placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY">
                        <small class="form-text text-muted">
                            <a href="https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html" target="_blank">
                                Jak utworzyć Access Keys?
                            </a>
                        </small>
                    </div>

                    <div class="alert alert-secondary">
                        <strong>Wymagane uprawnienia IAM:</strong>
                        <ul class="mb-0">
                            <li>s3:PutObject</li>
                            <li>s3:GetObject</li>
                            <li>s3:DeleteObject</li>
                            <li>s3:ListBucket</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="form-group mt-4">
                <?=
                Html::submitButton('<i class="fas fa-save me-2"></i>Zapisz konfigurację', [
                    'class' => 'btn btn-success',
                ])
                ?>

                <?=
                Html::a('<i class="fas fa-arrow-left me-2"></i>Powrót', ['archive/index'], [
                    'class' => 'btn btn-secondary',
                ])
                ?>
            </div>

<?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<JS
$('#test-connection-btn').on('click', function() {
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Testuję...');
    
    $.ajax({
        url: '/s3-config/test-connection',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('✓ Połączenie z S3 działa poprawnie!');
            } else {
                alert('✗ Błąd połączenia: ' + response.message);
            }
        },
        error: function() {
            alert('✗ Wystąpił błąd podczas testowania połączenia');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-plug me-2"></i>Testuj połączenie');
        }
    });
});
JS
);
?>
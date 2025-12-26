<?php

/** @var yii\web\View $this */
/** @var object $channel */
/** @var string $channelId */
/** @var string $channelName */
/** @var string $channelDescription */
/** @var array $settings */
/** @var array $configFields */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Ustawienia: ' . $channelName;
$this->params['breadcrumbs'][] = ['label' => 'Ustawienia', 'url' => ['index']];
$this->params['breadcrumbs'][] = $channelName;
?>

<div class="settings-channel">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="text-muted"><?= Html::encode($channelDescription) ?></p>
        </div>
        <?= Html::a('<i class="fas fa-arrow-left me-2"></i> Powrót', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Konfiguracja</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'channel-settings-form',
                    ]); ?>

                    <!-- Enabled -->
                    <div class="mb-3">
                        <label class="form-label">Włączony</label>
                        <div class="form-check form-switch">
                            <?= Html::checkbox(
                                'Settings[enabled]',
                                $settings['enabled'] ?? false,
                                [
                                    'class' => 'form-check-input',
                                    'id' => 'setting-enabled',
                                ]
                            ) ?>
                            <label class="form-check-label" for="setting-enabled">
                                Channel aktywny i może wysyłać powiadomienia
                            </label>
                        </div>
                    </div>

                    <!-- Cooldown -->
                    <div class="mb-3">
                        <?= Html::label('Cooldown (minuty)', 'setting-cooldown', ['class' => 'form-label']) ?>
                        <?= Html::textInput(
                            'Settings[cooldown]',
                            $settings['cooldown'] ?? 60,
                            [
                                'class' => 'form-control',
                                'id' => 'setting-cooldown',
                                'type' => 'number',
                                'min' => 1,
                            ]
                        ) ?>
                        <div class="form-text">
                            Minimalna liczba minut między kolejnymi powiadomieniami
                        </div>
                    </div>

                    <hr>

                    <!-- Dynamiczne pola z configFields -->
                    <?php if (!empty($configFields)): ?>
                        <?php foreach ($configFields as $fieldKey => $fieldConfig): ?>
                            <?php if (in_array($fieldKey, ['enabled', 'cooldown'])) continue; // już wyświetlone ?>
                            
                            <div class="mb-3">
                                <?php
                                $fieldName = "Settings[{$fieldKey}]";
                                $fieldValue = $settings[$fieldKey] ?? ($fieldConfig['default'] ?? '');
                                $fieldLabel = $fieldConfig['label'] ?? ucfirst($fieldKey);
                                $fieldType = $fieldConfig['type'] ?? 'text';
                                $fieldHelp = $fieldConfig['help'] ?? null;
                                $isRequired = $fieldConfig['required'] ?? false;
                                ?>

                                <?= Html::label($fieldLabel . ($isRequired ? ' *' : ''), 'setting-' . $fieldKey, ['class' => 'form-label']) ?>

                                <?php if ($fieldType === 'textarea'): ?>
                                    <?= Html::textarea($fieldName, $fieldValue, [
                                        'class' => 'form-control',
                                        'id' => 'setting-' . $fieldKey,
                                        'rows' => $fieldConfig['rows'] ?? 3,
                                        'placeholder' => $fieldConfig['placeholder'] ?? '',
                                    ]) ?>
                                <?php elseif ($fieldType === 'password'): ?>
                                    <?= Html::passwordInput($fieldName, $fieldValue, [
                                        'class' => 'form-control',
                                        'id' => 'setting-' . $fieldKey,
                                        'placeholder' => $fieldConfig['placeholder'] ?? '',
                                    ]) ?>
                                <?php elseif ($fieldType === 'select'): ?>
                                    <?= Html::dropDownList($fieldName, $fieldValue, $fieldConfig['options'] ?? [], [
                                        'class' => 'form-select',
                                        'id' => 'setting-' . $fieldKey,
                                        'prompt' => $fieldConfig['prompt'] ?? null,
                                    ]) ?>
                                <?php elseif ($fieldType === 'checkbox'): ?>
                                    <div class="form-check">
                                        <?= Html::checkbox($fieldName, $fieldValue, [
                                            'class' => 'form-check-input',
                                            'id' => 'setting-' . $fieldKey,
                                        ]) ?>
                                        <label class="form-check-label" for="setting-<?= $fieldKey ?>">
                                            <?= $fieldHelp ?? 'Zaznacz aby włączyć' ?>
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <?= Html::textInput($fieldName, $fieldValue, [
                                        'class' => 'form-control',
                                        'id' => 'setting-' . $fieldKey,
                                        'type' => $fieldType === 'number' ? 'number' : 'text',
                                        'placeholder' => $fieldConfig['placeholder'] ?? '',
                                    ]) ?>
                                <?php endif; ?>

                                <?php if ($fieldHelp && $fieldType !== 'checkbox'): ?>
                                    <div class="form-text"><?= $fieldHelp ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <?= Html::submitButton('<i class="fas fa-save me-2"></i> Zapisz ustawienia', [
                            'class' => 'btn btn-success btn-lg'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Test channel -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-vial me-2"></i> Test channela</h6>
                </div>
                <div class="card-body">
                    <p class="small">Wyślij testowe powiadomienie aby sprawdzić konfigurację.</p>
                    
                    <?php if ($channelId === 'email'): ?>
                        <div class="mb-2">
                            <?= Html::textInput('test-recipient', '', [
                                'class' => 'form-control form-control-sm',
                                'id' => 'test-recipient',
                                'placeholder' => 'email@example.com',
                            ]) ?>
                        </div>
                    <?php elseif ($channelId === 'sms'): ?>
                        <div class="mb-2">
                            <?= Html::textInput('test-recipient', '', [
                                'class' => 'form-control form-control-sm',
                                'id' => 'test-recipient',
                                'placeholder' => '+48123456789',
                            ]) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?= Html::button('<i class="fas fa-paper-plane me-2"></i> Wyślij test', [
                        'class' => 'btn btn-sm btn-primary w-100',
                        'id' => 'test-channel-btn',
                        'data-url' => \yii\helpers\Url::to(['test', 'id' => $channelId]),
                    ]) ?>
                    
                    <div id="test-result" class="mt-2"></div>
                </div>
            </div>

            <!-- Info -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informacje</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2"><strong>ID channela:</strong> <?= Html::encode($channelId) ?></p>
                    <p class="small mb-0">Pamiętaj aby zapisać ustawienia przed testem.</p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$this->registerJs(<<<JS
$('#test-channel-btn').on('click', function() {
    const btn = $(this);
    const url = btn.data('url');
    const recipient = $('#test-recipient').val();
    const resultDiv = $('#test-result');
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Wysyłanie...');
    resultDiv.html('');
    
    $.ajax({
        url: url,
        method: 'POST',
        data: { recipient: recipient },
        success: function(response) {
            if (response.success) {
                resultDiv.html('<div class="alert alert-success alert-sm mb-0">✓ Test zakończony sukcesem!</div>');
            } else {
                resultDiv.html('<div class="alert alert-danger alert-sm mb-0">✗ Błąd: ' + (response.error || 'Unknown error') + '</div>');
            }
        },
        error: function() {
            resultDiv.html('<div class="alert alert-danger alert-sm mb-0">✗ Błąd połączenia</div>');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i> Wyślij test');
        }
    });
});
JS
);
?>
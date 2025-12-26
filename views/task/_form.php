<?php

/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var yii\widgets\ActiveForm $form */
/** @var array $parsers */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

?>

<div class="task-form">

    <?php $form = ActiveForm::begin([
        'id' => 'task-form',
        'options' => ['class' => 'needs-validation'],
    ]); ?>

    <div class="row">
        <div class="col-md-8">
            
            <?= $form->field($model, 'name')->textInput([
                'maxlength' => true,
                'placeholder' => 'np. Opłata za prąd',
                'autofocus' => true,
            ]) ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'category')->dropDownList([
                        '' => '-- Wybierz kategorię --',
                        'rachunki' => '💰 Rachunki',
                        'zakupy' => '🛒 Zakupy',
                        'rośliny' => '🌱 Rośliny',
                        'monitoring' => '📊 Monitoring',
                    ], ['prompt' => '']) ?>
                </div>
                
                <div class="col-md-6">
                    <?= $form->field($model, 'status')->dropDownList([
                        'active' => 'Aktywne',
                        'paused' => 'Wstrzymane',
                        'completed' => 'Wykonane',
                        'archived' => 'Archiwum',
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'parser_class')->dropDownList(
                        ArrayHelper::map($parsers, 'class', 'name'),
                        [
                            'prompt' => '-- Wybierz typ zadania --',
                            'id' => 'parser-select',
                        ]
                    )->label('Typ zadania (Parser)') ?>
                </div>
                
                <div class="col-md-6">
                    <?= $form->field($model, 'fetcher_class')->dropDownList([
                        'EmptyFetcher' => 'Brak (tylko przypomnienia)',
                        'UrlFetcher' => 'HTTP/HTTPS Request',
                    ])->label('Źródło danych (Fetcher)') ?>
                </div>
            </div>

            <?= $form->field($model, 'schedule')->textInput([
                'placeholder' => 'Cron expression (np. "0 9 * * *") lub "manual"',
            ])->hint('Przykłady: "0 9 * * *" (codziennie o 9:00), "0 12 1 * *" (1-go dnia miesiąca o 12:00), "manual" (tylko ręcznie)') ?>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'amount')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'placeholder' => '0.00',
                    ]) ?>
                </div>
                
                <div class="col-md-4">
                    <?= $form->field($model, 'currency')->textInput([
                        'maxlength' => 3,
                        'value' => $model->currency ?: 'PLN',
                    ]) ?>
                </div>
                
                <div class="col-md-4">
                    <?= $form->field($model, 'due_date')->input('date') ?>
                </div>
            </div>

            <?= $form->field($model, 'config')->textarea([
                'rows' => 6,
                'placeholder' => 'JSON z konfiguracją (opcjonalnie)',
                'class' => 'form-control font-monospace',
            ])->hint('Konfiguracja w formacie JSON, np: {"url": "https://example.com", "timeout": 30}') ?>

        </div>

        <div class="col-md-4">
            
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">🔔 Powiadomienia</h6>
                </div>
                <div class="card-body">
                    
                    <?= $form->field($model, 'notification_channels')->textarea([
                        'rows' => 3,
                        'placeholder' => '["email", "sms", "telegram"]',
                        'class' => 'form-control font-monospace small',
                    ])->label('Kanały powiadomień')->hint('JSON array, np: ["email", "sms"]') ?>

                    <?= $form->field($model, 'notification_recipients')->textarea([
                        'rows' => 3,
                        'placeholder' => '{"email": ["user@example.com"]}',
                        'class' => 'form-control font-monospace small',
                    ])->label('Odbiorcy')->hint('JSON object z odbiorcami dla każdego kanału') ?>

                    <?= $form->field($model, 'cooldown_minutes')->textInput([
                        'type' => 'number',
                        'value' => $model->cooldown_minutes ?: 60,
                    ])->hint('Czas między powiadomieniami (w minutach)') ?>

                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">ℹ️ Pomoc</h6>
                </div>
                <div class="card-body small">
                    <p><strong>Parsery:</strong></p>
                    <ul class="mb-2">
                        <li><strong>ReminderParser</strong> - przypomnienia o terminach</li>
                        <li><strong>AggregateParser</strong> - raporty zbiorcze (suma rachunków)</li>
                        <li><strong>PlantReminderParser</strong> - pielęgnacja roślin</li>
                        <li><strong>UrlHealthCheckParser</strong> - monitoring stron</li>
                    </ul>
                    
                    <p><strong>Harmonogram (cron):</strong></p>
                    <ul class="mb-0">
                        <li><code>0 9 * * *</code> - codziennie o 9:00</li>
                        <li><code>0 12 1 * *</code> - 1-go każdego miesiąca o 12:00</li>
                        <li><code>0 8 * * 1</code> - w poniedziałki o 8:00</li>
                        <li><code>manual</code> - tylko ręcznie</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <div class="form-group mt-4">
        <?= Html::submitButton($model->isNewRecord ? '✓ Utwórz zadanie' : '✓ Zapisz zmiany', [
            'class' => 'btn btn-success btn-lg'
        ]) ?>
        <?= Html::a('Anuluj', ['index'], ['class' => 'btn btn-secondary btn-lg']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
$this->registerJs(<<<JS
// Automatyczne ustawienie fetchera na podstawie parsera
$('#parser-select').on('change', function() {
    var parser = $(this).val();
    var fetcherSelect = $('#task-fetcher_class');
    
    // Mapping parser -> domyślny fetcher
    var defaults = {
        'ReminderParser': 'EmptyFetcher',
        'AggregateParser': 'EmptyFetcher',
        'PlantReminderParser': 'EmptyFetcher',
        'UrlHealthCheckParser': 'UrlFetcher',
    };
    
    if (defaults[parser]) {
        fetcherSelect.val(defaults[parser]);
    }
});

// Walidacja JSON
$('form').on('submit', function(e) {
    var configField = $('#task-config');
    var config = configField.val().trim();
    
    if (config && config !== '') {
        try {
            JSON.parse(config);
        } catch (err) {
            alert('Błąd w polu Konfiguracja: nieprawidłowy JSON\\n' + err.message);
            configField.focus();
            e.preventDefault();
            return false;
        }
    }
    
    // Walidacja notification_channels
    var channelsField = $('#task-notification_channels');
    var channels = channelsField.val().trim();
    
    if (channels && channels !== '') {
        try {
            JSON.parse(channels);
        } catch (err) {
            alert('Błąd w polu Kanały powiadomień: nieprawidłowy JSON\\n' + err.message);
            channelsField.focus();
            e.preventDefault();
            return false;
        }
    }
    
    // Walidacja notification_recipients
    var recipientsField = $('#task-notification_recipients');
    var recipients = recipientsField.val().trim();
    
    if (recipients && recipients !== '') {
        try {
            JSON.parse(recipients);
        } catch (err) {
            alert('Błąd w polu Odbiorcy: nieprawidłowy JSON\\n' + err.message);
            recipientsField.focus();
            e.preventDefault();
            return false;
        }
    }
});
JS
);
?>

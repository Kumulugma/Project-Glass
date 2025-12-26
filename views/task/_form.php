<?php

/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var yii\widgets\ActiveForm $form */
/** @var array $parsers */
/** @var array $fetchers */
/** @var array $channels */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

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
                'placeholder' => 'np. Przypomnienie o opłacie za prąd',
                'autofocus' => true,
            ]) ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'parser_class')->dropDownList(
                        ArrayHelper::map($parsers, 'class', 'name'),
                        [
                            'prompt' => '-- Wybierz parser --',
                            'id' => 'parser-select',
                        ]
                    )->label('Parser') ?>
                    <p class="small text-muted mt-n2 mb-3" id="parser-description"></p>
                </div>
                
                <div class="col-md-6">
                    <?= $form->field($model, 'fetcher_class')->dropDownList(
                        ArrayHelper::map($fetchers, 'class', 'name'),
                        [
                            'prompt' => '-- Wybierz fetcher --',
                            'id' => 'fetcher-select',
                        ]
                    )->label('Fetcher') ?>
                    <p class="small text-muted mt-n2 mb-3" id="fetcher-description"></p>
                </div>
            </div>

            <!-- Konfiguracja JSON -->
            <?= $form->field($model, 'config')->textarea([
                'rows' => 12,
                'placeholder' => '{
  "amount": 150.00,
  "currency": "PLN",
  "due_date": "2025-02-15",
  "notify_before_days": 3,
  "reminder_message": "Przypomnienie: {{task_name}} - {{amount}} {{currency}}"
}',
                'class' => 'form-control font-monospace',
                'id' => 'task-config',
            ])->hint('Konfiguracja JSON dla parsera. Dostępne pola zależą od wybranego parsera.') ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'schedule')->textInput([
                        'placeholder' => 'Cron expression (np. 0 9 * * *) lub "manual"',
                    ])->hint('Przykłady: "0 9 * * *" (codziennie 9:00), "0 0 1 * *" (1. dnia miesiąca), "manual" (tylko ręcznie)') ?>
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

        </div>

        <div class="col-md-4">
            <!-- Kanały powiadomień -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Kanały powiadomień</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Przygotuj wybrane channele
                    $selectedChannels = [];
                    if ($model->notification_channels) {
                        $decoded = json_decode($model->notification_channels, true);
                        if (is_array($decoded)) {
                            $selectedChannels = $decoded;
                        }
                    }
                    
                    // Wyświetl checkboxy dla każdego channela
                    foreach ($channels as $channel):
                        $checked = in_array($channel['identifier'], $selectedChannels);
                    ?>
                        <div class="form-check mb-2">
                            <?= Html::checkbox(
                                'channels[]',
                                $checked,
                                [
                                    'value' => $channel['identifier'],
                                    'class' => 'form-check-input',
                                    'id' => 'channel-' . $channel['identifier'],
                                ]
                            ) ?>
                            <label class="form-check-label" for="channel-<?= $channel['identifier'] ?>">
                                <strong><?= Html::encode($channel['name']) ?></strong>
                                <br>
                                <small class="text-muted"><?= Html::encode($channel['description']) ?></small>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Odbiorcy -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Odbiorcy</h6>
                </div>
                <div class="card-body">
                    <?= $form->field($model, 'notification_recipients')->textarea([
                        'rows' => 4,
                        'placeholder' => '["email@example.com", "+48123456789"]',
                        'class' => 'form-control form-control-sm font-monospace',
                        'id' => 'task-notification_recipients',
                    ])->label(false)->hint('JSON array z odbiorcami (email, telefon, itp.)') ?>
                </div>
            </div>

            <!-- Cooldown -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Cooldown</h6>
                </div>
                <div class="card-body">
                    <?= $form->field($model, 'cooldown_minutes')->textInput([
                        'type' => 'number',
                        'min' => 1,
                        'placeholder' => '60',
                        'class' => 'form-control form-control-sm',
                    ])->label(false)->hint('Minuty między kolejnymi powiadomieniami') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? '✓ Utwórz zadanie' : '✓ Zapisz zmiany', [
            'class' => 'btn btn-success btn-lg'
        ]) ?>
        <?= Html::a('Anuluj', ['index'], ['class' => 'btn btn-secondary btn-lg']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
// Dane parserów i fetcherów jako JSON
$parsersData = json_encode($parsers);
$fetchersData = json_encode($fetchers);

// ============================================================
// NOWE: Przykładowe konfiguracje dla każdego parsera
// ============================================================
$parserExamples = [
    'ReminderParser' => json_encode([
        'due_date' => '2025-02-15',
        'notify_before_days' => 3,
        'amount' => 150.00,
        'currency' => 'PLN',
        'reminder_message' => 'Za {{days_until}} dni: {{task_name}} - {{amount}} {{currency}}',
        'today_message' => 'DZISIAJ upływa termin: {{task_name}} - {{amount}} {{currency}}',
        'overdue_message' => 'PRZETERMINOWANE ({{days_until}} dni temu): {{task_name}}'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    
    'PlantReminderParser' => json_encode([
        'action' => 'wysiew',
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-31',
        'reminder_message' => '🌱 Pamiętaj o {{action}}: {{plant_name}} (pozostało {{days_until_end}} dni)',
        'overdue_message' => 'Minął okres {{action}} dla: {{plant_name}}'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    
    'ShoppingItemParser' => json_encode([
        'shopping_category' => 'normalny',
        'message' => '🛒 Do kupienia: {{item_name}} - {{amount}} {{currency}}'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    
    'JsonEndpointParser' => json_encode([
        'fields_to_extract' => [
            'data.users.count' => ['transform' => 'int'],
            'data.status' => []
        ],
        'conditions' => [
            [
                'field' => 'data.users.count',
                'operator' => '>',
                'value' => 1000,
                'type' => 'alert',
                'subject' => 'Przekroczono limit użytkowników',
                'message' => 'Mamy już {{data.users.count}} użytkowników!',
                'priority' => 3
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    
    'AggregateParser' => json_encode([
        'source_category' => 'rachunki',
        'aggregate_type' => 'sum_amount',
        'period' => 'month',
        'report_message' => 'Suma rachunków w tym miesiącu: {{total_amount}} {{currency}}'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    
    'DatabaseFetcher' => json_encode([
        'table' => 'tasks',
        'select' => '*',
        'where' => ['status' => 'active'],
        'order_by' => 'created_at DESC',
        'limit' => 100
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
];

$parserExamplesJson = json_encode($parserExamples);

$this->registerJs(<<<JS
const parsers = $parsersData;
const fetchers = $fetchersData;
const parserExamples = $parserExamplesJson;

// Zapamiętaj poprzednio wybranego parsera
let previousParser = $('#parser-select').val();

// Automatyczne ustawienie fetchera na podstawie parsera + przykładowa konfiguracja
$('#parser-select').on('change', function() {
    const parserClass = $(this).val();
    const parser = parsers.find(p => p.class === parserClass);
    
    if (parser) {
        // Pokaż opis
        $('#parser-description').text(parser.description);
        
        // Ustaw domyślny fetcher
        if (parser.required_fetcher) {
            $('#fetcher-select').val(parser.required_fetcher);
            $('#fetcher-select').trigger('change');
        }
        
        // POPRAWIONE: Załaduj przykładową konfigurację przy zmianie parsera
        if (parserExamples[parserClass]) {
            const currentConfig = $('#task-config').val().trim();
            const currentParser = parserClass;
            
            // Warunki do wstawienia nowej konfiguracji:
            // 1. Pole jest puste, LUB
            // 2. Parser został zmieniony (nie jest to pierwsze załadowanie strony), LUB
            // 3. Obecna konfiguracja to przykład z innego parsera
            const shouldLoadExample = (
                !currentConfig || 
                currentConfig === '' || 
                (previousParser && previousParser !== currentParser) ||
                currentConfig.startsWith('{\\n  "amount"')
            );
            
            if (shouldLoadExample) {
                $('#task-config').val(parserExamples[parserClass]);
                
                // Dodaj animację dla lepszego UX (opcjonalne)
                $('#task-config').css('background-color', '#fffbcc');
                setTimeout(function() {
                    $('#task-config').css('background-color', '');
                }, 500);
            }
            
            // Zaktualizuj poprzedniego parsera
            previousParser = currentParser;
        }
    } else {
        $('#parser-description').text('');
    }
});

// Pokaż opis fetchera
$('#fetcher-select').on('change', function() {
    const fetcherClass = $(this).val();
    const fetcher = fetchers.find(f => f.class === fetcherClass);
    
    if (fetcher) {
        $('#fetcher-description').text(fetcher.description);
    } else {
        $('#fetcher-description').text('');
    }
});

// Trigger na start żeby pokazać opisy
if ($('#parser-select').val()) {
    $('#parser-select').trigger('change');
}
if ($('#fetcher-select').val()) {
    $('#fetcher-select').trigger('change');
}

// Walidacja JSON przed submitem
$('form').on('submit', function(e) {
    // Walidacja config
    const configField = $('#task-config');
    const config = configField.val().trim();
    
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
    
    // Walidacja notification_recipients
    const recipientsField = $('#task-notification_recipients');
    const recipients = recipientsField.val().trim();
    
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
    
    // Przekształć checkboxy channeli na JSON array
    const selectedChannels = [];
    $('input[name="channels[]"]:checked').each(function() {
        selectedChannels.push($(this).val());
    });
    
    // Usuń stare pole i dodaj nowe jako hidden input
    $('input[name="Task[notification_channels]"]').remove();
    $('<input>')
        .attr('type', 'hidden')
        .attr('name', 'Task[notification_channels]')
        .val(JSON.stringify(selectedChannels))
        .appendTo($(this));
});
JS
);
?>
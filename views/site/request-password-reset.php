<?php

/** @var yii\web\View $this */
/** @var app\models\PasswordResetRequestForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Resetowanie hasła';
?>

<div class="site-request-password-reset">
    <div class="row justify-content-center">
        <div class="col-md-5">
            
            <div class="text-center mb-4">
                <i class="fas fa-key" style="font-size: 3rem; color: var(--glass-warning);"></i>
                <h1 class="mt-3">Zapomniałeś hasła?</h1>
                <p class="text-muted">Nie martw się! Wyślemy Ci link do resetowania hasła.</p>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    
                    <p class="mb-4">Podaj adres email przypisany do Twojego konta. Wyślemy na niego link do utworzenia nowego hasła.</p>

                    <?php $form = ActiveForm::begin([
                        'id' => 'request-password-reset-form',
                    ]); ?>

                    <?= $form->field($model, 'email')->textInput([
                        'autofocus' => true,
                        'placeholder' => 'twoj@email.pl',
                        'type' => 'email',
                    ])->label('Adres email') ?>

                    <div class="form-group mt-4">
                        <?= Html::submitButton('<i class="fas fa-paper-plane me-2"></i> Wyślij link resetujący', [
                            'class' => 'btn btn-warning btn-lg w-100',
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                </div>
            </div>

            <div class="text-center mt-3">
                <?= Html::a('<i class="fas fa-arrow-left me-1"></i> Powrót do logowania', ['/site/login'], [
                    'class' => 'text-decoration-none',
                ]) ?>
            </div>

        </div>
    </div>
</div>

<style>
.site-request-password-reset {
    padding: 2rem 0;
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.site-request-password-reset .card {
    margin-top: 1rem;
    border: 1px solid var(--glass-border);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}
</style>
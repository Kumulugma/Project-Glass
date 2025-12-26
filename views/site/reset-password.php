<?php

/** @var yii\web\View $this */
/** @var app\models\ResetPasswordForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Nowe hasło';
?>

<div class="site-reset-password">
    <div class="row justify-content-center">
        <div class="col-md-5">
            
            <div class="text-center mb-4">
                <i class="fas fa-lock-open" style="font-size: 3rem; color: var(--glass-success);"></i>
                <h1 class="mt-3">Ustaw nowe hasło</h1>
                <p class="text-muted">Wprowadź nowe, bezpieczne hasło do swojego konta.</p>
            </div>

            <div class="card">
                <div class="card-body p-4">

                    <?php $form = ActiveForm::begin([
                        'id' => 'reset-password-form',
                    ]); ?>

                    <?= $form->field($model, 'password')->passwordInput([
                        'autofocus' => true,
                        'placeholder' => 'Minimum 6 znaków',
                    ])->label('Nowe hasło')->hint('Hasło powinno mieć minimum 6 znaków') ?>

                    <?= $form->field($model, 'password_repeat')->passwordInput([
                        'placeholder' => 'Powtórz hasło',
                    ])->label('Powtórz nowe hasło') ?>

                    <div class="form-group mt-4">
                        <?= Html::submitButton('<i class="fas fa-check-circle me-2"></i> Ustaw nowe hasło', [
                            'class' => 'btn btn-success btn-lg w-100',
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                </div>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Twoje hasło zostanie bezpiecznie zaszyfrowane
                </small>
            </div>

        </div>
    </div>
</div>

<style>
.site-reset-password {
    padding: 2rem 0;
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.site-reset-password .card {
    margin-top: 1rem;
    border: 1px solid var(--glass-border);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}
</style>
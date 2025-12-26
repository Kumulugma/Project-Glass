<?php

/** @var yii\web\View $this */
/** @var app\models\ChangePasswordForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Zmiana hasła';
?>

<div class="user-change-password">
    
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <h1><i class="fas fa-key me-2"></i> Zmiana hasła</h1>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ustaw nowe hasło</h5>
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Hasło powinno mieć minimum 6 znaków i być trudne do odgadnięcia.
                    </div>

                    <?php $form = ActiveForm::begin([
                        'id' => 'change-password-form',
                    ]); ?>

                    <?= $form->field($model, 'currentPassword')->passwordInput([
                        'placeholder' => 'Wpisz aktualne hasło',
                    ]) ?>

                    <hr class="my-4">

                    <?= $form->field($model, 'newPassword')->passwordInput([
                        'placeholder' => 'Minimum 6 znaków',
                    ]) ?>

                    <?= $form->field($model, 'confirmPassword')->passwordInput([
                        'placeholder' => 'Powtórz nowe hasło',
                    ]) ?>

                    <div class="form-group mt-4">
                        <?= Html::submitButton('<i class="fas fa-check-circle me-2"></i> Zmień hasło', [
                            'class' => 'btn btn-success',
                        ]) ?>
                        
                        <?= Html::a('<i class="fas fa-arrow-left me-2"></i> Powrót do profilu', ['profile'], [
                            'class' => 'btn btn-outline-secondary',
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                </div>
            </div>
            
            <div class="card mt-3 border-warning">
                <div class="card-body">
                    <h6><i class="fas fa-shield-alt text-warning me-2"></i> Wskazówki bezpieczeństwa</h6>
                    <ul class="mb-0 small">
                        <li>Używaj długich i skomplikowanych haseł</li>
                        <li>Nie używaj tego samego hasła w wielu miejscach</li>
                        <li>Zmieniaj hasło regularnie (co 3-6 miesięcy)</li>
                        <li>Nigdy nie udostępniaj swojego hasła innym osobom</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

</div>
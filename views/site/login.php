<?php

/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Logowanie - ' . Yii::$app->name;
?>

<div class="site-login">
    <div class="row justify-content-center">
        <div class="col-md-5">
            
            <div class="text-center mb-4">
                <i class="fas fa-gem" style="font-size: 3rem; color: var(--glass-primary);"></i>
                <h1 class="mt-3"><?= Html::encode(Yii::$app->name) ?></h1>
                <p class="text-muted">Zaloguj się do systemu</p>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    
                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'enableAjaxValidation' => false,
                    ]); ?>

                    <?= $form->field($model, 'username')->textInput([
                        'autofocus' => true,
                        'placeholder' => 'Wpisz nazwę użytkownika lub email',
                    ])->label('Nazwa użytkownika lub email') ?>

                    <?= $form->field($model, 'password')->passwordInput([
                        'placeholder' => 'Wpisz hasło',
                    ])->label('Hasło') ?>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <?= $form->field($model, 'rememberMe')->checkbox([
                                'template' => "<div class=\"form-check\">{input} {label}</div>\n{error}",
                            ])->label(false) ?>
                        </div>
                        <div>
                            <?= Html::a('Nie pamiętam hasła', ['/site/request-password-reset'], [
                                'class' => 'text-decoration-none small',
                            ]) ?>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <?= Html::submitButton('<i class="fas fa-sign-in-alt me-2"></i> Zaloguj się', [
                            'class' => 'btn btn-primary btn-lg w-100',
                            'name' => 'login-button'
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                </div>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Twoje dane są bezpiecznie szyfrowane
                </small>
            </div>

        </div>
    </div>
</div>

<style>
.site-login {
    padding: 2rem 0;
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
}

.site-login .card {
    margin-top: 1rem;
    border: 1px solid var(--glass-border);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.site-login .form-control:focus {
    border-color: var(--glass-primary);
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
}

.site-login .btn-primary {
    font-weight: 600;
    letter-spacing: 0.025em;
}

.site-login .form-check {
    display: flex;
    align-items: center;
    padding-left: 0;
}

.site-login .form-check-input {
    margin-right: 0.5rem;
    cursor: pointer;
}

.site-login .form-check-label {
    margin-bottom: 0;
    cursor: pointer;
    user-select: none;
}

.site-login .has-error .form-check-input {
    border-color: #dc3545;
}
</style>
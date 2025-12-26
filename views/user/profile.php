<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use app\models\User;

$this->title = 'Mój profil';
?>

<div class="user-profile">
    
    <div class="row">
        <div class="col-md-8">
            
            <h1><i class="fas fa-user-circle me-2"></i> Mój profil</h1>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Dane osobowe</h5>
                </div>
                <div class="card-body">
                    
                    <?php $form = ActiveForm::begin([
                        'id' => 'profile-form',
                    ]); ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'first_name')->textInput([
                                'placeholder' => 'Jan',
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'last_name')->textInput([
                                'placeholder' => 'Kowalski',
                            ]) ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'username')->textInput([
                        'placeholder' => 'jankowalski',
                    ]) ?>

                    <?= $form->field($model, 'email')->textInput([
                        'type' => 'email',
                        'placeholder' => 'jan@example.com',
                    ]) ?>

                    <div class="form-group mt-4">
                        <?= Html::submitButton('<i class="fas fa-save me-2"></i> Zapisz zmiany', [
                            'class' => 'btn btn-primary',
                        ]) ?>
                        
                        <?= Html::a('<i class="fas fa-key me-2"></i> Zmień hasło', ['change-password'], [
                            'class' => 'btn btn-outline-secondary',
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                </div>
            </div>

        </div>
        
        <div class="col-md-4">
            
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Informacje o koncie</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Rola:</dt>
                        <dd class="col-sm-7">
                            <?php if ($model->isAdmin): ?>
                                <span class="badge bg-danger">Administrator</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Użytkownik</span>
                            <?php endif; ?>
                        </dd>
                        
                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            <?php if ($model->status === User::STATUS_ACTIVE): ?>
                                <span class="badge bg-success">Aktywny</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Nieaktywny</span>
                            <?php endif; ?>
                        </dd>
                        
                        <dt class="col-sm-5">Utworzono:</dt>
                        <dd class="col-sm-7"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></dd>
                        
                        <?php if ($model->last_login_at): ?>
                            <dt class="col-sm-5">Ostatnie logowanie:</dt>
                            <dd class="col-sm-7">
                                <?= Yii::$app->formatter->asDatetime($model->last_login_at) ?>
                                <?php if ($model->last_login_ip): ?>
                                    <br><small class="text-muted"><?= Html::encode($model->last_login_ip) ?></small>
                                <?php endif; ?>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Bezpieczeństwo</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        Twoje hasło jest bezpiecznie zaszyfrowane
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-clock text-info me-2"></i>
                        Regularnie zmieniaj hasło dla bezpieczeństwa
                    </p>
                </div>
            </div>

        </div>
    </div>

</div>
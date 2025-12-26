<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var yii\bootstrap5\ActiveForm $form */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>

<div class="user-form">

    <div class="card">
        <div class="card-body">
            
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'first_name')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'last_name')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'type' => 'email']) ?>

            <?= $form->field($model, 'password')->passwordInput(['maxlength' => true])
                ->hint($model->isNewRecord ? 'Hasło musi mieć minimum 6 znaków' : 'Zostaw puste aby nie zmieniać hasła') ?>

            <?= $form->field($model, 'role')->dropDownList([
                \app\models\User::ROLE_USER => 'Użytkownik',
                \app\models\User::ROLE_ADMIN => 'Administrator',
            ]) ?>

            <?= $form->field($model, 'status')->dropDownList([
                \app\models\User::STATUS_ACTIVE => 'Aktywny',
                \app\models\User::STATUS_INACTIVE => 'Nieaktywny',
            ]) ?>

            <div class="form-group mt-4">
                <?= Html::submitButton('<i class="fas fa-save me-2"></i> Zapisz', ['class' => 'btn btn-success']) ?>
                <?= Html::a('<i class="fas fa-times me-2"></i> Anuluj', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>

        </div>
    </div>

</div>
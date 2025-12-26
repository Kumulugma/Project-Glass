<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */

use yii\bootstrap5\Html;

$this->title = 'Edycja: ' . $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Użytkownicy', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->fullName, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edycja';
?>

<div class="user-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>


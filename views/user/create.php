<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */

use yii\bootstrap5\Html;

$this->title = 'Dodaj użytkownika';
$this->params['breadcrumbs'][] = ['label' => 'Użytkownicy', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>


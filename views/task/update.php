<?php

/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var array $parsers */

use yii\helpers\Html;

$this->title = 'Edycja: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Zadania', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edycja';
?>

<div class="task-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'parsers' => $parsers,
    ]) ?>

</div>
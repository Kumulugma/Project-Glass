<?php

/** @var yii\web\View $this */
/** @var app\models\Task $model */
/** @var array $parsers */
/** @var array $fetchers */
/** @var array $channels */

use yii\helpers\Html;

$this->title = 'Nowe zadanie';
$this->params['breadcrumbs'][] = ['label' => 'Zadania', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="task-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'parsers' => $parsers,
        'fetchers' => $fetchers,
        'channels' => $channels,
    ]) ?>

</div>
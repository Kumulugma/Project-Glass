<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */

use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Zarządzanie użytkownikami';
?>

<div class="user-index">

    <h1><i class="fas fa-users me-2"></i> <?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('<i class="fas fa-plus me-2"></i> Dodaj użytkownika', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <!-- Statystyki -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-primary"><?= $stats['all'] ?></h3>
                    <p class="mb-0 text-muted">Wszyscy</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success"><?= $stats['active'] ?></h3>
                    <p class="mb-0 text-muted">Aktywni</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-warning"><?= $stats['inactive'] ?></h3>
                    <p class="mb-0 text-muted">Nieaktywni</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-danger"><?= $stats['admins'] ?></h3>
                    <p class="mb-0 text-muted">Administratorzy</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista użytkowników -->
    <div class="card">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'layout' => "{items}\n{pager}",
                'columns' => [
                    'id',
                    [
                        'attribute' => 'username',
                        'format' => 'raw',
                        'value' => function($model) {
                            return Html::a(Html::encode($model->username), ['view', 'id' => $model->id]);
                        },
                    ],
                    [
                        'label' => 'Pełne imię',
                        'value' => 'fullName',
                    ],
                    'email:email',
                    [
                        'attribute' => 'role',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->isAdmin 
                                ? '<span class="badge bg-danger">Admin</span>' 
                                : '<span class="badge bg-secondary">User</span>';
                        },
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->status === \app\models\User::STATUS_ACTIVE
                                ? '<span class="badge bg-success">Aktywny</span>'
                                : '<span class="badge bg-warning">Nieaktywny</span>';
                        },
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],
                    [
                        'attribute' => 'last_login_at',
                        'format' => 'datetime',
                        'headerOptions' => ['style' => 'width: 180px'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {update} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="fas fa-eye"></i>', ['view', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-info',
                                    'title' => 'Szczegóły',
                                ]);
                            },
                            'update' => function ($url, $model) {
                                return Html::a('<i class="fas fa-edit"></i>', ['update', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-warning',
                                    'title' => 'Edytuj',
                                ]);
                            },
                            'delete' => function ($url, $model) {
                                if ($model->id === Yii::$app->user->id) {
                                    return '';
                                }
                                return Html::a('<i class="fas fa-trash"></i>', ['delete', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'title' => 'Usuń',
                                    'data-method' => 'post',
                                    'data-confirm' => 'Czy na pewno usunąć tego użytkownika?',
                                ]);
                            },
                        ],
                        'headerOptions' => ['style' => 'width: 140px'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>
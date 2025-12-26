<?php
/** @var yii\web\View $this */
/** @var app\models\User $model */

/** @var yii\data\ActiveDataProvider $logsProvider */
use yii\bootstrap5\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

$this->title = $model->fullName;
$this->params['breadcrumbs'][] = ['label' => 'Użytkownicy', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('<i class="fas fa-arrow-left me-2"></i> Powrót', ['index'], ['class' => 'btn btn-secondary']) ?>
        <?= Html::a('<i class="fas fa-edit me-2"></i> Edytuj', ['update', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
        <?php if ($model->id !== Yii::$app->user->id): ?>
            <?=
            Html::a('<i class="fas fa-trash me-2"></i> Usuń', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data-method' => 'post',
                'data-confirm' => 'Czy na pewno usunąć tego użytkownika?',
            ])
            ?>
<?php endif; ?>
    </p>

    <div class="row">
        <div class="col-md-6">

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informacje podstawowe</h5>
                </div>
                <div class="card-body">
                    <?=
                    DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'username',
                            'email:email',
                            'first_name',
                            'last_name',
                            [
                                'attribute' => 'role',
                                'format' => 'raw',
                                'value' => $model->isAdmin ? '<span class="badge bg-danger">Administrator</span>' : '<span class="badge bg-secondary">Użytkownik</span>',
                            ],
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => $model->status === \app\models\User::STATUS_ACTIVE ? '<span class="badge bg-success">Aktywny</span>' : '<span class="badge bg-warning">Nieaktywny</span>',
                            ],
                            'created_at:datetime',
                            'updated_at:datetime',
                        ],
                    ])
                    ?>
                </div>
            </div>

        </div>

        <div class="col-md-6">

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Aktywność</h5>
                </div>
                <div class="card-body">
                    <?=
                    DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'last_login_at',
                                'format' => 'datetime',
                            ],
                            'last_login_ip',
                        ],
                    ])
                    ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Ostatnie logi -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Ostatnie aktywności</h5>
        </div>
        <div class="card-body p-0">
            <?=
            GridView::widget([
                'dataProvider' => $logsProvider,
                'tableOptions' => ['class' => 'table table-hover table-sm mb-0'],
                'layout' => "{items}\n{pager}",
                'columns' => [
                    [
                        'attribute' => 'created_at',
                        'format' => 'datetime',
                        'headerOptions' => ['style' => 'width: 180px'],
                    ],
                    [
                        'attribute' => 'action',
                        'value' => function ($model) {
                            return $model->getActionLabel();
                        },
                    ],
                    'description',
                    [
                        'attribute' => 'ip_address',
                        'headerOptions' => ['style' => 'width: 150px'],
                    ],
                ],
            ]);
            ?>
        </div>
    </div>

</div>


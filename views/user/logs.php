<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */
/** @var array $users */
/** @var array $actions */
/** @var int|null $selectedUserId */
/** @var string|null $selectedAction */

use yii\bootstrap5\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;

$this->title = 'Logi użytkowników';
?>

<div class="user-logs">

    <h1><i class="fas fa-user-shield me-2"></i> <?= Html::encode($this->title) ?></h1>

    <!-- Statystyki -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-primary"><?= $stats['all'] ?></h3>
                    <p class="mb-0 text-muted">Wszystkie logi</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success"><?= $stats['today'] ?></h3>
                    <p class="mb-0 text-muted">Dzisiaj</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-info"><?= $stats['week'] ?></h3>
                    <p class="mb-0 text-muted">Ostatnie 7 dni</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtry -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Użytkownik</label>
                    <?= Html::dropDownList('userId', $selectedUserId, 
                        ArrayHelper::map($users, 'id', function($user) {
                            return $user->fullName . ' (' . $user->username . ')';
                        }), 
                        ['class' => 'form-select', 'prompt' => 'Wszyscy użytkownicy']
                    ) ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Akcja</label>
                    <?= Html::dropDownList('action', $selectedAction, 
                        array_combine($actions, $actions),
                        ['class' => 'form-select', 'prompt' => 'Wszystkie akcje']
                    ) ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label><br>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i> Filtruj
                    </button>
                    <?= Html::a('Wyczyść', ['logs'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista logów -->
    <div class="card">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'layout' => "{items}\n{pager}",
                'columns' => [
                    [
                        'attribute' => 'created_at',
                        'format' => 'datetime',
                        'headerOptions' => ['style' => 'width: 180px'],
                    ],
                    [
                        'attribute' => 'user_id',
                        'label' => 'Użytkownik',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->user 
                                ? Html::a(Html::encode($model->user->fullName), ['/user/view', 'id' => $model->user_id])
                                : '<span class="text-muted">System</span>';
                        },
                    ],
                    [
                        'attribute' => 'action',
                        'value' => function($model) {
                            return $model->getActionLabel();
                        },
                        'headerOptions' => ['style' => 'width: 200px'],
                    ],
                    'description',
                    [
                        'attribute' => 'ip_address',
                        'headerOptions' => ['style' => 'width: 150px'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>
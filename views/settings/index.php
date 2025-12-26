<?php

/** @var yii\web\View $this */
/** @var array $channels */
/** @var array $channelStatuses */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Ustawienia';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="settings-index">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="row">
        <!-- Kanały powiadomień -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Kanały powiadomień</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($channels)): ?>
                        <div class="list-group-item">
                            <p class="text-muted mb-0">Brak dostępnych channeli.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($channels as $channel): ?>
                            <?php
                            $status = $channelStatuses[$channel['identifier']] ?? ['enabled' => false, 'cooldown' => 60];
                            $isEnabled = $status['enabled'];
                            $cooldown = $status['cooldown'];
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1">
                                            <?= Html::encode($channel['name']) ?>
                                            <?php if ($isEnabled): ?>
                                                <span class="badge bg-success ms-2">Włączony</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary ms-2">Wyłączony</span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="mb-2 small text-muted"><?= Html::encode($channel['description']) ?></p>
                                        <p class="mb-0 small">
                                            <i class="fas fa-clock me-1"></i>
                                            Cooldown: <strong><?= $cooldown ?> minut</strong>
                                        </p>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <?= Html::a(
                                            '<i class="fas fa-cog"></i> Konfiguruj',
                                            ['channel', 'id' => $channel['identifier']],
                                            ['class' => 'btn btn-sm btn-primary']
                                        ) ?>
                                        <?= Html::a(
                                            $isEnabled ? '<i class="fas fa-toggle-on"></i>' : '<i class="fas fa-toggle-off"></i>',
                                            ['toggle', 'id' => $channel['identifier']],
                                            [
                                                'class' => 'btn btn-sm ' . ($isEnabled ? 'btn-success' : 'btn-secondary'),
                                                'data-method' => 'post',
                                                'title' => $isEnabled ? 'Wyłącz' : 'Włącz',
                                            ]
                                        ) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Info box -->
    <div class="alert alert-info">
        <h6><i class="fas fa-info-circle me-2"></i> Informacje</h6>
        <ul class="mb-0">
            <li><strong>Cooldown</strong> określa ile czasu musi minąć między kolejnymi powiadomieniami z danego channela</li>
            <li>Wyłączony channel nie będzie wysyłał żadnych powiadomień</li>
            <li>Możesz przetestować konfigurację każdego channela na jego stronie ustawień</li>
        </ul>
    </div>

</div>
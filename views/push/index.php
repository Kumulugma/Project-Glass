<?php

/** @var yii\web\View $this */
/** @var app\models\PushSubscription[] $subscriptions */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Subskrypcje Push';
$this->params['breadcrumbs'][] = $this->title;

// Statystyki
$total = count($subscriptions);
$active = count(array_filter($subscriptions, fn($s) => $s->is_active));
$inactive = $total - $active;
?>

<div class="push-subscriptions-index">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-bell me-2"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="fas fa-plus me-2"></i> Zapisz to urządzenie', ['/push/subscribe-page'], ['class' => 'btn btn-success']) ?>
            <?= Html::a('<i class="fas fa-vial me-2"></i> Test', ['test'], [
                'class' => 'btn btn-outline-primary',
                'data-method' => 'post',
                'data-confirm' => 'Wysłać testowe powiadomienie do wszystkich aktywnych subskrypcji?',
            ]) ?>
        </div>
    </div>

    <!-- Statystyki -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-primary"><?= $total ?></h3>
                    <p class="mb-0 text-muted">Wszystkie subskrypcje</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success"><?= $active ?></h3>
                    <p class="mb-0 text-muted">Aktywne</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-danger"><?= $inactive ?></h3>
                    <p class="mb-0 text-muted">Nieaktywne</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista subskrypcji -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Przeglądarka / Urządzenie</th>
                            <th style="width: 150px;">Status</th>
                            <th style="width: 180px;">Utworzono</th>
                            <th style="width: 180px;">Ostatnie użycie</th>
                            <th style="width: 120px;">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($subscriptions)): ?>
                            <?php foreach ($subscriptions as $subscription): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $subscription->id ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <?php
                                                // Parsuj User Agent dla ikon
                                                $ua = $subscription->user_agent;
                                                $icon = 'fa-globe';
                                                $browser = 'Nieznana';
                                                
                                                if (stripos($ua, 'Chrome') !== false) {
                                                    $icon = 'fa-chrome';
                                                    $browser = 'Chrome';
                                                } elseif (stripos($ua, 'Firefox') !== false) {
                                                    $icon = 'fa-firefox';
                                                    $browser = 'Firefox';
                                                } elseif (stripos($ua, 'Safari') !== false) {
                                                    $icon = 'fa-safari';
                                                    $browser = 'Safari';
                                                } elseif (stripos($ua, 'Edge') !== false) {
                                                    $icon = 'fa-edge';
                                                    $browser = 'Edge';
                                                }
                                                
                                                $isMobile = stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false;
                                                $deviceIcon = $isMobile ? 'fa-mobile-alt' : 'fa-desktop';
                                                ?>
                                                <div class="mb-1">
                                                    <i class="fab <?= $icon ?> me-1"></i>
                                                    <strong><?= $browser ?></strong>
                                                    <i class="fas <?= $deviceIcon ?> ms-2 text-muted"></i>
                                                </div>
                                                <?php if ($subscription->device_name): ?>
                                                    <small class="text-muted"><?= Html::encode($subscription->device_name) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($subscription->is_active): ?>
                                            <span class="badge bg-success">✓ Aktywna</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">✗ Nieaktywna</span>
                                            <?php if ($subscription->failure_reason): ?>
                                                <br>
                                                <small class="text-muted" title="<?= Html::encode($subscription->failure_reason) ?>">
                                                    <?= Html::encode(mb_substr($subscription->failure_reason, 0, 30)) ?>...
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?= Yii::$app->formatter->asDatetime($subscription->created_at) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($subscription->last_used_at): ?>
                                            <?= Yii::$app->formatter->asDatetime($subscription->last_used_at) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?= Html::a('<i class="fas fa-trash"></i>', ['delete-subscription', 'id' => $subscription->id], [
                                            'class' => 'btn btn-sm btn-outline-danger',
                                            'title' => 'Usuń',
                                            'data-method' => 'post',
                                            'data-confirm' => 'Czy na pewno usunąć tę subskrypcję?',
                                        ]) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <div class="mb-2">📭</div>
                                    <div>Brak subskrypcji push</div>
                                    <div class="mt-2">
                                        <?= Html::a('Dodaj pierwszą subskrypcję', ['/push/subscribe-page'], ['class' => 'btn btn-sm btn-primary']) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="card mt-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i> Informacje</h6>
            <ul class="small mb-0">
                <li class="mb-2">Każda subskrypcja reprezentuje jedno urządzenie/przeglądarkę</li>
                <li class="mb-2">Subskrypcje mogą być anonimowe (bez user_id)</li>
                <li class="mb-2">Nieaktywne subskrypcje to te, które przestały odpowiadać (np. przeglądarka odinstalowana)</li>
                <li class="mb-2">Powiadomienia wysyłane przez task trafiają do WSZYSTKICH aktywnych subskrypcji</li>
                <li>Test wysyła powiadomienie do wszystkich aktywnych urządzeń</li>
            </ul>
        </div>
    </div>

</div>
<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

// Czcionka Inter z Google Fonts
$this->registerLinkTag(['rel' => 'preconnect', 'href' => 'https://fonts.googleapis.com']);
$this->registerLinkTag(['rel' => 'preconnect', 'href' => 'https://fonts.gstatic.com', 'crossorigin' => true]);
$this->registerLinkTag(['rel' => 'stylesheet', 'href' => 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap']);

// Font Awesome dla ikon
$this->registerLinkTag(['rel' => 'stylesheet', 'href' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <style>
        :root {
            --glass-primary: #2563eb;
            --glass-primary-dark: #1d4ed8;
            --glass-success: #10b981;
            --glass-warning: #f59e0b;
            --glass-danger: #ef4444;
            --glass-info: #06b6d4;
            --glass-dark: #1e293b;
            --glass-light: #f8fafc;
            --glass-border: #e2e8f0;
            --glass-text: #334155;
            --glass-text-light: #64748b;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: var(--glass-text);
            background-color: #f1f5f9;
        }
        
        /* Navbar */
        .navbar-dark {
            background: linear-gradient(135deg, var(--glass-dark) 0%, #334155 100%) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
            letter-spacing: -0.02em;
        }
        
        .nav-link {
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            padding: 0.5rem 0.75rem !important;
        }
        
        .nav-link:hover {
            color: rgba(255,255,255,0.9) !important;
            transform: translateY(-1px);
        }
        
        .navbar-nav .dropdown-menu {
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 0.5rem 0;
        }
        
        .navbar-nav .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        .navbar-nav .dropdown-item:hover {
            background: var(--glass-light);
            color: var(--glass-primary);
        }
        
        .navbar-nav .dropdown-divider {
            margin: 0.5rem 0;
        }
        
        /* Fix dla przycisku wylogowania w dropdown */
        .navbar-nav .dropdown-item form {
            margin: 0;
        }
        
        .navbar-nav .dropdown-item .btn-link {
            color: inherit;
            padding: 0;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .navbar-nav .dropdown-item .btn-link:hover {
            color: var(--glass-primary);
        }
        
        /* Main container */
        #main {
            padding-top: 80px;
            padding-bottom: 40px;
            min-height: calc(100vh - 140px);
        }
        
        .container {
            max-width: 1400px;
        }
        
        /* Cards */
        .card {
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
            background: white;
        }
        
        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--glass-border);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-primary {
            background: var(--glass-primary);
        }
        
        .btn-primary:hover {
            background: var(--glass-primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
        }
        
        .btn-success {
            background: var(--glass-success);
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }
        
        .btn-warning {
            background: var(--glass-warning);
        }
        
        .btn-danger {
            background: var(--glass-danger);
        }
        
        .btn-info {
            background: var(--glass-info);
        }
        
        /* Badges */
        .badge {
            padding: 0.35em 0.75em;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.85em;
        }
        
        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        /* Tables */
        .table {
            font-size: 14px;
        }
        
        .table thead th {
            background: var(--glass-light);
            color: var(--glass-text);
            font-weight: 600;
            border-bottom: 2px solid var(--glass-border);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 0.875rem;
        }
        
        .table tbody tr {
            transition: background 0.15s ease;
        }
        
        .table tbody tr:hover {
            background: #f8fafc;
        }
        
        /* Forms */
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--glass-text);
        }
        
        .form-control, .form-select {
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 0.625rem 0.875rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--glass-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Footer */
        .footer-bottom {
            background: white;
            border-top: 1px solid var(--glass-border);
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .footer-bottom-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .footer-bottom p {
            margin: 0;
            color: var(--glass-text-light);
            font-size: 0.9rem;
        }
        
        .powered-by {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .k3e-link {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--glass-primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .k3e-link:hover {
            color: var(--glass-primary-dark);
            gap: 0.375rem;
        }
        
        .k3e-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
        }
        
        /* Headings */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            color: var(--glass-dark);
            letter-spacing: -0.02em;
        }
        
        h1 { font-size: 2rem; margin-bottom: 1.5rem; }
        h2 { font-size: 1.75rem; }
        h3 { font-size: 1.5rem; }
        h4 { font-size: 1.25rem; }
        h5 { font-size: 1.125rem; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }
            
            #main {
                padding-top: 70px;
            }
            
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header id="header">
    <?php
    NavBar::begin([
        'brandLabel' => '<i class="fas fa-gem me-2"></i>' . Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar-expand-md navbar-dark fixed-top'],
        'innerContainerOptions' => ['class' => 'container'],
    ]);
    
    if (!Yii::$app->user->isGuest) {
    echo Nav::widget([
    'options' => ['class' => 'navbar-nav me-auto'],
    'items' => [
        ['label' => '<i class="fas fa-home me-1"></i> Dashboard', 'url' => ['/dashboard/index'], 'encode' => false],
        ['label' => '<i class="fas fa-mobile-alt me-1"></i> Mobilny', 'url' => ['/dashboard/mobile'], 'encode' => false],
        [
            'label' => '<i class="fas fa-tasks me-1"></i> Zadania',
            'encode' => false,
            'items' => [
                ['label' => '<i class="fas fa-list me-2"></i> Wszystkie zadania', 'url' => ['/task/index'], 'encode' => false],
                ['label' => '<i class="fas fa-plus-circle me-2"></i> Nowe zadanie', 'url' => ['/task/create'], 'encode' => false],
                '<div class="dropdown-divider"></div>',
                // NOWE - Wyniki fetcherów
                ['label' => '<i class="fas fa-database me-2"></i> Wyniki', 'url' => ['/results/index'], 'encode' => false],
            ],
        ],
        [
            'label' => '<i class="fas fa-cog me-1"></i> System',
            'encode' => false,
            'items' => [
                ['label' => '<i class="fas fa-bell me-2"></i> Powiadomienia', 'url' => ['/notification/index'], 'encode' => false],
                ['label' => '<i class="fas fa-history me-2"></i> Historia wykonań', 'url' => ['/execution/index'], 'encode' => false],
                ['label' => '<i class="fas fa-chart-bar me-2"></i> Statystyki', 'url' => ['/stats/index'], 'encode' => false],
                '<div class="dropdown-divider"></div>',
                // NOWE - Ustawienia channeli
                ['label' => '<i class="fas fa-sliders-h me-2"></i> Ustawienia channeli', 'url' => ['/settings/index'], 'encode' => false],
                '<div class="dropdown-divider"></div>',
                ['label' => '<i class="fas fa-user-shield me-2"></i> Logi użytkowników', 'url' => ['/user/logs'], 'encode' => false, 'visible' => Yii::$app->user->identity->isAdmin],
                ['label' => '<i class="fas fa-users me-2"></i> Zarządzaj użytkownikami', 'url' => ['/user/index'], 'encode' => false, 'visible' => Yii::$app->user->identity->isAdmin],
            ],
        ],
    ]
]);
    }
            
    $userMenuItems = [];
            
    if (Yii::$app->user->isGuest) {
        $userMenuItems[] = ['label' => '<i class="fas fa-sign-in-alt me-1"></i> Zaloguj się', 'url' => ['/site/login'], 'encode' => false];
    } else {
        $userMenuItems[] = [
            'label' => '<i class="fas fa-user-circle me-1"></i> ' . Html::encode(Yii::$app->user->identity->fullName),
            'encode' => false,
            'items' => [
                ['label' => '<i class="fas fa-user me-2"></i> Mój profil', 'url' => ['/user/profile'], 'encode' => false],
                ['label' => '<i class="fas fa-key me-2"></i> Zmień hasło', 'url' => ['/user/change-password'], 'encode' => false],
            ],
        ];
        $userMenuItems[] = [
            'label' => '<i class="fas fa-sign-out-alt me-1"></i> Wyloguj się',
            'url' => '#',
            'encode' => false,
            'linkOptions' => [
                'onclick' => 'document.getElementById("logout-form").submit(); return false;',
            ],
        ];
    }
            
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav ms-auto'],
        'items' => $userMenuItems,
    ]);
    
    // Ukryty formularz wylogowania
    if (!Yii::$app->user->isGuest) {
        echo Html::beginForm(['/site/logout'], 'post', ['id' => 'logout-form', 'style' => 'display:none;']);
        echo Html::endForm();
    }
    
    NavBar::end();
    ?>
</header>

<main id="main" class="flex-shrink-0" role="main">
    <div class="container">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= Yii::$app->session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= Yii::$app->session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('info')): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?= Yii::$app->session->getFlash('info') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?= $content ?>
    </div>
</main>

<footer id="footer" class="mt-auto">
    <div class="footer-bottom">
        <div class="footer-bottom-content">
            <p>
                © 2025 <?= Html::encode(Yii::$app->name) ?>. Wszystkie prawa zastrzeżone.
            </p>
            <p class="powered-by">
                Wspierane przez: 
                <a href="//k3e.pl" target="_blank" rel="noopener noreferrer" class="k3e-link">
                    <span class="k3e-logo">K</span>3e.pl
                    <i class="fas fa-external-link-alt" style="font-size: 0.8em;"></i>
                </a>
            </p>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
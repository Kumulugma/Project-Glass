<?php

use yii\helpers\Html;

/** @var \yii\web\View $this */
/** @var \app\models\User $user */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .content p {
            margin: 0 0 15px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background: #2563eb;
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background: #1d4ed8;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            background: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Resetowanie hasła</h1>
        </div>
        
        <div class="content">
            <p><strong>Witaj <?= Html::encode($user->fullName) ?>,</strong></p>
            
            <p>Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta w systemie <strong>GlassSystem</strong>.</p>
            
            <p>Kliknij poniższy przycisk, aby ustawić nowe hasło:</p>
            
            <div style="text-align: center;">
                <a href="<?= Html::encode($resetLink) ?>" class="button">
                    Zresetuj hasło
                </a>
            </div>
            
            <div class="info-box">
                <strong>⏰ Ważne informacje:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Link jest ważny przez <strong>1 godzinę</strong></li>
                    <li>Jeśli to nie Ty, zignoruj tę wiadomość</li>
                    <li>Twoje hasło pozostanie bez zmian</li>
                </ul>
            </div>
            
            <p style="margin-top: 30px; font-size: 14px; color: #64748b;">
                Jeśli przycisk nie działa, skopiuj i wklej poniższy link do przeglądarki:<br>
                <a href="<?= Html::encode($resetLink) ?>" style="word-break: break-all; color: #2563eb;">
                    <?= Html::encode($resetLink) ?>
                </a>
            </p>
        </div>
        
        <div class="footer">
            <p style="margin: 0 0 10px 0;">© <?= date('Y') ?> GlassSystem. Wszystkie prawa zastrzeżone.</p>
            <p style="margin: 0;">
                Wspierane przez 
                <a href="https://k3e.pl" target="_blank">K3e.pl</a>
            </p>
        </div>
    </div>
</body>
</html>
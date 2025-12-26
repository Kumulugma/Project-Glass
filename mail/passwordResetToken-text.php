<?php

use yii\helpers\Html;

/** @var \yii\web\View $this */
/** @var \app\models\User $user */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
?>
===============================================
🔐 RESETOWANIE HASŁA - GlassSystem
===============================================

Witaj <?= $user->fullName ?>,

Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta w systemie GlassSystem.

Kliknij poniższy link, aby ustawić nowe hasło:

<?= $resetLink ?>


⏰ WAŻNE INFORMACJE:
--------------------
• Link jest ważny przez 1 godzinę
• Jeśli to nie Ty złożyłeś tę prośbę, zignoruj tę wiadomość
• Twoje hasło pozostanie bez zmian, jeśli nie klikniesz linku


Jeśli masz problemy z linkiem, skopiuj go i wklej do przeglądarki.


===============================================
© <?= date('Y') ?> GlassSystem
Wspierane przez K3e.pl (https://k3e.pl)
===============================================